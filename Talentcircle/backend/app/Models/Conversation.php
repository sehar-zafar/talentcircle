<?php

class Conversation {
    public $id;
    public $user_a_id;
    public $user_b_id;
    public $created_at;

    private static $pdo;

    private static function getPDO() {
        if (!self::$pdo) {
            self::$pdo = new PDO('mysql:host=127.0.0.1;dbname=talentcircle', 'root', '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
        }
        return self::$pdo;
    }

    public static function forUser($userId) {
        $pdo = self::getPDO();
        $stmt = $pdo->prepare('SELECT * FROM chat_conversations WHERE user_a_id = ? OR user_b_id = ? ORDER BY created_at DESC');
        $stmt->execute([$userId, $userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $out = [];
        foreach ($rows as $r) {
            $c = new self();
            foreach ($r as $k => $v) $c->$k = $v;
            $out[] = $c;
        }
        return $out;
    }

    public static function findIfUserIn($conversationId, $userId) {
        $pdo = self::getPDO();
        $stmt = $pdo->prepare('SELECT * FROM chat_conversations WHERE id = ? AND (user_a_id = ? OR user_b_id = ?) LIMIT 1');
        $stmt->execute([$conversationId, $userId, $userId]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$r) return null;
        $c = new self();
        foreach ($r as $k => $v) $c->$k = $v;
        return $c;
    }
}

