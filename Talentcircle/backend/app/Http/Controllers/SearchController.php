<?php
require_once __DIR__ . '/../../Models/User.php';

function sendJson($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function normalizeStr($s) {
    $s = (string)$s;
    return mb_strtolower(trim($s));
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];

$token = $input['token'] ?? '';

// NOTE: Search page is intended to be public. We do not require token.

$q = normalizeStr($input['q'] ?? ($_GET['q'] ?? ''));
$teach = normalizeStr($input['teach'] ?? ($_GET['teach'] ?? 'all'));
$learn = normalizeStr($input['learn'] ?? ($_GET['learn'] ?? 'all'));
$location = normalizeStr($input['location'] ?? ($_GET['location'] ?? 'all'));
$status = normalizeStr($input['status'] ?? ($_GET['status'] ?? 'all'));

// Map the UI codes to DB expectations.
$teachMap = [
    'python' => 'python',
    'uiux' => 'uiux',
    'laravel' => 'laravel',
    'network' => 'network',
    'all' => null
];
$learnMap = [
    'typescript' => 'typescript',
    'figma' => 'figma',
    'webrtc' => 'webrtc',
    'seo' => 'seo',
    'all' => null
];

$locationMap = [
    'pk' => 'pk',
    'us' => 'us',
    'uk' => 'uk',
    'de' => 'de',
    'all' => null
];

$teachFilter = $teachMap[$teach] ?? null;
$learnFilter = $learnMap[$learn] ?? null;
$locationFilter = $locationMap[$location] ?? null;

$wantOnline = null;
if ($status === 'online') $wantOnline = true;
elseif ($status === 'offline') $wantOnline = false;

$pdo = new PDO('mysql:host=127.0.0.1;dbname=talentcircle', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// We rely on users.skills_teach / skills_learn JSON columns.
// Expected columns based on other code: users(name,bio,image,skills_teach,skills_learn,phone,...) plus a location field.
// Since schema isn't shown, we attempt best-effort: location may be stored in users.phone or another column.
// We will try common options: users.location_code, users.location, users.region.

$users = $pdo->query('SELECT * FROM users LIMIT 200')->fetchAll(PDO::FETCH_ASSOC);

$matches = [];
foreach ($users as $u) {
    $name = normalizeStr($u['name'] ?? '');
    $bio = normalizeStr($u['bio'] ?? '');

    if ($q) {
        $qOk = (strpos($name, $q) !== false) || (strpos($bio, $q) !== false);
        if (!$qOk) continue;
    }

    $teachArr = json_decode($u['skills_teach'] ?: '[]', true);
    $learnArr = json_decode($u['skills_learn'] ?: '[]', true);

    // DB might store arrays of skill names/ids; we do a string match against elements.
    if ($teachFilter) {
        $teachOk = false;
        foreach ((array)$teachArr as $t) {
            if (normalizeStr($t) === $teachFilter) { $teachOk = true; break; }
        }
        if (!$teachOk) continue;
    }

    if ($learnFilter) {
        $learnOk = false;
        foreach ((array)$learnArr as $l) {
            if (normalizeStr($l) === $learnFilter) { $learnOk = true; break; }
        }
        if (!$learnOk) continue;
    }

    // location filter (best effort)
    if ($locationFilter) {
        $loc = $u['location_code'] ?? ($u['location'] ?? ($u['region'] ?? ''));
        if (normalizeStr($loc) !== $locationFilter) continue;
    }

    // status filter (best effort): online may be based on sessions or a flag.
    // We'll attempt a boolean column users.online.
    if ($wantOnline !== null) {
        $onlineVal = $u['online'] ?? null;
        if ($onlineVal === null) {
            // If unknown, do not filter out.
        } else {
            $onlineBool = (bool)$onlineVal;
            if ($onlineBool !== $wantOnline) continue;
        }
    }

    $matches[] = [
        'id' => $u['id'],
        'name' => $u['name'] ?? '',
        'bio' => substr($u['bio'] ?? '', 0, 100),
        'image' => $u['image'] ?? null,
        'score' => 0
    ];
}

// Return top results for the grid
usort($matches, function($a,$b){
    return ($b['score'] ?? 0) <=> ($a['score'] ?? 0);
});

sendJson([
    'peers' => array_slice($matches, 0, 24),
    'count' => count($matches)
]);

