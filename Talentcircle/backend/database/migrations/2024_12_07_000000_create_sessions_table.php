<?php

return [
    'sessions' => [
        'sql' => "
CREATE TABLE IF NOT EXISTS sessions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  matched_user_id INT NOT NULL,
  skill VARCHAR(255) NOT NULL,
  scheduled_time DATETIME NULL,
  meet_link TEXT NULL,
  status ENUM('active', 'completed') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user (user_id),
  INDEX idx_matched (matched_user_id),
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (matched_user_id) REFERENCES users(id)
)
"
    ]
];


