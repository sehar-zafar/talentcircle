<?php

$pdo = new PDO('mysql:host=127.0.0.1;dbname=talentcircle', 'root', '');

$users = [
    ['John AI Expert', 'john@example.com', ['AI', 'ML', 'Python'], ['JS', 'React'], 'AI specialist'],
    ['PHP Guru', 'php@example.com', ['PHP', 'Laravel', 'MySQL'], ['AI', 'Data'], 'Backend master'],
    ['JS Ninja', 'js@example.com', ['JS', 'React', 'Node'], ['PHP', 'SQL'], 'Fullstack dev'],
    ['UX Designer', 'ux@example.com', ['UX', 'Figma', 'HTML'], ['Coding'], 'Design pro'],
    ['Data Scientist', 'data@example.com', ['Python', 'SQL', 'AI'], ['Web Dev'], 'ML engineer']
];

foreach ($users as $u) {
    $hashed = password_hash('pass', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT IGNORE INTO users (name, email, password, skills_teach, skills_learn, bio) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$u[0], $u[1], $hashed, json_encode($u[2]), json_encode($u[3]), $u[4]]);
}

echo "Sample users seeded.";

