<?php
class Skill {
    public $id;
    public $name;
    public $category;
    public $description;
    
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
        $stmt = $pdo->prepare('SELECT * FROM skills WHERE id = ?');
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($data) {
            $skill = new self();
            foreach ($data as $key => $val) $skill->$key = $val;
            return $skill;
        }
        return null;
    }
    
public static function all($category = null, $limit = 100) {
        $pdo = self::getPDO();
        $sql = 'SELECT * FROM skills';
        $params = [];
        if ($category) {
            $sql .= ' WHERE category = ?';
            $params[] = $category;
        }
        $sql .= ' ORDER BY name LIMIT ?';
        $params[] = $limit;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function search($query, $category = null) {
        $pdo = self::getPDO();
        $sql = 'SELECT * FROM skills WHERE name LIKE ? OR description LIKE ?';
        $params = ["%$query%", "%$query%"];
        if ($category) {
            $sql .= ' AND category = ?';
            $params[] = $category;
        }
        $sql .= ' ORDER BY name LIMIT 20';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public static function findByName($name) {
        $pdo = self::getPDO();
        $stmt = $pdo->prepare('SELECT * FROM skills WHERE name = ?');
        $stmt->execute([$name]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($data) {
            $skill = new self();
            foreach ($data as $key => $val) $skill->$key = $val;
            return $skill;
        }
        return null;
    }
}
?>

