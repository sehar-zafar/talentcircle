<?php

return [
    'user_settings' => [
        'sql' => "CREATE TABLE IF NOT EXISTS user_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,

    -- Profile
    first_name VARCHAR(100) DEFAULT NULL,
    last_name VARCHAR(100) DEFAULT NULL,
    timezone VARCHAR(64) DEFAULT NULL,

    -- Appearance
    theme VARCHAR(32) DEFAULT 'Dark',
    accent_color VARCHAR(32) DEFAULT 'Violet (default)',
    font_size VARCHAR(32) DEFAULT 'Medium (default)',
    language VARCHAR(64) DEFAULT 'English (US)',

    -- Notifications
    notif_email_digests TINYINT(1) NOT NULL DEFAULT 1,
    notif_usage_alerts TINYINT(1) NOT NULL DEFAULT 1,
    notif_product_updates TINYINT(1) NOT NULL DEFAULT 0,
    notif_security_alerts TINYINT(1) NOT NULL DEFAULT 1,

    -- Privacy & Security
    two_factor_enabled TINYINT(1) NOT NULL DEFAULT 0,
    login_notifications_enabled TINYINT(1) NOT NULL DEFAULT 1,
    public_profile_enabled TINYINT(1) NOT NULL DEFAULT 0,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_user_settings_user
      FOREIGN KEY (user_id) REFERENCES users(id)
      ON DELETE CASCADE
);",
    ],
];


