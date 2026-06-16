<?php
require_once __DIR__ . '/../../Models/User.php';
require_once __DIR__ . '/../../Models/ForumTopic.php';

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

// GET list topics
if ($method === 'GET' && $uri === '/api/forum/topics') {
    $category = isset($_GET['category']) ? trim((string)$_GET['category']) : null;
    $q = isset($_GET['q']) ? trim((string)$_GET['q']) : null;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;

    if ($category !== '' && $category !== 'All Feed') {
        // keep as-is
    } else {
        $category = null;
    }

    $topics = ForumTopic::search($category, $q, $limit);

    $out = [];
    foreach ($topics as $t) {
        $author = null;
        if (method_exists('User', 'findById')) {
            $author = User::findById($t->user_id);
        }


        // If author fetch fails, just return minimal
        $out[] = [
            'id' => (int)$t->id,
            'user_id' => (int)$t->user_id,
            'author' => $author ? [
                'id' => (int)$author->id,
                'name' => $author->name,
                'image' => $author->image
            ] : null,
            'title' => $t->title,
            'category' => $t->category,
            'token_value' => (int)$t->token_value,
            'description' => $t->description,
            'created_at' => $t->created_at
        ];
    }

    sendJson(['topics' => $out]);
}

// POST create topic
if ($method === 'POST' && $uri === '/api/forum/topics') {
    $title = isset($input['title']) ? (string)$input['title'] : '';
    $category = isset($input['category']) ? (string)$input['category'] : '';
    $tokenValue = isset($input['token_value']) ? (int)$input['token_value'] : 0;
    $description = isset($input['description']) ? (string)$input['description'] : '';

    $title = trim($title);
    $category = trim($category);
    $description = trim($description);

    if ($title === '') sendJson(['error' => 'title required'], 400);
    if ($category === '') sendJson(['error' => 'category required'], 400);
    if ($description === '') sendJson(['error' => 'description required'], 400);

    if (mb_strlen($title) > 180) sendJson(['error' => 'title too long'], 400);
    if (mb_strlen($description) > 5000) sendJson(['error' => 'description too long'], 400);

    $topic = ForumTopic::create($user->id, $title, $category, $tokenValue, $description);

    sendJson(['success' => true, 'topic' => [
        'id' => (int)$topic->id,
        'user_id' => (int)$topic->user_id,
        'title' => $topic->title,
        'category' => $topic->category,
        'token_value' => (int)$topic->token_value,
        'description' => $topic->description,
        'created_at' => $topic->created_at
    ]]);
}

sendJson(['error' => 'Not found'], 404);

