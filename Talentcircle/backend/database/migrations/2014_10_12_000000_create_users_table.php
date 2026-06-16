<?php

return [
    'users' => [
        'sql' => "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255),
    email VARCHAR(255) UNIQUE,
    password VARCHAR(255),
    image VARCHAR(500),
    age INT,
    education TEXT,
    certificates JSON DEFAULT '[]',
    skills_teach JSON,
    skills_learn JSON,
    bio TEXT,
    tokens INT DEFAULT 0,
    last_login DATE NULL,
    google_id VARCHAR(255) NULL,
    phone VARCHAR(25) NULL UNIQUE,
    remember_token VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_phone (phone),
    INDEX idx_email (email),
    INDEX idx_google_id (google_id)
);",
    ],
];

