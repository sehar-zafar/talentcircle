<?php
require_once __DIR__ . '/../../Models/User.php';
require_once __DIR__ . '/../../Models/Conversation.php';
require_once __DIR__ . '/../../Models/ChatMessage.php';

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

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

header('Content-Type: application/json');

$token = getToken($input);
$user = $token ? User::findByToken($token) : null;

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT');
    header('Access-Control-Allow-Headers: *');
    exit(0);
}

if (!$user) {
    sendJson(['error' => 'Unauthorized'], 401);
}

if ($method === 'GET' && $uri === '/api/chat/conversations') {
    $convs = Conversation::forUser($user->id);

    $out = [];
    foreach ($convs as $c) {
        $otherId = ($c->user_a_id == $user->id) ? $c->user_b_id : $c->user_a_id;
        $other = User::findByToken($otherId);

        $out[] = [
            'id' => (int)$c->id,
            'user_a_id' => (int)$c->user_a_id,
            'user_b_id' => (int)$c->user_b_id,
            'other_user' => $other ? [
                'id' => (int)$other->id,
                'name' => $other->name,
                'image' => $other->image
            ] : null,
            'created_at' => $c->created_at
        ];
    }

    sendJson(['conversations' => $out]);
}

if ($method === 'GET' && $uri === '/api/chat/messages') {
    $conversationId = isset($_GET['conversation_id']) ? (int)$_GET['conversation_id'] : 0;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 200;

    if (!$conversationId) {
        sendJson(['error' => 'conversation_id required'], 400);
    }

    $conv = Conversation::findIfUserIn($conversationId, $user->id);
    if (!$conv) {
        sendJson(['error' => 'Conversation not found'], 404);
    }

    $messages = ChatMessage::forConversation($conversationId, $limit);

    // Normalize output
    $out = [];
    foreach ($messages as $m) {
        $metadata = $m['metadata'];
        if (is_string($metadata) && $metadata !== '' && $metadata[0] === '{') {
            $decoded = json_decode($metadata, true);
            if (json_last_error() === JSON_ERROR_NONE) $metadata = $decoded;
        }

        $out[] = [
            'id' => (int)$m['id'],
            'conversation_id' => (int)$m['conversation_id'],
            'sender_user_id' => (int)$m['sender_user_id'],
            'type' => $m['type'] ?? 'text',
            'body' => $m['body'],
            'sticker_key' => $m['sticker_key'],
            'attachment_url' => $m['attachment_url'],
            'metadata' => $metadata,
            'created_at' => $m['created_at']
        ];
    }

    sendJson(['messages' => $out]);
}

if ($method === 'POST' && $uri === '/api/chat/messages') {
    $conversationId = isset($input['conversation_id']) ? (int)$input['conversation_id'] : 0;
    $type = isset($input['type']) ? (string)$input['type'] : 'text';
    $body = isset($input['body']) ? trim((string)$input['body']) : '';

    if (!$conversationId) {
        sendJson(['error' => 'conversation_id required'], 400);
    }

    if ($type === 'text') {
        if ($body === '') sendJson(['error' => 'body required for text message'], 400);
        if (mb_strlen($body) > 5000) sendJson(['error' => 'Message too long'], 400);
    } elseif ($type === 'sticker') {
        $stickerKey = isset($input['sticker_key']) ? (string)$input['sticker_key'] : '';
        if ($stickerKey === '') sendJson(['error' => 'sticker_key required for sticker message'], 400);
    } elseif ($type === 'call') {
        // call metadata can hold mode/status/options
    } else {
        // allow generic custom events
    }

    $conv = Conversation::findIfUserIn($conversationId, $user->id);
    if (!$conv) {
        sendJson(['error' => 'Conversation not found'], 404);
    }

    $payload = [
        'type' => $type,
        'body' => $type === 'text' ? $body : null,
        'sticker_key' => isset($input['sticker_key']) ? $input['sticker_key'] : null,
        'attachment_url' => isset($input['attachment_url']) ? $input['attachment_url'] : null,
        'metadata' => isset($input['metadata']) ? $input['metadata'] : null
    ];

    $message = ChatMessage::create($conversationId, $user->id, $payload);

    sendJson(['success' => true, 'message' => $message]);
}

sendJson(['error' => 'Not found'], 404);

