<?php

return [
    'chat_conversations' => [
        'sql' => "CREATE TABLE IF NOT EXISTS chat_conversations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_a_id INT NOT NULL,
    user_b_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_a (user_a_id),
    INDEX idx_user_b (user_b_id)
);",
    ],

    'chat_messages' => [
        'sql' => "CREATE TABLE IF NOT EXISTS chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    sender_user_id INT NOT NULL,
    type VARCHAR(32) NOT NULL DEFAULT 'text',
    body TEXT NULL,
    sticker_key VARCHAR(128) NULL,
    attachment_url TEXT NULL,
    metadata JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_conversation_id (conversation_id),
    INDEX idx_sender_user_id (sender_user_id),
    INDEX idx_created_at (created_at)
);",
    ],
];


