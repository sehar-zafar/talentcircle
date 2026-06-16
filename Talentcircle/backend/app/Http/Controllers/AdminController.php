<?php

require_once __DIR__ . '/../../Models/User.php';



$input = json_decode(file_get_contents('php://input'), true);

function sendJson($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

$token = $_SERVER['HTTP_AUTHORIZATION'] ?? $_GET['token'] ?? null;
$user = User::findByToken($token);
if (!$user || $user->role !== 'admin') {
    sendJson(['error' => 'Admin access required'], 403);
}

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if ($uri == '/api/admin/users') {
        $users = User::all();
        sendJson($users);
    }
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && $uri == '/api/admin/promote') {
    $targetId = $input['user_id'] ?? null;
    if (!$targetId) sendJson(['error' => 'User ID required'], 400);
    
    $pdo = User::getPDO();
    $stmt = $pdo->prepare('UPDATE users SET role = CASE WHEN role = "admin" THEN "user" ELSE "admin" END WHERE id = ?');
    $success = $stmt->execute([$targetId]);
    if ($success) {
        sendJson(['success' => true]);
    } else {
        sendJson(['error' => 'Update failed'], 500);
    }
} else {
    sendJson(['error' => 'Not found'], 404);
}

