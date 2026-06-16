<?php
class SkillTest {
    public $id;
    public $user_id;
    public $skill_id;
    public $score;
    public $duration;
    public $answers; // JSON
    public $verified;
    public $attempt_date;
    
    private static $pdo;
    
    private static function getPDO() {
        if (!self::$pdo) {
            self::$pdo = new PDO('mysql:host=127.0.0.1;dbname=talentcircle', 'root', '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
        }
        return self::$pdo;
    }
    
    public static function save($userId, $skillId, $score, $duration, $answers, $verified = 0) {
        $pdo = self::getPDO();
        $stmt = $pdo->prepare("INSERT INTO skill_tests (user_id, skill_id, score, duration, answers, verified) VALUES (?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$userId, $skillId, $score, $duration, json_encode($answers), $verified]);
    }
    
    public static function historyForUserSkill($userId, $skillId, $limit = 5) {
        $pdo = self::getPDO();
        $stmt = $pdo->prepare("SELECT * FROM skill_tests WHERE user_id = ? AND skill_id = ? ORDER BY attempt_date DESC LIMIT ?");
        $stmt->execute([$userId, $skillId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

