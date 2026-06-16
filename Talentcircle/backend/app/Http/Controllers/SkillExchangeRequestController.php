<?php

require_once __DIR__ . '/../../Models/SkillExchangeRequest.php';
require_once __DIR__ . '/../../Models/User.php';

header('Content-Type: application/json');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT');
header('Access-Control-Allow-Headers: *');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

function sendJson($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function getToken() {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    return $_SERVER['HTTP_AUTHORIZATION'] ?? ($_GET['token'] ?? ($input['token'] ?? null));
}

function getRequestJson() {
    return json_decode(file_get_contents('php://input'), true) ?: [];
}

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

$input = getRequestJson();
$token = getToken();
$user = $token ? User::findByToken($token) : null;

if (!$user) {
    sendJson(['error' => 'Unauthorized'], 401);
}

// GET /api/skill-exchange/requests?tab=incoming|sent&status=pending...
if ($method === 'GET' && $uri === '/api/skill-exchange/requests') {
    $tab = $_GET['tab'] ?? 'incoming'; // incoming|sent
    $status = $_GET['status'] ?? null;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;

    $items = SkillExchangeRequest::listForUser($user->id, $tab, $status, $limit);
    $out = [];
    foreach ($items as $it) {
        $out[] = SkillExchangeRequest::toApi($it);
    }

    sendJson(['requests' => $out]);
}

// GET /api/skill-exchange/requests/{id}
if ($method === 'GET' && preg_match('#^/api/skill-exchange/requests/(\d+)$#', $uri, $m)) {
    $id = (int)$m[1];
    $req = SkillExchangeRequest::findById($id);
    if (!$req) sendJson(['error' => 'Not found'], 404);

    // Authorization: requester or target can view
    if ((int)$req->requester_user_id !== (int)$user->id && (int)$req->target_user_id !== (int)$user->id) {
        sendJson(['error' => 'Forbidden'], 403);
    }

    sendJson(['request' => SkillExchangeRequest::toApi($req)]);
}

// POST /api/skill-exchange/requests (send request)
if ($method === 'POST' && $uri === '/api/skill-exchange/requests') {
    $targetUserId = $input['target_user_id'] ?? $input['target'] ?? null;
    $title = $input['title'] ?? $input['topic'] ?? null;
    $description = $input['description'] ?? $input['desc'] ?? null;
    $clientKey = $input['client_request_key'] ?? null;

    if ($targetUserId === null) sendJson(['error' => 'target_user_id required'], 400);
    if ($title === null || trim((string)$title) === '') sendJson(['error' => 'title required'], 400);

    // No self-request
    if ((int)$targetUserId === (int)$user->id) {
        sendJson(['error' => 'Cannot request yourself'], 400);
    }

    $created = SkillExchangeRequest::create([
        'requester_user_id' => $user->id,
        'target_user_id' => (int)$targetUserId,
        'title' => (string)$title,
        'description' => $description,
        'client_request_key' => $clientKey
    ]);

    if (!$created) {
        sendJson(['error' => 'Failed to create request'], 500);
    }

    sendJson(['success' => true, 'request' => SkillExchangeRequest::toApi($created)]);
}

// POST /api/skill-exchange/requests/{id}/accept
if ($method === 'POST' && preg_match('#^/api/skill-exchange/requests/(\d+)/accept$#', $uri, $m)) {
    $id = (int)$m[1];
    $updated = SkillExchangeRequest::acceptByTarget($id, $user->id);
    if (!$updated) sendJson(['error' => 'Invalid transition'], 400);
    sendJson(['success' => true, 'request' => SkillExchangeRequest::toApi($updated)]);
}

// POST /api/skill-exchange/requests/{id}/reject
if ($method === 'POST' && preg_match('#^/api/skill-exchange/requests/(\d+)/reject$#', $uri, $m)) {
    $id = (int)$m[1];
    $reason = $input['reason'] ?? null;
    $updated = SkillExchangeRequest::rejectByTarget($id, $user->id, $reason);
    if (!$updated) sendJson(['error' => 'Invalid transition'], 400);
    sendJson(['success' => true, 'request' => SkillExchangeRequest::toApi($updated)]);
}

// POST /api/skill-exchange/requests/{id}/cancel
if ($method === 'POST' && preg_match('#^/api/skill-exchange/requests/(\d+)/cancel$#', $uri, $m)) {
    $id = (int)$m[1];
    $reason = $input['reason'] ?? null;
    $updated = SkillExchangeRequest::cancelByRequester($id, $user->id, $reason);
    if (!$updated) sendJson(['error' => 'Invalid transition'], 400);
    sendJson(['success' => true, 'request' => SkillExchangeRequest::toApi($updated)]);
}

// POST /api/skill-exchange/requests/{id}/start
if ($method === 'POST' && preg_match('#^/api/skill-exchange/requests/(\d+)/start$#', $uri, $m)) {
    $id = (int)$m[1];
    $updated = SkillExchangeRequest::startByTarget($id, $user->id);
    if (!$updated) sendJson(['error' => 'Invalid transition'], 400);
    sendJson(['success' => true, 'request' => SkillExchangeRequest::toApi($updated)]);
}

// POST /api/skill-exchange/requests/{id}/complete
if ($method === 'POST' && preg_match('#^/api/skill-exchange/requests/(\d+)/complete$#', $uri, $m)) {
    $id = (int)$m[1];
    $updated = SkillExchangeRequest::completeByTarget($id, $user->id);
    if (!$updated) sendJson(['error' => 'Invalid transition'], 400);
    sendJson(['success' => true, 'request' => SkillExchangeRequest::toApi($updated)]);
}

sendJson(['error' => 'Not found'], 404);

