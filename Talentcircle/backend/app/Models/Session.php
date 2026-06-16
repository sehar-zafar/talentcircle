<?php

class Session {
    public $id;
    public $user_id;
    public $matched_user_id;
    public $skill;
    public $scheduled_time;
    public $meet_link;
    public $status;
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

    public static function create($data) {
        $pdo = self::getPDO();
        $stmt = $pdo->prepare('INSERT INTO sessions (user_id, matched_user_id, skill, scheduled_time, meet_link, status) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $data['user_id'],
            $data['matched_user_id'],
            $data['skill'],
            $data['scheduled_time'] ?? null,
            $data['meet_link'] ?? null,
            $data['status'] ?? 'active'
        ]);
        return self::find($pdo->lastInsertId());
    }

    public static function find($id) {
        $pdo = self::getPDO();
        $stmt = $pdo->prepare('SELECT * FROM sessions WHERE id = ?');
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($data) {
            $session = new self();
            foreach ($data as $key => $val) {
                $session->$key = $val;
            }
            return $session;
        }
        return null;
    }

    public static function findByUser($user_id) {
        $pdo = self::getPDO();
        $stmt = $pdo->prepare('SELECT * FROM sessions WHERE user_id = ? ORDER BY created_at DESC LIMIT 1');
        $stmt->execute([$user_id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($data) {
            $session = new self();
            foreach ($data as $key => $val) {
                $session->$key = $val;
            }
            return $session;
        }
        return null;
    }

    public static function findAllByUser($user_id, $status = null) {
        $pdo = self::getPDO();
        $sql = 'SELECT * FROM sessions WHERE user_id = ?';
        $params = [$user_id];
        if ($status) {
            $sql .= ' AND status = ?';
            $params[] = $status;
        }
        $sql .= ' ORDER BY scheduled_time DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $sessions = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $session = new self();
            foreach ($data as $key => $val) {
                $session->$key = $val;
            }
            $sessions[] = $session;
        }
        return $sessions;
    }

    public static function updateStatus($id, $status) {
        $pdo = self::getPDO();
        $stmt = $pdo->prepare('UPDATE sessions SET status = ? WHERE id = ?');
        return $stmt->execute([$status, $id]);
    }
}

