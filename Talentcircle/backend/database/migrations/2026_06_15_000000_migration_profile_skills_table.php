<?php

return [
    'skills' => [
        'sql' => "CREATE TABLE IF NOT EXISTS skills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    category VARCHAR(50) DEFAULT 'tech',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)"
    ],

    'user_skills' => [
        'sql' => "CREATE TABLE IF NOT EXISTS user_skills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    skill_id INT NOT NULL,
    type ENUM('have', 'learn') NOT NULL,
    verified TINYINT(1) DEFAULT 0,
    score INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_skill (user_id, skill_id, type),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE
)"
    ],

    'quiz_questions' => [
        'sql' => "CREATE TABLE IF NOT EXISTS quiz_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    skill_id INT NOT NULL,
    question TEXT NOT NULL,
    options JSON NOT NULL,
    correct_index INT NOT NULL,
    difficulty ENUM('easy', 'medium', 'hard') DEFAULT 'medium',
    FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE
)"
    ],

    'skill_tests' => [
        'sql' => "CREATE TABLE IF NOT EXISTS skill_tests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    skill_id INT NOT NULL,
    score INT NOT NULL,
    duration INT, -- seconds
    answers JSON,
    verified TINYINT(1) DEFAULT 0,
    attempt_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE
)"
    ],
];

