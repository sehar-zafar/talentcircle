<?php
class UserSkill {
    public $id;
    public $user_id;
    public $skill_id;
    public $type; // 'have' or 'learn'
    public $verified;
    public $score;
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
    
    public static function find($id) {
        $pdo = self::getPDO();
        $stmt = $pdo->prepare('SELECT * FROM user_skills WHERE id = ?');
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($data) {
            $us = new self();
            foreach ($data as $key => $val) $us->$key = $val;
            return $us;
        }
        return null;
    }
    
    public static function forUser($userId, $type = null) {
        $pdo = self::getPDO();
        $sql = 'SELECT us.*, s.name as skill_name FROM user_skills us 
                JOIN skills s ON us.skill_id = s.id 
                WHERE us.user_id = ?';
        $params = [$userId];
        if ($type) {
            $sql .= ' AND us.type = ?';
            $params[] = $type;
        }
        $sql .= ' ORDER BY us.created_at DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public static function add($userId, $skillId, $type, $verified = 0, $score = 0) {
        $pdo = self::getPDO();
        $stmt = $pdo->prepare("INSERT INTO user_skills (user_id, skill_id, type, verified, score) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$userId, $skillId, $type, $verified, $score]);
    }
    
    public static function delete($userSkillId) {
        $pdo = self::getPDO();
        $stmt = $pdo->prepare('DELETE FROM user_skills WHERE id = ?');
        return $stmt->execute([$userSkillId]);
    }
    
    public static function updateVerified($userSkillId, $verified, $score) {
        $pdo = self::getPDO();
        $stmt = $pdo->prepare('UPDATE user_skills SET verified = ?, score = ? WHERE id = ?');
        return $stmt->execute([$verified, $score, $userSkillId]);
    }
    
    // Stats for profile
    public static function getUserStats($userId) {
        $pdo = self::getPDO();
        $stats = [
            'total_have' => 0, 'verified_count' => 0, 'avg_score' => 0
        ];
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as total_have, SUM(verified) as verified_count, 
                               AVG(score) as avg_score FROM user_skills WHERE user_id = ? AND type = 'have'");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_have'] = $row['total_have'];
        $stats['verified_count'] = $row['verified_count'] ?? 0;
        $stats['avg_score'] = round(($row['avg_score'] ?? 0), 1);
        
        return $stats;
    }
}
?>

