<?php
require_once __DIR__ . '/../../Models/User.php';
require_once __DIR__ . '/../../Models/Session.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$input = is_array($input) ? $input : [];

function sendJson($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function getToken($input) {
    return $_SERVER['HTTP_AUTHORIZATION'] ?? $_GET['token'] ?? ($input['token'] ?? null);
}

function escapeSystemText($s) {
    return trim((string)$s);
}

function formatTime($iso) {
    try {
        $d = new DateTime($iso);
        return $d->format('g:i A');
    } catch (Exception $e) {
        return $iso;
    }
}

function isToday($iso, $tz = 'UTC') {
    if (!$iso) return false;
    try {
        $d = new DateTime($iso);
        $d->setTimezone(new DateTimeZone($tz));
        $today = new DateTime('now', new DateTimeZone($tz));
        return $d->format('Y-m-d') === $today->format('Y-m-d');
    } catch (Exception $e) {
        return false;
    }
}

function computeBotStatus($session, $nowTs = null) {
    // Best-effort mapping from existing session.status + time.
    // session->status likely stores 'active'/'completed' etc, but we’ll keep robust.
    $statusRaw = strtolower((string)($session->status ?? ''));

    if (in_array($statusRaw, ['completed', 'done', 'finished', 'success', 'reviewed'], true)) {
        return 'done';
    }
    if (in_array($statusRaw, ['active', 'upcoming', 'scheduled'], true)) {
        // if scheduled_time in past => done
        if (!empty($session->scheduled_time)) {
            $now = $nowTs ?: time();
            try {
                $st = (new DateTime($session->scheduled_time))->getTimestamp();
                if ($st < $now - 60) return 'done';
                return 'upcoming';
            } catch (Exception $e) {
                return 'upcoming';
            }
        }
        return 'upcoming';
    }

    // fallback: if status empty, use time
    if (!empty($session->scheduled_time)) {
        $now = $nowTs ?: time();
        try {
            $st = (new DateTime($session->scheduled_time))->getTimestamp();
            if ($st < $now - 60) return 'done';
            return 'upcoming';
        } catch (Exception $e) {
            return 'upcoming';
        }
    }

    return 'upcoming';
}

function getOtherUserBrief($user, $otherUser) {
    return [
        'name' => (string)($otherUser->name ?? 'Unknown'),
        'image' => $otherUser->image ?? null,
    ];
}

function loadTodaySessionsForUser($userId) {
    // Session model only provides findAllByUser($user_id, $status = null).
    $all = Session::findAllByUser($userId, null);

    $todayTz = getenv('APP_TIMEZONE') ?: 'UTC';
    $today = [];
    foreach ($all as $s) {
        if (isToday($s->scheduled_time ?? null, $todayTz)) {
            $today[] = $s;
        }
    }

    // sort by scheduled_time asc
    usort($today, function($a, $b) {
        return strcmp((string)($a->scheduled_time ?? ''), (string)($b->scheduled_time ?? ''));
    });

    return $today;
}

function buildSystemPrompt($todaySessions, $timezone = 'UTC') {
    $now = new DateTime('now', new DateTimeZone($timezone));
    $todayLabel = $now->format('l, F j, Y');
    $nowLabel = $now->format('g:i A');

    $lines = [];
    foreach ($todaySessions as $i => $s) {
        $time = formatTime($s->scheduled_time);
        $name = (string)($s->matched_user_id ?? 'Candidate');
        $type = (string)($s->skill ?? 'Session');
        $platform = !empty($s->meet_link) ? 'Meet' : 'Unknown';
        $botStatus = computeBotStatus($s, $now->getTimestamp());

        // Keep wording stable for frontend use.
        $statusWord = strtoupper($botStatus === 'done' ? 'COMPLETED' : 'UPCOMING');
        $lines[] = ($i+1) . ". {$time} – {$name} | {$type} | {$platform} | STATUS: {$statusWord}";
    }

    $remaining = 0;
    $done = 0;
    foreach ($todaySessions as $s) {
        if (computeBotStatus($s, $now->getTimestamp()) === 'done') $done++; else $remaining++;
    }

    $rules = [
        '- Be concise and direct. No fluff.',
        '- Use bullet points or short structured answers where helpful.',
        '- When listing sessions, always include: name, time, session type, platform, and status.',
        '- If asked about "now" or "current" session, identify the first upcoming session based on scheduled_time and current time.',
        '- If asked to reschedule, suggest contacting the candidate directly via chat.' ,
        '- Keep responses under 120 words unless a full schedule list is requested.'
    ];

    return "You are the TalentCircle Session Assistant — a concise, professional AI that helps recruiters manage their daily interview sessions.\n\nToday is {$todayLabel}. The current time is approximately {$nowLabel}.\n\nThe user's scheduled sessions for today are:\n" . implode("\n", $lines) . "\n\nRules:\n" . implode("\n", $rules) . "\n\nThere are {$remaining} remaining session(s) today";
}

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

auth:
$token = getToken($input);
$user = $token ? User::findByToken($token) : null;
if (!$user) {
    sendJson(['error' => 'Unauthorized'], 401);
}

// Load today's sessions once when needed
$todaySessions = loadTodaySessionsForUser($user->id);

if ($method === 'GET' && $uri === '/api/bot/sessions/today') {
    $out = [];
    foreach ($todaySessions as $s) {
        $botStatus = computeBotStatus($s, time());
        $out[] = [
            'id' => (int)$s->id,
            'time' => formatTime($s->scheduled_time),
            'name' => (string)($s->matched_user_id ?? 'Candidate'),
            'type' => (string)($s->skill ?? 'Session'),
            'platform' => !empty($s->meet_link) ? 'Meet' : 'Unknown',
            'status' => $botStatus,
            'scheduled_time' => $s->scheduled_time,
            'meet_link' => $s->meet_link
        ];
    }

    $done = 0;
    foreach ($out as $x) if ($x['status'] === 'done') $done++;
    $left = count($out) - $done;

    sendJson([
        'date' => (new DateTime('now', new DateTimeZone(getenv('APP_TIMEZONE') ?: 'UTC')))->format('Y-m-d'),
        'sessions' => $out,
        'stats' => [
            'total' => count($out),
            'done' => $done,
            'left' => $left
        ]
    ]);
}

if ($method === 'POST' && $uri === '/api/bot/chat') {
    $message = trim((string)($input['message'] ?? ''));
    if ($message === '') {
        sendJson(['error' => 'message required'], 400);
    }

    $anthropicKey = getenv('ANTHROPIC_API_KEY') ?: '';
    if ($anthropicKey === '') {
        sendJson(['error' => 'Missing ANTHROPIC_API_KEY on server'], 500);
    }

    $timezone = getenv('APP_TIMEZONE') ?: 'UTC';
    $systemPrompt = buildSystemPrompt($todaySessions, $timezone);

    $payload = [
        'model' => 'claude-sonnet-4-20250514',
        'max_tokens' => 300,
        'system' => $systemPrompt,
        'messages' => [ ['role' => 'user', 'content' => $message] ]
    ];

    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'x-api-key: ' . $anthropicKey,
        'anthropic-version: 2023-06-01'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

    $resp = curl_exec($ch);
    if ($resp === false) {
        $err = curl_error($ch);
        curl_close($ch);
        sendJson(['error' => 'Anthropic request failed', 'details' => $err], 500);
    }
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($resp, true);
    if ($code < 200 || $code >= 300) {
        sendJson(['error' => 'Anthropic error', 'status' => $code, 'raw' => $data], 500);
    }

    $reply = $data['content'][0]['text'] ?? null;
    if (!$reply) {
        // Sometimes structure differs.
        $reply = 'Sorry, I couldn\'t get a response.';
    }

    sendJson(['reply' => $reply]);
}

sendJson(['error' => 'Not found'], 404);

