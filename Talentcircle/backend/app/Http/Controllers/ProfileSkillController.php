<?php
require_once __DIR__ . '/../../Models/User.php';
require_once __DIR__ . '/../../Models/Skill.php';
require_once __DIR__ . '/../../Models/UserSkill.php';
require_once __DIR__ . '/../../Models/QuizQuestion.php';
require_once __DIR__ . '/../../Models/SkillTest.php';

$input = json_decode(file_get_contents('php://input'), true) ?: [];

function sendJson($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];
$pathParts = explode('/', trim($uri, '/'));

$token = $_GET['token'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? $input['token'] ?? null;
$user = User::findByToken($token);

if (!$user && strpos($uri, '/api/profile/') !== 0) { // Allow public profile view
    sendJson(['error' => 'Unauthorized'], 401);
}

if ($method == 'GET') {
    if ($uri == '/api/profile-skill') {
        // Own profile + stats
        $stats = UserSkill::getUserStats($user->id);
        $haveSkills = UserSkill::forUser($user->id, 'have');
        $learnSkills = UserSkill::forUser($user->id, 'learn');
        
        $profile = [
            'user' => (array)$user,
            'stats' => $stats,
            'skills_have' => $haveSkills,
            'skills_learn' => $learnSkills
        ];
        sendJson($profile);
        
    } elseif ($uri == '/api/skills') {
        $category = $_GET['category'] ?? null;
        $skills = Skill::all($category);
        sendJson(['skills' => $skills]);
        
    } elseif ($uri == '/api/skills/search') {
        $query = $_GET['q'] ?? '';
        $category = $_GET['category'] ?? null;
        if (strlen($query) < 2) sendJson(['skills' => []]);
        $skills = Skill::search($query, $category);
        sendJson(['skills' => $skills]);
        
    } elseif (preg_match('/\/api\/profile\/(\d+)/', $uri, $matches)) {
        // Own profile + stats (new endpoint /api/profile-skill)
        $stats = UserSkill::getUserStats($user->id);
        $haveSkills = UserSkill::forUser($user->id, 'have');
        $learnSkills = UserSkill::forUser($user->id, 'learn');
        
        $profile = [
            'user' => (array)$user,
            'stats' => $stats,
            'skills_have' => $haveSkills,
            'skills_learn' => $learnSkills
        ];
        sendJson($profile);
        
    } elseif (preg_match('/\/api\/profile\/(\d+)/', $uri, $matches)) {
        // Public profile for userId
        $targetId = $matches[1];
        $targetUser = User::findByToken($targetId); // Reuse findByToken(id)
        if (!$targetUser) sendJson(['error' => 'User not found'], 404);
        
        $stats = UserSkill::getUserStats($targetId);
        $publicSkills = UserSkill::forUser($targetId, 'have'); // Only show 'have' publicly
        
        sendJson([
            'user' => [
                'id' => $targetUser->id, 'name' => $targetUser->name,
                'bio' => $targetUser->bio, 'image' => $targetUser->image
            ],
            'stats' => $stats,
            'skills_have' => $publicSkills
        ]);
        
    } elseif (preg_match('/\/api\/quiz\/start\/(\d+)/', $uri, $matches)) {
        $skillId = $matches[1];
        $questions = QuizQuestion::randomForSkill($skillId, 10);
        sendJson(['questions' => $questions, 'skill_id' => $skillId]);
    }
    
} elseif ($method == 'POST') {
    if ($uri == '/api/profile/update') {
        // Update profile
        unset($input['token']); // Security
        $user->updateProfile($input);
        $stats = UserSkill::getUserStats($user->id);
        sendJson(['success' => true, 'user' => (array)$user, 'stats' => $stats]);
        
    } elseif ($uri == '/api/skills/add') {
        $skillName = $input['skill_name'] ?? '';
        $type = $input['type'] ?? 'learn'; // 'have' or 'learn'
        
        if (!$skillName || !in_array($type, ['have', 'learn'])) {
            sendJson(['error' => 'Invalid skill/type'], 400);
        }
        
        $skill = Skill::findByName($skillName);
        if (!$skill) {
            sendJson(['error' => 'Skill not found'], 404);
        }
        
        // Check if already added
        $existing = UserSkill::forUser($user->id, $type);
        foreach ($existing as $us) {
            if ($us['skill_id'] == $skill->id) {
                sendJson(['error' => 'Skill already added'], 400);
            }
        }
        
        UserSkill::add($user->id, $skill->id, $type);
        
        if ($type == 'have') {
            // Auto-trigger quiz
            $quizData = QuizQuestion::randomForSkill($skill->id, 10);
            sendJson([
                'success' => true,
                'message' => 'Skill added! Starting verification test...',
                'quiz' => ['questions' => $quizData, 'skill_id' => $skill->id]
            ]);
        } else {
            sendJson(['success' => true, 'message' => 'Skill added to learn list']);
        }
        
    } elseif ($uri == '/api/quiz/submit') {
        $skillId = $input['skill_id'] ?? 0;
        $answers = $input['answers'] ?? []; // array of selected indices
        $startTime = $input['start_time'] ?? 0;
        $duration = time() - $startTime;
        
        if (!$skillId || count($answers) < 5) {
            sendJson(['error' => 'Invalid submission'], 400);
        }
        
        // Get questions (same random set client should send back)
        $questions = QuizQuestion::randomForSkill($skillId, 10);
        if (count($questions) != count($answers)) {
            sendJson(['error' => 'Question mismatch (anti-cheat)'], 400);
        }
        
        $score = 0;
        foreach ($questions as $i => $q) {
            if ($answers[$i] == $q['correct_index']) $score++;
        }
        $score = round(($score / count($questions)) * 100);
        $verified = $score >= 70 ? 1 : 0;
        
        // Save test history
        SkillTest::save($user->id, $skillId, $score, $duration, $answers, $verified);
        
        // Update user_skill
        $userSkills = UserSkill::forUser($user->id, 'have');
        foreach ($userSkills as $us) {
            if ($us['skill_id'] == $skillId) {
                UserSkill::updateVerified($us['id'], $verified, $score);
                break;
            }
        }
        
        sendJson([
            'success' => true,
            'score' => $score,
            'verified' => $verified,
            'message' => $verified ? '🎉 Skill verified!' : 'Try again to verify!'
        ]);
    }
    
} elseif ($method == 'DELETE' && preg_match('/\/api\/skills\/(\d+)/', $uri, $matches)) {
    $userSkillId = $matches[1];
    UserSkill::delete($userSkillId);
    sendJson(['success' => true]);
}
?>

