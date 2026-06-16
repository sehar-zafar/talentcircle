<?php

class ChatMessage {
    public $id;
    public $conversation_id;
    public $sender_user_id;
    public $body;
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

    public static function forConversation($conversationId, $limit = 200) {
        $pdo = self::getPDO();
        $stmt = $pdo->prepare(
            'SELECT * FROM chat_messages WHERE conversation_id = ? ORDER BY created_at ASC, id ASC LIMIT ?'
        );
        $stmt->bindValue(1, (int)$conversationId, PDO::PARAM_INT);
        $stmt->bindValue(2, (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $rows;
    }

    public static function create($conversationId, $senderUserId, $payload) {
        $pdo = self::getPDO();

        $type = isset($payload['type']) ? (string)$payload['type'] : 'text';
        $body = array_key_exists('body', $payload) ? (string)$payload['body'] : null;
        $stickerKey = isset($payload['sticker_key']) ? (string)$payload['sticker_key'] : null;
        $attachmentUrl = isset($payload['attachment_url']) ? (string)$payload['attachment_url'] : null;
        $metadata = isset($payload['metadata']) ? $payload['metadata'] : null;
        if (is_array($metadata)) $metadata = json_encode($metadata);

        $stmt = $pdo->prepare(
            'INSERT INTO chat_messages (conversation_id, sender_user_id, type, body, sticker_key, attachment_url, metadata) VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            (int)$conversationId,
            (int)$senderUserId,
            $type,
            $body !== null ? $body : null,
            $stickerKey !== null ? $stickerKey : null,
            $attachmentUrl !== null ? $attachmentUrl : null,
            $metadata !== null ? $metadata : null
        ]);

        $id = $pdo->lastInsertId();
        $stmt2 = $pdo->prepare('SELECT * FROM chat_messages WHERE id = ?');
        $stmt2->execute([(int)$id]);
        return $stmt2->fetch(PDO::FETCH_ASSOC);
    }
}

