<?php

// Simple migration runner compatibility note:
// This codebase uses a custom migrate.php runner.
// This migration follows the same pattern as other migrations in the repo.

class CreateSkillExchangeRequestsTable
{
    public static function up($pdo)
    {
        $sql = "
        CREATE TABLE IF NOT EXISTS skill_exchange_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            requester_user_id INT NOT NULL,
            target_user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',

            // Timeline/state timestamps
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL,
            accepted_at DATETIME NULL,
            rejected_at DATETIME NULL,
            started_at DATETIME NULL,
            completed_at DATETIME NULL,

            // Optional metadata
            requester_cancelled_at DATETIME NULL,
            cancel_reason VARCHAR(255) NULL,

            // For optimistic UI / idempotency
            client_request_key VARCHAR(64) NULL,

            INDEX idx_sender (requester_user_id),
            INDEX idx_target (target_user_id),
            INDEX idx_status (status),
            UNIQUE KEY uq_client_request_key (client_request_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";

        $pdo->exec($sql);
    }

    public static function down($pdo)
    {
        $pdo->exec('DROP TABLE IF EXISTS skill_exchange_requests');
    }
}

