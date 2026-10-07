<?php

class Device_model {
    private $table = 'devices';
    private $db;

    public function __construct()
    {
        $this->db = new Database;
    }

    public function getAllDevicesByUser($userId)
    {
        $this->db->query("SELECT d.*, u.username as creator_username, u.name as creator_name 
                          FROM " . $this->table . " d 
                          LEFT JOIN users u ON u.id = d.user_id 
                          WHERE d.user_id IN ($userId) 
                          ORDER BY d.id DESC");
        return $this->db->resultSet();
    }

    public function getDeviceById($id, $userId)
    {
        $this->db->query("SELECT * FROM " . $this->table . " WHERE id = :id AND user_id IN ($userId) LIMIT 1");
        $this->db->bind('id',      $id);
        return $this->db->single();
    }

    public function getDeviceBySession($sessionId)
    {
        $this->db->query("SELECT * FROM " . $this->table . " WHERE session_id = :session_id LIMIT 1");
        $this->db->bind('session_id', $sessionId);
        return $this->db->single();
    }

    public function addDevice($data)
    {
        $query = "INSERT INTO " . $this->table . " (user_id, name, session_id, phone, status, created_at)
                  VALUES (:user_id, :name, :session_id, :phone, :status, NOW())";
        $this->db->query($query);
        $this->db->bind('user_id',    $data['user_id']);
        $this->db->bind('name',       $data['name']);
        $this->db->bind('session_id', $data['session_id']);
        $this->db->bind('phone',      $data['phone']  ?? null);
        $this->db->bind('status',     $data['status'] ?? 'pending');

        $this->db->execute();
        return $this->db->rowCount();
    }

    public function updateStatus($sessionId, $status, $phone = null)
    {
        if ($phone !== null) {
            $this->db->query("UPDATE " . $this->table . " SET status = :status, phone = :phone WHERE session_id = :session_id");
            $this->db->bind('phone', $phone);
        } else {
            $this->db->query("UPDATE " . $this->table . " SET status = :status WHERE session_id = :session_id");
        }
        $this->db->bind('status',     $status);
        $this->db->bind('session_id', $sessionId);
        $this->db->execute();
        return $this->db->rowCount();
    }

    public function deleteDevice($id, $userId)
    {
        $this->db->query("DELETE FROM " . $this->table . " WHERE id = :id AND user_id IN ($userId)");
        $this->db->bind('id',      $id);
        $this->db->execute();
        return $this->db->rowCount();
    }

    public function updateName($id, $userId, $name)
    {
        $this->db->query("UPDATE " . $this->table . " SET name = :name WHERE id = :id AND user_id IN ($userId)");
        $this->db->bind('name',    $name);
        $this->db->bind('id',      $id);
        $this->db->execute();
        return $this->db->rowCount();
    }

    public function updateToken($id, $userId, $token)
    {
        $this->db->query("UPDATE " . $this->table . " SET token = :token WHERE id = :id AND user_id IN ($userId)");
        $this->db->bind('token',   $token);
        $this->db->bind('id',      $id);
        $this->db->execute();
        return $this->db->rowCount();
    }

    public function updateWebhookUrl($id, $userId, $webhookUrl)
    {
        $this->db->query("UPDATE " . $this->table . " SET webhook_url = :webhook_url WHERE id = :id AND user_id IN ($userId)");
        $this->db->bind('webhook_url', $webhookUrl);
        $this->db->bind('id',          $id);
        $this->db->execute();
        return $this->db->rowCount();
    }

    public function getDeviceByToken($token)
    {
        $this->db->query("SELECT * FROM " . $this->table . " WHERE token = :token LIMIT 1");
        $this->db->bind('token', $token);
        return $this->db->single();
    }
}
