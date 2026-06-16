<?php

require_once __DIR__ . '/../../Models/User.php';
require_once __DIR__ . '/../../Models/Session.php';
require_once __DIR__ . '/../../Models/Points.php';
require_once __DIR__ . '/../../Models/Badge.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true) ?: [];

function sendJson($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function getTokenFromRequest($input) {
    return $_SERVER['HTTP_AUTHORIZATION'] ?? ($_GET['token'] ?? ($input['token'] ?? null));
}

function getPDO() {
    return new PDO('mysql:host=127.0.0.1;dbname=talentcircle', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
}

function safeInt($v, $default = 0) {
    if ($v === null || $v === '') return $default;
    return (int)$v;
}

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

$token = getTokenFromRequest($input);
$user = $token ? User::findByToken($token) : null;

if (!$user) {
    sendJson(['error' => 'Unauthorized'], 401);
}

$day = date('Y-m-d');

// GET /api/dashboard/summary
if ($method === 'GET' && $uri === '/api/dashboard/summary') {
    // KPIs (best-effort, derived from existing tables/models)
    $pdo = getPDO();

    // Total sessions & candidates/placements: best-effort from sessions table
    $totalSessions = 0;
    $placements = 0;
    $candidates = 0;

    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) AS c FROM sessions WHERE user_id = ?");
        $stmt->execute([$user->id]);
        $totalSessions = safeInt($stmt->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);

        $stmt = $pdo->prepare("SELECT COUNT(*) AS c FROM sessions WHERE user_id = ? AND status = 'completed'");
        $stmt->execute([$user->id]);
        $placements = safeInt($stmt->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);

        // candidates inferred from matched_user_id uniqueness
        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT matched_user_id) AS c FROM sessions WHERE user_id = ?");
        $stmt->execute([$user->id]);
        $candidates = safeInt($stmt->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);
    } catch (Throwable $e) {
        // keep defaults
    }

    // Avg rating: out of scope for now; keep null so frontend can keep placeholder
    $avgRating = null;

    // Charts seed: use points events for a monthly trend (last 12 months)
    $months = [];
    $sessionsLabels = [];
    $sessionsData = [];
    $placementData = [];

    for ($i = 11; $i >= 0; $i--) {
        $ts = strtotime("-{$i} months", time());
        $months[] = date('Y-m', $ts);
        $sessionsLabels[] = date('M', $ts);
        $sessionsData[] = 0;
        $placementData[] = 0;
    }

    try {
        $stmt = $pdo->prepare("SELECT status, scheduled_time FROM sessions WHERE user_id = ? ORDER BY scheduled_time ASC");
        $stmt->execute([$user->id]);
        $sessionsRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($sessionsRows as $r) {
            $ts = strtotime($r['scheduled_time'] ?? $r['created_at'] ?? '');
            if (!$ts) continue;
            $mkey = date('Y-m', $ts);
            $idx = array_search($mkey, $months, true);
            if ($idx === false) continue;
            $sessionsData[$idx] += 1;
            if (($r['status'] ?? '') === 'completed') {
                $placementData[$idx] += 1;
            }
        }
    } catch (Throwable $e) {
        // keep defaults
    }

    // Token earnings chart seed (weekly best-effort using points events)
    $weeklyLabels = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
    $weeklyTotals = array_fill(0, 7, 0);

    try {
        $stmt = $pdo->prepare("SELECT created_at, kind, tokens_awarded FROM users_points_events WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
        $stmt->execute([$user->id]);
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($events as $e) {
            // only count earned kinds
            $kind = strtolower((string)$e['kind']);
            if (!in_array($kind, ['teach','review','streak','refer','profile','survey','ad','login'], true)) continue;
            $ts = strtotime($e['created_at']);
            if (!$ts) continue;
            // PHP w: 1=Mon ... 7=Sun
            $w = (int)date('N', $ts);
            $idx = $w - 1;
            if ($idx >= 0 && $idx < 7) {
                $weeklyTotals[$idx] += safeInt($e['tokens_awarded']);
            }
        }
    } catch (Throwable $e) {
        // keep defaults
    }

    // Donut: allocations by session type best-effort from sessions.skill field
    $donutSegments = [
        ['label' => 'Career', 'val' => 0, 'color' => '#9b30d9'],
        ['label' => 'Interview', 'val' => 0, 'color' => '#b855f0'],
        ['label' => 'Resume', 'val' => 0, 'color' => '#22c55e'],
        ['label' => 'Mock', 'val' => 0, 'color' => '#60a5fa'],
    ];

    try {
        $stmt = $pdo->prepare("SELECT skill, scheduled_time FROM sessions WHERE user_id = ? AND scheduled_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $stmt->execute([$user->id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
            $skill = strtolower((string)($r['skill'] ?? ''));
            $typeIdx = null;
            if (str_contains($skill, 'career')) $typeIdx = 0;
            else if (str_contains($skill, 'interview')) $typeIdx = 1;
            else if (str_contains($skill, 'resume')) $typeIdx = 2;
            else if (str_contains($skill, 'mock')) $typeIdx = 3;

            if ($typeIdx !== null) {
                $donutSegments[$typeIdx]['val'] += 1;
            }
        }
    } catch (Throwable $e) {
        // keep defaults
    }

    return sendJson([
        'kpis' => [
            'total_sessions' => $totalSessions,
            'placements' => $placements,
            'candidates' => $candidates,
            'avg_rating' => $avgRating,
        ],
        'charts' => [
            'sessions' => [
                'labels' => $sessionsLabels,
                'sessions' => $sessionsData,
                'placements' => $placementData,
            ],
            'earnings' => [
                'labels' => $weeklyLabels,
                'tokens' => $weeklyTotals,
            ],
            'allocation' => [
                'segments' => array_map(fn($s) => ['label' => $s['label'], 'val' => $s['val']], $donutSegments),
            ],
        ],
        'token_balance' => (int)$user->tokens,
        'today' => [
            'tokens_today' => 0
        ],
    ]);
}

// GET /api/dashboard/sessions
if ($method === 'GET' && $uri === '/api/dashboard/sessions') {
    $type = $_GET['type'] ?? null;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

    // Use existing session model
    $sessions = Session::forUser($user->id, $type, $limit);

    return sendJson(['sessions' => $sessions]);
}

sendJson(['error' => 'Not found'], 404);

