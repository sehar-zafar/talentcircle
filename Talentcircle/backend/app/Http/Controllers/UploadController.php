<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: *');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

$uploadDir = '../../images/uploads/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$token = $_POST['token'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$pdo = new PDO('mysql:host=127.0.0.1;dbname=talentcircle', 'root', '');
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? OR remember_token = ?');
$stmt->execute([$token, $token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$uploaded = [];
foreach (['profile_image', 'certificates[]'] as $field) {
    if (isset($_FILES[$field])) {
        $files = is_array($_FILES[$field]['name']) ? $_FILES[$field]['name'] : [$_FILES[$field]['name']];
        foreach ($files as $i => $name) {
            if ($_FILES[$field]['error'][$i] == 0) {
                $ext = pathinfo($name, PATHINFO_EXTENSION);
                $filename = uniqid() . '.' . $ext;
                $path = $uploadDir . $filename;
                if (move_uploaded_file($_FILES[$field]['tmp_name'][$i], $path)) {
                    $uploaded[] = str_replace('../..', '', $path);
                }
            }
        }
    }
}

echo json_encode(['success' => true, 'files' => $uploaded]);
?>

