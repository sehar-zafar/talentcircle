<?php

header('Content-Type: application/json');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT');
header('Access-Control-Allow-Headers: *');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') exit(0);

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

switch ($uri) {
    case '/api/register':
    case '/api/login':
    case '/api/auth/google':
    case '/api/otp/send':
    case '/api/otp/verify':
    case '/api/profile':
    case '/api/profile/update':
    case '/api/profile/setup':
        include __DIR__ . '/../app/Http/Controllers/AuthController.php';
        break;
    case '/api/ai-matches':
        include 'app/Http/Controllers/AiMatchController.php';
        break;
    case '/api/admin/users':
    case '/api/admin/promote':
        include 'app/Http/Controllers/AdminController.php';
        break;
    case '/api/upload':
        include 'app/Http/Controllers/UploadController.php';
        break;
    case '/api/session/start':
    case '/api/sessions':
        include 'app/Http/Controllers/SessionController.php';
        break;
    case '/api/forgot-password':
    case '/api/profile-skill':
    case '/api/skills':
    case '/api/skills/add':
    case '/api/skills/search':
    case '/api/quiz/start':
    case '/api/quiz/submit':
    case '/api/profile/':
        include 'app/Http/Controllers/AuthController.php';
        break;

    // Chat
    case '/api/chat/conversations':
    case '/api/chat/messages':
        include 'app/Http/Controllers/ChatController.php';
        break;


    // Forum (Community Hub)
    case '/api/forum/topics':
        include 'app/Http/Controllers/ForumController.php';
        break;

    // Bot
    case '/api/bot/sessions/today':
    case '/api/bot/chat':
        include 'app/Http/Controllers/BotController.php';
        break;



    // Badges
    case '/api/badges':
        include 'app/Http/Controllers/BadgesController.php';
        break;

    // Points
    case '/api/points/summary':
    case '/api/points/login/claim':
    case '/api/points/survey/submit':
    case '/api/points/ad/claim':
    case '/api/points/badges':
    case '/api/points/leaderboard':
    case '/api/points/activity':
        include 'app/Http/Controllers/PointsController.php';
        break;

    // Tokens / wallet
    case '/api/tokens/summary':
    case '/api/tokens/transactions':
        include 'app/Http/Controllers/TokensController.php';
        break;

    // Settings
    case '/api/settings':
    case '/api/settings/update':
        include 'app/Http/Controllers/SettingsController.php';
        break;

    // Search peers (Discover Peers)
    case '/api/search/peers':
        include 'app/Http/Controllers/SearchController.php';
        break;

    // Dashboard
    case '/api/dashboard/summary':
    case '/api/dashboard/sessions':
        include 'app/Http/Controllers/DashboardController.php';
        break;

    // Skill exchange requests
    case '/api/skill-exchange/requests':
        include 'app/Http/Controllers/SkillExchangeRequestController.php';
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Not found']);
}




