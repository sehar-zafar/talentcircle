<?php
require_once __DIR__ . '/../../Models/User.php';
require_once __DIR__ . '/../../Models/Badge.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true) ?: [];

function sendJson($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

$token = $_SERVER['HTTP_AUTHORIZATION'] ?? $_GET['token'] ?? $input['token'] ?? null;
$user = $token ? User::findByToken($token) : null;

if (!$user) {
    sendJson(['error' => 'Unauthorized'], 401);
}

if ($method === 'GET' && $uri === '/api/badges') {
    // Stub: currently returns only catalog (all badges treated as locked).
    $badges = Badge::catalog();

    $out = [];
    foreach ($badges as $b) {
        $out[] = [
            'id' => $b['id'],
            'icon' => $b['icon'],
            'label' => $b['label'],
            'rarity' => $b['rarity'],
            'cat' => $b['cat'],
            'earned' => false,
            'xp' => $b['xp'] ?? 0,
            'date' => null,
            'desc' => $b['desc'] ?? '',
            'progress' => $b['progress'] ?? null,
        ];
    }

    sendJson(['badges' => $out]);
}

sendJson(['error' => 'Not found'], 404);

