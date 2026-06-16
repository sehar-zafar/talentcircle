<?php

<?php

class SkillExchangeRequest {

    public $id;
    public $requester_user_id;
    public $target_user_id;
    public $title;
    public $description;
    public $status;

    public $created_at;
    public $updated_at;

    public $accepted_at;
    public $rejected_at;
    public $started_at;
    public $completed_at;

    public $requester_cancelled_at;
    public $cancel_reason;

    public $client_request_key;

    private static $pdo;

    public function __construct() {}

    private static function getPDO() {
        if (!self::$pdo) {
            self::$pdo = new PDO('mysql:host=127.0.0.1;dbname=talentcircle', 'root', '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
        }
        return self::$pdo;
    }

    private static function mapRow($row) {
        if (!$row) return null;
        $obj = new self();
        foreach ($row as $k => $v) {
            if (property_exists($obj, $k)) {
                $obj->$k = $v;
            }
        }
        return $obj;
    }

    public static function create($data) {
        $pdo = self::getPDO();

        $requester = (int)($data['requester_user_id'] ?? 0);
        $target = (int)($data['target_user_id'] ?? 0);
        $title = (string)($data['title'] ?? '');
        $description = $data['description'] ?? null;
        $status = 'pending';
        $clientKey = isset($data['client_request_key']) && $data['client_request_key'] !== '' ? (string)$data['client_request_key'] : null;

        if ($requester <= 0 || $target <= 0) return null;
        if (trim($title) === '') return null;

        $stmt = $pdo->prepare(
            'INSERT INTO skill_exchange_requests (requester_user_id, target_user_id, title, description, status, client_request_key)
             VALUES (?, ?, ?, ?, ?, ?)'
        );

        $stmt->execute([
            $requester,
            $target,
            $title,
            $description,
            $status,
            $clientKey
        ]);

        return self::findById((int)$pdo->lastInsertId());
    }

    public static function findById($id) {
        $pdo = self::getPDO();
        $stmt = $pdo->prepare('SELECT * FROM skill_exchange_requests WHERE id = ? LIMIT 1');
        $stmt->execute([(int)$id]);
        return self::mapRow($stmt->fetch(PDO::FETCH_ASSOC));
    }

    public static function listForUser($userId, $tab = 'incoming', $status = null, $limit = 50) {
        $pdo = self::getPDO();
        $userId = (int)$userId;
        $limit = (int)$limit;
        if ($limit <= 0) $limit = 50;

        $where = 'WHERE 1=1';
        $params = [];

        if ($tab === 'incoming') {
            $where .= ' AND target_user_id = ?';
            $params[] = $userId;
        } else {
            $where .= ' AND requester_user_id = ?';
            $params[] = $userId;
        }

        if ($status !== null && $status !== '') {
            $where .= ' AND status = ?';
            $params[] = (string)$status;
        }

        $sql = 'SELECT * FROM skill_exchange_requests ' . $where . ' ORDER BY created_at DESC LIMIT ' . $limit;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $out = [];
        foreach ($rows as $r) {
            $out[] = self::mapRow($r);
        }
        return $out;
    }

    public static function toApi($obj) {
        if (!$obj) return null;
        return [
            'id' => (int)$obj->id,
            'requester_user_id' => (int)$obj->requester_user_id,
            'target_user_id' => (int)$obj->target_user_id,
            'title' => $obj->title,
            'description' => $obj->description,
            'status' => $obj->status,
            'created_at' => $obj->created_at,
            'updated_at' => $obj->updated_at,
            'accepted_at' => $obj->accepted_at,
            'rejected_at' => $obj->rejected_at,
            'started_at' => $obj->started_at,
            'completed_at' => $obj->completed_at,
        ];
    }

    private static function transition($requestId, $userId, $newStatus, $fields) {
        $pdo = self::getPDO();

        $requestId = (int)$requestId;
        $userId = (int)$userId;

        $stmt = $pdo->prepare('SELECT * FROM skill_exchange_requests WHERE id = ? LIMIT 1');
        $stmt->execute([$requestId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;

        // Auth/role rules are validated by controller; model just applies update atomically.
        $setParts = [];
        $params = [];
        foreach ($fields as $col => $val) {
            $setParts[] = "$col = ?";
            $params[] = $val;
        }

        $setParts[] = 'status = ?';
        $params[] = $newStatus;

        $setParts[] = 'updated_at = NOW()';

        $params[] = $requestId;

        $sql = 'UPDATE skill_exchange_requests SET ' . implode(', ', $setParts) . ' WHERE id = ?';
        $stmt2 = $pdo->prepare($sql);
        $stmt2->execute($params);

        return self::findById($requestId);
    }

    public static function acceptByTarget($requestId, $userId) {
        $pdo = self::getPDO();
        $stmt = $pdo->prepare('SELECT status, target_user_id FROM skill_exchange_requests WHERE id = ? LIMIT 1');
        $stmt->execute([(int)$requestId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;

        if ((int)$row['target_user_id'] !== (int)$userId) return null;
        if (($row['status'] ?? '') !== 'pending') return null;

        return self::transition($requestId, $userId, 'accepted', ['accepted_at' => date('Y-m-d H:i:s')]);
    }

    public static function rejectByTarget($requestId, $userId, $reason = null) {
        $pdo = self::getPDO();
        $stmt = $pdo->prepare('SELECT status, target_user_id FROM skill_exchange_requests WHERE id = ? LIMIT 1');
        $stmt->execute([(int)$requestId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;

        if ((int)$row['target_user_id'] !== (int)$userId) return null;
        if (($row['status'] ?? '') !== 'pending') return null;

        $fields = [
            'rejected_at' => date('Y-m-d H:i:s')
        ];
        if ($reason !== null && trim((string)$reason) !== '') {
            $fields['cancel_reason'] = (string)$reason;
        }

        return self::transition($requestId, $userId, 'rejected', $fields);
    }

    public static function cancelByRequester($requestId, $userId, $reason = null) {
        $pdo = self::getPDO();
        $stmt = $pdo->prepare('SELECT status, requester_user_id FROM skill_exchange_requests WHERE id = ? LIMIT 1');
        $stmt->execute([(int)$requestId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;

        if ((int)$row['requester_user_id'] !== (int)$userId) return null;
        if (!in_array((string)$row['status'], ['pending'], true)) return null;

        $fields = [
            'requester_cancelled_at' => date('Y-m-d H:i:s')
        ];
        if ($reason !== null && trim((string)$reason) !== '') {
            $fields['cancel_reason'] = (string)$reason;
        }

        // Map cancel to rejected-like state for UI simplicity
        return self::transition($requestId, $userId, 'rejected', $fields);
    }

    public static function startByTarget($requestId, $userId) {
        $pdo = self::getPDO();
        $stmt = $pdo->prepare('SELECT status, target_user_id FROM skill_exchange_requests WHERE id = ? LIMIT 1');
        $stmt->execute([(int)$requestId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;

        if ((int)$row['target_user_id'] !== (int)$userId) return null;
        if (($row['status'] ?? '') !== 'accepted') return null;

        return self::transition($requestId, $userId, 'ongoing', ['started_at' => date('Y-m-d H:i:s')]);
    }

    public static function completeByTarget($requestId, $userId) {
        $pdo = self::getPDO();
        $stmt = $pdo->prepare('SELECT status, target_user_id FROM skill_exchange_requests WHERE id = ? LIMIT 1');
        $stmt->execute([(int)$requestId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;

        if ((int)$row['target_user_id'] !== (int)$userId) return null;
        if (($row['status'] ?? '') !== 'ongoing') return null;

        return self::transition($requestId, $userId, 'completed', ['completed_at' => date('Y-m-d H:i:s')]);
    }
}

