<?php

require_once __DIR__ . '/../../Models/User.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true) ?: [];

function sendJson($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function getToken() {
    return $_SERVER['HTTP_AUTHORIZATION'] ?? ($_GET['token'] ?? ($GLOBALS['input']['token'] ?? null));
}

function getPDO() {
    return new PDO('mysql:host=127.0.0.1;dbname=talentcircle', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
}

function getOrCreateDefaultsRow($pdo, $userId) {
    $stmt = $pdo->prepare('SELECT * FROM user_settings WHERE user_id = ?');
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row) {
        return $row;
    }

    // Build defaults matching the frontend exact state
    $defaults = [
        'user_id' => $userId,
        'first_name' => null,
        'last_name' => null,
        'timezone' => null,

        'theme' => 'Dark',
        'accent_color' => 'Violet (default)',
        'font_size' => 'Medium (default)',
        'language' => 'English (US)',

        'notif_email_digests' => 1,
        'notif_usage_alerts' => 1,
        'notif_product_updates' => 0,
        'notif_security_alerts' => 1,

        'two_factor_enabled' => 0,
        'login_notifications_enabled' => 1,
        'public_profile_enabled' => 0,
    ];

    $cols = array_keys($defaults);
    $placeholders = implode(',', array_fill(0, count($cols), '?'));
    $sql = 'INSERT INTO user_settings (' . implode(',', $cols) . ') VALUES (' . $placeholders . ')';
    $pdo->prepare($sql)->execute(array_values($defaults));

    $stmt = $pdo->prepare('SELECT * FROM user_settings WHERE user_id = ?');
    $stmt->execute([$userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function normalizeBool($v) {
    if (is_bool($v)) return $v ? 1 : 0;
    if (is_numeric($v)) return ((int)$v) ? 1 : 0;
    if (is_string($v)) {
        $s = strtolower(trim($v));
        if ($s === 'true' || $s === '1' || $s === 'on') return 1;
        if ($s === 'false' || $s === '0' || $s === 'off') return 0;
    }
    return null;
}

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];
$token = getToken();
$user = $token ? User::findByToken($token) : null;

if (!$user) {
    sendJson(['error' => 'Unauthorized'], 401);
}

$pdo = getPDO();

// ==========================================
// GET /api/settings
// ==========================================
if ($method === 'GET' && $uri === '/api/settings') {
    $row = getOrCreateDefaultsRow($pdo, (int)$user->id);

    sendJson([
        'settings' => [
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'],
            'timezone' => $row['timezone'],

            'theme' => $row['theme'],
            'accent_color' => $row['accent_color'],
            'font_size' => $row['font_size'],
            'language' => $row['language'],

            'notif_email_digests' => (int)$row['notif_email_digests'],
            'notif_usage_alerts' => (int)$row['notif_usage_alerts'],
            'notif_product_updates' => (int)$row['notif_product_updates'],
            'notif_security_alerts' => (int)$row['notif_security_alerts'],

            'two_factor_enabled' => (int)$row['two_factor_enabled'],
            'login_notifications_enabled' => (int)$row['login_notifications_enabled'],
            'public_profile_enabled' => (int)$row['public_profile_enabled'],
        ]
    ]);
}

// ==========================================
// POST /api/settings/update
// ==========================================
if ($method === 'POST' && $uri === '/api/settings/update') {
    
    // Only accept keys strictly bound to our frontend layout
    $allowed = [
        'first_name', 'last_name', 'timezone',
        'theme', 'accent_color', 'font_size', 'language',
        'notif_email_digests', 'notif_usage_alerts', 'notif_product_updates', 'notif_security_alerts',
        'two_factor_enabled', 'login_notifications_enabled', 'public_profile_enabled'
    ];

    $toUpdate = [];
    foreach ($allowed as $k) {
        if (!array_key_exists($k, $input)) continue;
        $toUpdate[$k] = $input[$k];
    }

    // Normalize incoming booleans from the frontend toggles
    $boolKeys = [
        'notif_email_digests', 'notif_usage_alerts', 'notif_product_updates', 
        'notif_security_alerts', 'two_factor_enabled', 'login_notifications_enabled', 
        'public_profile_enabled'
    ];

    foreach ($boolKeys as $bKey) {
        if (array_key_exists($bKey, $toUpdate)) {
            $nv = normalizeBool($toUpdate[$bKey]);
            if ($nv === null) {
                sendJson(['error' => "Invalid boolean for {$bKey}"], 400);
            }
            $toUpdate[$bKey] = $nv;
        }
    }

    getOrCreateDefaultsRow($pdo, (int)$user->id);

    if (count($toUpdate) === 0) {
        sendJson(['success' => true, 'settings' => getOrCreateDefaultsRow($pdo, (int)$user->id)], 200);
    }

    // Build the dynamic update query
    $setParts = [];
    $params = [];
    foreach ($toUpdate as $col => $val) {
        $setParts[] = "$col = ?";
        $params[] = $val;
    }
    
    // Bind the user_id for the WHERE clause
    $params[] = (int)$user->id;

    $sql = 'UPDATE user_settings SET ' . implode(', ', $setParts) . ' WHERE user_id = ?';
    $pdo->prepare($sql)->execute($params);

    $row = getOrCreateDefaultsRow($pdo, (int)$user->id);

    sendJson([
        'success' => true,
        'settings' => [
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'],
            'timezone' => $row['timezone'],
            'theme' => $row['theme'],
            'accent_color' => $row['accent_color'],
            'font_size' => $row['font_size'],
            'language' => $row['language'],
            'notif_email_digests' => (int)$row['notif_email_digests'],
            'notif_usage_alerts' => (int)$row['notif_usage_alerts'],
            'notif_product_updates' => (int)$row['notif_product_updates'],
            'notif_security_alerts' => (int)$row['notif_security_alerts'],
            'two_factor_enabled' => (int)$row['two_factor_enabled'],
            'login_notifications_enabled' => (int)$row['login_notifications_enabled'],
            'public_profile_enabled' => (int)$row['public_profile_enabled'],
        ]
    ]);
}

sendJson(['error' => 'Not found'], 404);