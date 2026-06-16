<?php

// Points + reputation tables.
// Returns the common structure expected by backend/database/migrate.php.

return [
    // --- users_points_daily ---
    "users_points_daily" => [
        "sql" => "CREATE TABLE IF NOT EXISTS users_points_daily (\n"
            . "  id INT AUTO_INCREMENT PRIMARY KEY,\n"
            . "  user_id INT NOT NULL,\n"
            . "  day DATE NOT NULL,\n"
            . "  login_claimed TINYINT(1) NOT NULL DEFAULT 0,\n"
            . "  login_day_index INT NOT NULL DEFAULT 1,\n"
            . "  survey_claimed TINYINT(1) NOT NULL DEFAULT 0,\n"
            . "  survey_choice VARCHAR(255) NULL,\n"
            . "  ads_watched_count INT NOT NULL DEFAULT 0,\n"
            . "  ads_limit INT NOT NULL DEFAULT 5,\n"
            . "  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n"
            . "  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n"
            . "  UNIQUE KEY uq_user_day (user_id, day),\n"
            . "  INDEX idx_user_day (user_id, day)\n"
            . ") ENGINE=InnoDB;",
    ],

    // --- users_points_events ---
    "users_points_events" => [
        "sql" => "CREATE TABLE IF NOT EXISTS users_points_events (\n"
            . "  id INT AUTO_INCREMENT PRIMARY KEY,\n"
            . "  user_id INT NOT NULL,\n"
            . "  day DATE NOT NULL,\n"
            . "  kind VARCHAR(64) NOT NULL,\n"
            . "  description VARCHAR(255) NOT NULL,\n"
            . "  tokens_awarded INT NOT NULL DEFAULT 0,\n"
            . "  xp_awarded INT NOT NULL DEFAULT 0,\n"
            . "  metadata JSON NULL,\n"
            . "  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n"
            . "  INDEX idx_user_created (user_id, created_at),\n"
            . "  INDEX idx_user_day (user_id, day)\n"
            . ") ENGINE=InnoDB;",
    ],

    // --- users_xp ---
    "users_xp" => [
        "sql" => "CREATE TABLE IF NOT EXISTS users_xp (\n"
            . "  id INT AUTO_INCREMENT PRIMARY KEY,\n"
            . "  user_id INT NOT NULL UNIQUE,\n"
            . "  total_xp INT NOT NULL DEFAULT 0,\n"
            . "  level INT NOT NULL DEFAULT 1,\n"
            . "  streak_days INT NOT NULL DEFAULT 0,\n"
            . "  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP\n"
            . ") ENGINE=InnoDB;",
    ],
];

