<?php

require_once __DIR__ . '/../../Models/User.php';
require_once __DIR__ . '/../../Models/Badge.php';
require_once __DIR__ . '/../../Models/Points.php';

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

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];
$token = getTokenFromRequest($input);
$user = $token ? User::findByToken($token) : null;

if (!$user) {
    sendJson(['error' => 'Unauthorized'], 401);
}

$day = date('Y-m-d');

// --- GET summary/badges/leaderboard/activity ---
if ($method === 'GET' && $uri === '/api/points/summary') {
    $daily = Points::ensureDailyRow($user->id, $day);
    $xpRow = Points::ensureUserXpRow($user->id);

    $tokensToday = 0;

    // Tokens today derived from events (simple + consistent)
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=talentcircle', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $stmt = $pdo->prepare('SELECT COALESCE(SUM(tokens_awarded),0) AS t FROM users_points_events WHERE user_id = ? AND day = ?');
    $stmt->execute([$user->id, $day]);
    $tokensToday = (int)($stmt->fetch(PDO::FETCH_ASSOC)['t'] ?? 0);

    $totalXp = (int)$xpRow['total_xp'];
    $level = (int)$xpRow['level'];

    $xpToNext = ($level * 500) - $totalXp;
    if ($xpToNext < 0) $xpToNext = 0;

    $progressPct = min(100, (int)round((($totalXp % 500) / 500) * 100));

    // Reset countdown to midnight
    $now = time();
    $tomorrow = strtotime('tomorrow 00:00:00');
    $resetIn = max(0, $tomorrow - $now);
    $h = floor($resetIn / 3600);
    $m = floor(($resetIn % 3600) / 60);
    $s = $resetIn % 60;
    $countdown = sprintf('%02d:%02d:%02d', $h, $m, $s);

    sendJson([
        'token_balance' => (int)$user->tokens,
        'today_tokens' => $tokensToday,
        'total_xp' => $totalXp,
        'level' => $level,
        'progress_pct' => $progressPct,
        'xp_to_next' => $xpToNext,
        'reset_countdown' => $countdown,
        'daily_state' => [
            'login_day_index' => (int)$daily['login_day_index'],
            'login_claimed' => (int)$daily['login_claimed'],
            'survey_claimed' => (int)$daily['survey_claimed'],
            'ads_watched_count' => (int)$daily['ads_watched_count'],
            'ads_limit' => (int)$daily['ads_limit'],
        ]
    ]);
}

