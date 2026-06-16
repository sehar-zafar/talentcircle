<?php

return [
    'password_reset_tokens' => [
        'sql' => "CREATE TABLE IF NOT EXISTS password_reset_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        token VARCHAR(255) NOT NULL,
        expires_at INT NOT NULL,
        used TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_email (email),
        INDEX idx_expires_at (expires_at),
        UNIQUE KEY uq_email_token (email, token)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
    ],
];


