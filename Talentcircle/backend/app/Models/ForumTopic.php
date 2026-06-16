<?php

require_once __DIR__ . '/../Models/User.php';

class ForumTopic {
    public $id;
    public $user_id;
    public $title;
    public $category;
    public $token_value;
    public $description;
    public $created_at;
    public $updated_at;

    private static $pdo;

    private static function getPDO() {
        if (!self::$pdo) {
            self::$pdo = new PDO('mysql:host=127.0.0.1;dbname=talentcircle', 'root', '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
        }
        return self::$pdo;
    }

    private static function fromRow($row) {
        $t = new self();
        foreach ($row as $k => $v) $t->$k = $v;
        return $t;
    }

    public static function search($category = null, $q = null, $limit = 50) {
        $pdo = self::getPDO();
        $limit = max(1, min(200, (int)$limit));

        $where = [];
        $params = [];

        if ($category) {
            $where[] = 'category = ?';
            $params[] = $category;
        }

        if ($q) {
            $where[] = '(title LIKE ? OR description LIKE ?)';
            $like = '%' . $q . '%';
            $params[] = $like;
            $params[] = $like;
        }

        $sql = 'SELECT * FROM forum_topics';
        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= ' ORDER BY created_at DESC LIMIT ?';
        $params[] = $limit;

        // Use explicit type binding for LIMIT placeholder stability
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);


        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $out = [];
        foreach ($rows as $r) $out[] = self::fromRow($r);
        return $out;
    }

    public static function findById($id) {
        $pdo = self::getPDO();
        $stmt = $pdo->prepare('SELECT * FROM forum_topics WHERE id = ? LIMIT 1');
        $stmt->execute([(int)$id]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        return $r ? self::fromRow($r) : null;
    }

    public static function create($userId, $title, $category, $tokenValue, $description) {
        $pdo = self::getPDO();

        $stmt = $pdo->prepare('
            INSERT INTO forum_topics (user_id, title, category, token_value, description, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, NOW(), NOW())
        ');

        $stmt->execute([
            (int)$userId,
            trim((string)$title),
            trim((string)$category),
            (int)$tokenValue,
            trim((string)$description)
        ]);

        $id = (int)$pdo->lastInsertId();
        return self::findById($id);
    }
}

