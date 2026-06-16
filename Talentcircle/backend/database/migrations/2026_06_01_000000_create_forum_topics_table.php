<?php

return [
    'forum_topics' => [
        'sql' => "CREATE TABLE IF NOT EXISTS forum_topics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(180) NOT NULL,
    category VARCHAR(64) NOT NULL,
    token_value INT NOT NULL DEFAULT 0,
    description TEXT NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,

    INDEX idx_forum_topics_created_at (created_at),
    INDEX idx_forum_topics_category (category),
    INDEX idx_forum_topics_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    ],
];


