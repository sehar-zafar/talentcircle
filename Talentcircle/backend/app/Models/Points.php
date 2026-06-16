<?php

class Points
{
    private static function getPDO() {
        require_once __DIR__ . '/../Services/Database.php';
        return Database::connection();
    }


    public static function getDailyRow($userId, $day) {
        $pdo = self::getPDO();
        $stmt = $pdo->prepare('SELECT * FROM users_points_daily WHERE user_id = ? AND day = ? LIMIT 1');
        $stmt->execute([$userId, $day]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function ensureDailyRow($userId, $day) {
        $row = self::getDailyRow($userId, $day);
        if ($row) return $row;

        $pdo = self::getPDO();
        $pdo->prepare('INSERT INTO users_points_daily (user_id, day, login_day_index) VALUES (?, ?, 1)')
            ->execute([$userId, $day]);

        return self::getDailyRow($userId, $day);
    }

    public static function recordEvent($userId, $kind, $description, $tokens, $xp, $metadata = null, $day = null) {
        $pdo = self::getPDO();
        $day = $day ?: date('Y-m-d');
        $stmt = $pdo->prepare('INSERT INTO users_points_events (user_id, day, kind, description, tokens_awarded, xp_awarded, metadata) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $userId,
            $day,
            $kind,
            $description,
            (int)$tokens,
            (int)$xp,
            $metadata ? json_encode($metadata) : null
        ]);
    }

    public static function getUserXpRow($userId) {
        $pdo = self::getPDO();
        $stmt = $pdo->prepare('SELECT * FROM users_xp WHERE user_id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function ensureUserXpRow($userId) {
        $row = self::getUserXpRow($userId);
        if ($row) return $row;

        $pdo = self::getPDO();
        $pdo->prepare('INSERT INTO users_xp (user_id, total_xp, level, streak_days) VALUES (?, 0, 1, 0)')
            ->execute([$userId]);

        return self::getUserXpRow($userId);
    }

    public static function addTokensAndXp($userId, $tokensToAdd, $xpToAdd) {
        $pdo = self::getPDO();

        // Update tokens in users table
        $pdo->prepare('UPDATE users SET tokens = tokens + ?, last_login = CURDATE() WHERE id = ?')
            ->execute([(int)$tokensToAdd, (int)$userId]);

        // Update XP + level
        $xpRow = self::ensureUserXpRow($userId);
        $totalXp = (int)$xpRow['total_xp'] + (int)$xpToAdd;

        // Simple level curve for now: level increases every 500 XP
        $newLevel = max(1, (int)floor($totalXp / 500) + 1);

        $pdo->prepare('UPDATE users_xp SET total_xp = ?, level = ?, updated_at = NOW() WHERE user_id = ?')
            ->execute([(int)$totalXp, (int)$newLevel, (int)$userId]);

        return [
            'total_xp' => $totalXp,
            'level' => $newLevel
        ];
    }
}