if ($method === 'POST' && $uri === '/api/points/login/claim') {
    $daily = Points::ensureDailyRow($user->id, $day);

    if ((int)$daily['login_claimed'] === 1) {
        sendJson(['error' => 'Login already claimed'], 400);
    }

    // For now: escalating by day index within a week-like pattern: 15 + (index-1)*5 capped
    $dayIndex = max(1, (int)$daily['login_day_index']);
    $reward = min(50, 15 + ($dayIndex - 1) * 5);

    $xpReward = $reward; // simple mapping

    Points::addTokensAndXp($user->id, $reward, $xpReward);
    Points::recordEvent($user->id, 'login', 'Daily login claimed', $reward, $xpReward, ['dayIndex' => $dayIndex], $day);

    $pdo = new PDO('mysql:host=127.0.0.1;dbname=talentcircle', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdo->prepare('UPDATE users_points_daily SET login_claimed = 1, login_day_index = ? WHERE user_id = ? AND day = ?')
        ->execute([$dayIndex, $user->id, $day]);

    $user = User::findByToken($token);
    sendJson(['success' => true, 'tokens_awarded' => $reward]);
}

if ($method === 'POST' && $uri === '/api/points/survey/submit') {
    $choice = $input['choice'] ?? null;
    if (!$choice) sendJson(['error' => 'choice is required'], 400);

    $daily = Points::ensureDailyRow($user->id, $day);
    if ((int)$daily['survey_claimed'] === 1) {
        sendJson(['error' => 'Survey already submitted'], 400);
    }

    $reward = 20;
    $xpReward = 20;

    Points::addTokensAndXp($user->id, $reward, $xpReward);
    Points::recordEvent($user->id, 'survey', 'Survey submitted', $reward, $xpReward, ['choice' => $choice], $day);

    $pdo = new PDO('mysql:host=127.0.0.1;dbname=talentcircle', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdo->prepare('UPDATE users_points_daily SET survey_claimed = 1, survey_choice = ? WHERE user_id = ? AND day = ?')
        ->execute([$choice, $user->id, $day]);

    $user = User::findByToken($token);
    sendJson(['success' => true, 'tokens_awarded' => $reward]);
}

if ($method === 'POST' && $uri === '/api/points/ad/claim') {
    $daily = Points::ensureDailyRow($user->id, $day);

    $limit = (int)$daily['ads_limit'];
    $watched = (int)$daily['ads_watched_count'];

    if ($watched >= $limit) {
        sendJson(['error' => 'Daily ad limit reached'], 400);
    }

    // 10 tokens each
    $reward = 10;
    $xpReward = 10;

    Points::addTokensAndXp($user->id, $reward, $xpReward);
    Points::recordEvent($user->id, 'ad', 'Ad reward claimed', $reward, $xpReward, null, $day);

    $pdo = new PDO('mysql:host=127.0.0.1;dbname=talentcircle', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdo->prepare('UPDATE users_points_daily SET ads_watched_count = ads_watched_count + 1 WHERE user_id = ? AND day = ?')
        ->execute([$user->id, $day]);

    $user = User::findByToken($token);
    sendJson(['success' => true, 'tokens_awarded' => $reward, 'ads_watched_count' => $watched + 1]);
}

if ($method === 'GET' && $uri === '/api/points/badges') {
    // For now, reuse Badge catalog. Earned/progress is out of scope for this first pass.
    $badges = Badge::catalog();
    $out = [];
    foreach ($badges as $b) {
        $out[] = [
            'id' => $b['id'],
            'icon' => $b['icon'],
            'label' => $b['label'],
            'earned' => false,
            'rarity' => $b['rarity'],
            'desc' => $b['desc'] ?? '',
            'xp' => $b['xp'] ?? 0,
            'date' => $b['date'] ?? null,
            'progress' => $b['progress'] ?? null,
            'cat' => $b['cat'] ?? null
        ];
    }
    sendJson(['badges' => $out]);
}

if ($method === 'GET' && $uri === '/api/points/activity') {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=talentcircle', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $stmt = $pdo->prepare('SELECT * FROM users_points_events WHERE user_id = ? ORDER BY created_at DESC LIMIT 15');
    $stmt->execute([$user->id]);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $out = [];
    foreach ($events as $e) {
        $out[] = [
            'kind' => $e['kind'],
            'description' => $e['description'],
            'tokens_awarded' => (int)$e['tokens_awarded'],
            'xp_awarded' => (int)$e['xp_awarded'],
            'created_at' => $e['created_at'],
            'metadata' => $e['metadata'] ? json_decode($e['metadata'], true) : null
        ];
    }

    sendJson(['activity' => $out]);
}

if ($method === 'GET' && $uri === '/api/points/leaderboard') {
    $window = $_GET['window'] ?? 'week';

    // Simple global leaderboard based on total_xp.
    // Real week/month filtering would require event/rolling windows.
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=talentcircle', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $stmt = $pdo->prepare('SELECT u.id, u.name, u.id AS user_id, x.total_xp, SUBSTRING(u.name,1,1) as avatar_letter FROM users u JOIN users_xp x ON x.user_id = u.id ORDER BY x.total_xp DESC LIMIT 50');
    $stmt->execute([]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $startRank = 1;
    $list = [];
    foreach ($rows as $i => $r) {
        $list[] = [
            'rank' => $startRank + $i,
            'name' => $r['name'],
            'xp' => (int)$r['total_xp'],
            'av' => $r['avatar_letter'] ?: 'U',
            'isMe' => ((int)$r['id'] === (int)$user->id)
        ];
    }

    sendJson(['window' => $window, 'leaders' => $list]);
}

sendJson(['error' => 'Not found'], 404);

