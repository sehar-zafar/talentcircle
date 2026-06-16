<?php

return [
    'otp_codes' => [
        'sql' => "CREATE TABLE IF NOT EXISTS otp_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone VARCHAR(20) NOT NULL,
    code VARCHAR(6) NOT NULL,
    expires_at INT NOT NULL, -- Unix timestamp
    used TINYINT(1) DEFAULT 0,
    name VARCHAR(255) DEFAULT NULL, -- optional name for registration
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_phone (phone),
    INDEX idx_expires (expires_at)
);",
    ],
];

