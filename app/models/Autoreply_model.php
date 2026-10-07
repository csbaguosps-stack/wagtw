<?php

class Autoreply_model {
    private $table = 'autoreplies';
    private $db;

    public function __construct()
    {
        $this->db = new Database;
    }

    public function getAllByUser($userId)
    {
        $this->db->query("SELECT ar.*, d.name as device_name FROM " . $this->table . " ar LEFT JOIN devices d ON d.id = ar.device_id WHERE ar.user_id IN ($userId) ORDER BY ar.id DESC");
        return $this->db->resultSet();
    }

    public function addAutoreply($data)
    {
        $query = "INSERT INTO " . $this->table . " 
                  (user_id, device_id, name, trigger_word, match_type, target_reply, reply_type, reply_text, delay, set_read, set_typing, active) 
                  VALUES 
                  (:user_id, :device_id, :name, :trigger_word, :match_type, :target_reply, :reply_type, :reply_text, :delay, :set_read, :set_typing, :active)";
        
        $this->db->query($query);
        $this->db->bind('user_id', $data['user_id']);
        $this->db->bind('device_id', $data['device_id']);
        $this->db->bind('name', $data['name']);
        $this->db->bind('trigger_word', $data['trigger_word']);
        $this->db->bind('match_type', $data['match_type']);
        $this->db->bind('target_reply', !empty($data['target_reply']) ? $data['target_reply'] : 'both');
        $this->db->bind('reply_type', $data['reply_type']);
        $this->db->bind('reply_text', $data['reply_text']);
        $this->db->bind('delay', $data['delay']);
        $this->db->bind('set_read', $data['set_read']);
        $this->db->bind('set_typing', $data['set_typing']);
        $this->db->bind('active', 1);

        $this->db->execute();
        return $this->db->rowCount();
    }

    public function deleteAutoreply($id, $userId)
    {
        $this->db->query("DELETE FROM " . $this->table . " WHERE id = :id AND user_id IN ($userId)");
        $this->db->bind('id', $id);
        $this->db->execute();
        return $this->db->rowCount();
    }
}
