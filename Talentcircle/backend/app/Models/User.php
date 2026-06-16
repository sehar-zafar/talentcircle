<?php

class User {
    public $id;
    public $name;
    public $email;
    public $password;
    public $skills_teach;
    public $skills_learn;
    public $bio;
    public $image;
    public $age;
    public $education;
    public $certificates;
    public $role;
    public $tokens = 0;
    public $last_login;
    public $google_id;
    public $phone;
    public $remember_token;

    private static $pdo;

    private static function getPDO() {
        if (!self::$pdo) {
            self::$pdo = new PDO('mysql:host=127.0.0.1;dbname=talentcircle', 'root', '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
        }
        return self::$pdo;
    }

    public static function findByEmail($email) {
        $pdo = self::getPDO();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($data) {
            $user = new self();
            foreach ($data as $key => $val) {
                $user->$key = $val;
            }
            return $user;
        }
        return null;
    }

    public static function findByPhone($phone) {
        $pdo = self::getPDO();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE phone = ?');
        $stmt->execute([$phone]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($data) {
            $user = new self();
            foreach ($data as $key => $val) {
                $user->$key = $val;
            }
            return $user;
        }
        return null;
    }

    public static function findByToken($token) {
        $pdo = self::getPDO();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE remember_token = ? OR id = ?');
        $stmt->execute([$token, $token]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($data) {
            $user = new self();
            foreach ($data as $key => $val) {
                $user->$key = $val;
            }
            return $user;
        }
        return null;
    }

    public function updateProfile($data) {
        $pdo = self::getPDO();
        $setParts = [];
        $params = [];
        foreach ($data as $key => $val) {
            if ($key === 'password') {
                $val = password_hash($val, PASSWORD_DEFAULT);
            } elseif (in_array($key, ['skills_teach', 'skills_learn', 'certificates'])) {
                $val = json_encode($val);
            }
            $setParts[] = "$key = ?";
            $params[] = $val;
        }
        $params[] = $this->id;
        $sql = 'UPDATE users SET ' . implode(', ', $setParts) . ' WHERE id = ?';
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public static function all() {
        $pdo = self::getPDO();
        $stmt = $pdo->query('SELECT * FROM users');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateTokens($amount) {
        $this->tokens += $amount;
        $pdo = self::getPDO();
        $stmt = $pdo->prepare('UPDATE users SET tokens = ?, last_login = CURDATE() WHERE id = ?');
        return $stmt->execute([$this->tokens, $this->id]);
    }
}

