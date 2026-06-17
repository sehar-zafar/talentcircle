<?php

require_once __DIR__ . '/../../Models/User.php';

header('Content-Type: application/json');

function sendJson($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function getToken(array $input) {
    return $_SERVER['HTTP_AUTHORIZATION'] ?? $_GET['token'] ?? ($input['token'] ?? null);
}

function safeJsonDecode($value, $default = []) {
    if ($value === null || $value === '') return $default;
    if (is_array($value)) return $value;
    $decoded = json_decode((string)$value, true);
    return (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : $default;
}

function buildMatchSystemPrompt() {
    return "You are TalentCircle's AI Matching Assistant. "
        . "You will receive the requesting user's desired skills to learn and skills to teach, "
        . "and a candidate user's skills to learn/teach. "
        . "Return ONLY valid JSON matching the schema below. "
        . "Do not include any extra keys or markdown.";
}

function buildMatchUserPrompt(array $requestSkillsLearn, array $requestSkillsTeach, array $candidate) {
    $payload = [
        'requester' => [
            'skillsLearn' => array_values($requestSkillsLearn),
            'skillsTeach' => array_values($requestSkillsTeach)
        ],
        'candidate' => [
            'id' => $candidate['id'] ?? null,
            'name' => $candidate['name'] ?? null,
            'bio' => $candidate['bio'] ?? null,
            'skillsLearn' => $candidate['skills_learn'] ?? [],
            'skillsTeach' => $candidate['skills_teach'] ?? []
        ]
    ];

    // Important: ask for exact JSON schema.
    return "Compute how compatible the candidate is for skill exchange.\n\n" .
        "Request and candidate data:\n" . json_encode($payload, JSON_UNESCAPED_SLASHES) . "\n\n" .
        "Return JSON schema:\n" .
        json_encode([
            'compatibilityScore' => 0,          // integer 0-100
            'matchingSkills' => [],            // array of strings
            'learningPotential' => 'low' ,   // one of: low|medium|high
            'reason' => ''                    // short string
        ], JSON_UNESCAPED_SLASHES);
}

function anthropicClaudeJsonCompletion(array $payload, string $anthropicKey, int $timeoutSeconds = 15) {
    $ch = curl_init('https://api.anthropic.com/v1/messages');

    // Timeout handling
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeoutSeconds);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeoutSeconds);

    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'x-api-key: ' . $anthropicKey,
        'anthropic-version: 2023-06-01'
    ]);

    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

    $resp = curl_exec($ch);
    if ($resp === false) {
        $err = curl_error($ch);
        curl_close($ch);
        sendJson(['error' => 'Anthropic request failed', 'details' => $err], 500);
    }

    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($resp, true);
    if ($code < 200 || $code >= 300) {
        sendJson(['error' => 'Anthropic error', 'status' => $code, 'raw' => $data], 500);
    }

    return $data;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$token = getToken($input);
$user = $token ? User::findByToken($token) : null;

if (!$user || ($user->tokens ?? 0) < 10) {
    sendJson(['error' => 'Insufficient tokens. Need 10, have ' . ($user->tokens ?? 0)], 402);
}

// Deduct tokens for AI matching.
$user->updateTokens(-10);

$anthropicKey = getenv('ANTHROPIC_API_KEY') ?: '';
if ($anthropicKey === '') {
    sendJson(['error' => 'Missing ANTHROPIC_API_KEY on server'], 500);
}

$skillsLearn = $input['skillsLearn'] ?? [];
$skillsTeach = $input['skillsTeach'] ?? [];

if (!is_array($skillsLearn) || !is_array($skillsTeach)) {
    sendJson(['error' => 'skillsLearn and skillsTeach must be arrays'], 400);
}

if (empty($skillsLearn) && empty($skillsTeach)) {
    sendJson(['error' => 'Please provide skills to learn or teach for matching'], 400);
}

// Load candidate pool (keeps existing behavior: LIMIT 20)
$pdo = new PDO('mysql:host=127.0.0.1;dbname=talentcircle', 'root', '');
$stmt = $pdo->query('SELECT * FROM users LIMIT 20');
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$matches = [];
$systemPrompt = buildMatchSystemPrompt();

foreach ($users as $candidateRow) {
    $candidate = [
        'id' => $candidateRow['id'] ?? null,
        'name' => $candidateRow['name'] ?? null,
        'bio' => substr((string)($candidateRow['bio'] ?? ''), 0, 100),
        'skills_learn' => safeJsonDecode($candidateRow['skills_learn'] ?? '[]', []),
        'skills_teach' => safeJsonDecode($candidateRow['skills_teach'] ?? '[]', [])
    ];

    $userPrompt = buildMatchUserPrompt($skillsLearn, $skillsTeach, $candidate);

    // Call Claude once per candidate.
    $payload = [
        'model' => 'claude-sonnet-4-20250514',
        'max_tokens' => 250,
        'system' => $systemPrompt,
        'messages' => [
            ['role' => 'user', 'content' => $userPrompt]
        ]
    ];

    $resp = anthropicClaudeJsonCompletion($payload, $anthropicKey, 15);
    $text = $resp['content'][0]['text'] ?? '';

    // Claude should return JSON only. Try to parse it.
    $json = json_decode($text, true);
    if (!is_array($json)) {
        // Fallback: do not crash the whole endpoint; return a low-score default.
        $json = [
            'compatibilityScore' => 0,
            'matchingSkills' => [],
            'learningPotential' => 'low',
            'reason' => 'Model returned non-JSON or unparsable response.'
        ];
    }

    $compat = (int)($json['compatibilityScore'] ?? 0);
    if ($compat < 0) $compat = 0;
    if ($compat > 100) $compat = 100;

    $matches[] = [
        'id' => $candidate['id'],
        'name' => $candidate['name'],
        'bio' => $candidate['bio'],
        'score' => $compat, // Keep existing frontend field name "score"
        'matchingSkills' => $json['matchingSkills'] ?? [],
        'learningPotential' => $json['learningPotential'] ?? 'low',
        'reason' => $json['reason'] ?? ''
    ];
}

// Sort by AI compatibility score desc
usort($matches, fn($a, $b) => ($b['score'] ?? 0) <=> ($a['score'] ?? 0));

// Keep existing response shape as much as possible.
sendJson([
    'matches' => array_slice($matches, 0, 6),
    'aiEngine' => 'Anthropic Claude Sonnet 4'
]);


