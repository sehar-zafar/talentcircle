<?php
require_once __DIR__ . '/../../Models/Session.php';
require_once __DIR__ . '/../../Models/User.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true) ?: [];

function sendJson($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function getToken() {
    return $_SERVER['HTTP_AUTHORIZATION'] ?? $_GET['token'] ?? $GLOBALS['input']['token'] ?? null;
}

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

$token = $_SERVER['HTTP_AUTHORIZATION'] ?? $_GET['token'] ?? $input['token'] ?? null;
$user = $token ? User::findByToken($token) : null;

// Basic auth for session endpoints
if (!$user) {
    sendJson(['error' => 'Unauthorized'], 401);
}

if ($method === 'GET' && $uri === '/api/sessions') {
    // Optional query params: limit, type
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $type = $_GET['type'] ?? null;

    $sessions = Session::forUser($user->id, $type, $limit);
    sendJson(['sessions' => $sessions]);
}

// Google Meet (via Google Calendar API)
require_once __DIR__ . '/../../Services/GoogleMeetService.php';

if ($method === 'POST' && $uri === '/api/session/start') {

    // Body params: session_type, partner_user_id, title
    // Also supports scheduling params (used for Meet link generation):
    // - scheduled_time (ISO string)
    // - duration_minutes (int, default 60)
    // - timezone (string, default from env or UTC)
    $sessionType = $input['session_type'] ?? ($input['type'] ?? 'default');
    $partnerUserId = $input['partner_user_id'] ?? ($input['partner'] ?? null);
    $title = $input['title'] ?? null;

    if ($partnerUserId === null) {
        sendJson(['error' => 'partner_user_id required'], 400);
    }

    $durationMinutes = isset($input['duration_minutes']) ? (int)$input['duration_minutes'] : 60;
    if ($durationMinutes <= 0) $durationMinutes = 60;

    $timezone = $input['timezone'] ?? (getenv('GOOGLE_DEFAULT_TIMEZONE') ?: 'UTC');

    // Use provided scheduled_time or default to now.
    $scheduledTime = $input['scheduled_time'] ?? null;
    if (!$scheduledTime) {
        $scheduledTime = gmdate('c');
    }

    $start = new DateTime($scheduledTime);
    $end = clone $start;
    $end->modify('+' . $durationMinutes . ' minutes');

    // Google Calendar expects RFC3339 dateTime.
    $startStr = $start->format(DATE_ATOM);
    $endStr = $end->format(DATE_ATOM);

    $calendarId = $input['calendar_id'] ?? (getenv('GOOGLE_CALENDAR_ID') ?: 'primary');


    // Create session row with scheduled_time; meet_link will be set after Meet creation.
    $session = Session::createForUser($user->id, $partnerUserId, $sessionType, $title, $startStr);

    if (!$session) {
        sendJson(['error' => 'Failed to create session'], 500);
    }

    // Meet description
    $desc = 'Talent Circle session: ' . ($title ?: 'Session') . ' (' . $sessionType . ')';

    // Unique requestId for idempotency
    $requestId = 'tc_' . $session->id . '_' . bin2hex(random_bytes(6));

    $result = GoogleMeetService::createMeetLink(
        (string)$calendarId,
        ($title ?: 'Talent Circle Session'),
        $desc,
        $startStr,
        $endStr,
        (string)$timezone,
        (string)$requestId
    );

    if (isset($result['error'])) {
        // Keep session created even if Meet link fails.
        sendJson(['success' => true, 'session' => $session, 'warning' => $result['error']]);
    }

    // Persist meet link
    $pdo = Session::getPDO();
    $stmt = $pdo->prepare('UPDATE sessions SET meet_link = ? WHERE id = ?');
    $stmt->execute([$result['hangoutLink'], $session->id]);

    $session = Session::find($session->id);
    sendJson(['success' => true, 'session' => $session]);
}


sendJson(['error' => 'Not found'], 404);

