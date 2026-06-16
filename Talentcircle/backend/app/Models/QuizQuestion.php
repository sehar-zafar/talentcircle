<?php
class QuizQuestion {
    public $id;
    public $skill_id;
    public $question;
    public $options; // decoded array
    public $correct_index;
    public $difficulty;

    private static $pdo;

    private static function getPDO() {
        if (!self::$pdo) {
            self::$pdo = new PDO('mysql:host=127.0.0.1;dbname=talentcircle', 'root', '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
        }
        return self::$pdo;
    }

    private static function decodeOptions($raw) {
        if (is_array($raw)) return $raw;
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    public static function randomForSkill($skillId, $limit = 10) {
        $pdo = self::getPDO();
        $stmt = $pdo->prepare(
            'SELECT * FROM quiz_questions WHERE skill_id = ? ORDER BY RAND() LIMIT ?'
        );
        $stmt->bindValue(1, (int)$skillId, PDO::PARAM_INT);
        $stmt->bindValue(2, (int)$limit, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'id' => (int)$r['id'],
                'skill_id' => (int)$r['skill_id'],
                'question' => $r['question'],
                'options' => self::decodeOptions($r['options']),
                'correct_index' => (int)$r['correct_index'],
                'difficulty' => $r['difficulty'],
            ];
        }
        return $out;
    }

    public static function allForSkill($skillId) {
        $pdo = self::getPDO();
        $stmt = $pdo->prepare('SELECT * FROM quiz_questions WHERE skill_id = ? ORDER BY id ASC');
        $stmt->execute([(int)$skillId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'id' => (int)$r['id'],
                'skill_id' => (int)$r['skill_id'],
                'question' => $r['question'],
                'options' => self::decodeOptions($r['options']),
                'correct_index' => (int)$r['correct_index'],
                'difficulty' => $r['difficulty'],
            ];
        }
        return $out;
    }
}
?>

