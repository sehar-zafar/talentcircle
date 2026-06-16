<?php

require_once __DIR__ . '/../../Models/User.php';

function sendJson($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function jaccardSimilarity($a, $b) {
    $setA = array_unique($a);
    $setB = array_unique($b);
    $intersection = count(array_intersect($setA, $setB));
    $union = count(array_unique(array_merge($setA, $setB)));
    return $union ? $intersection / $union * 100 : 0;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$token = $input['token'] ?? '';
$user = User::findByToken($token);
if (!$user || $user->tokens < 10) {
    sendJson(['error' => 'Insufficient tokens. Need 10, have ' . ($user->tokens ?? 0)], 402);
}
$user->updateTokens(-10); // Deduct 10 tokens for AI match

$pdo = new PDO('mysql:host=127.0.0.1;dbname=talentcircle', 'root', '');
$stmt = $pdo->query("SELECT * FROM users LIMIT 20");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$skillsLearn = $input['skillsLearn'] ?? [];
$skillsTeach = $input['skillsTeach'] ?? [];
if (empty($skillsLearn) && empty($skillsTeach)) {
    sendJson(['error' => 'Please provide skills to learn or teach for matching'], 400);
}

$matches = [];
foreach ($users as $user) {
    $teach = json_decode($user['skills_teach'] ?: '[]', true);
    $learn = json_decode($user['skills_learn'] ?: '[]', true);
    
    $youWantTheyTeach = jaccardSimilarity($skillsLearn, $teach);
    $theyWantYouTeach = jaccardSimilarity($skillsTeach, $learn);
    $score = (($youWantTheyTeach + $theyWantYouTeach) / 2);
    
    $matches[] = [
        'id' => $user['id'],
        'name' => $user['name'],
        'bio' => substr($user['bio'], 0, 100),
        'score' => round($score * 100),
        'overlap' => array_intersect($skillsLearn, $teach)
    ];
}

usort($matches, fn($a, $b) => $b['score'] <=> $a['score']);

echo json_encode(['matches' => array_slice($matches, 0, 6), 'aiEngine' => 'PHP Jaccard v1.0']);

