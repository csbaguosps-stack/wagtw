<?php

class MessageHistory_model {
    private $table = 'messages';
    private $db;

    public function __construct()
    {
        $this->db = new Database;
    }

    // Server-Side Processing untuk DataTables
    public function getDatatables($userId, $postData)
    {
        $columns = ['m.id', 'm.created_at', 'd.phone', 'm.to_phone', 'm.from_phone', 'm.msg_type', 'm.message', 'm.status', 'm.direction'];
        
        $sql = "SELECT m.*, d.phone as device_phone 
                FROM {$this->table} m
                LEFT JOIN devices d ON m.device_id = d.id
                WHERE m.user_id IN ($userId)";

        // Filter by device
        if (!empty($postData['device_id'])) {
            $sql .= " AND m.device_id = :device_id";
        }

        // Pencarian global
        if (!empty($postData['search']['value'])) {
            $searchValue = $postData['search']['value'];
            $sql .= " AND (m.message LIKE :search OR m.to_phone LIKE :search OR m.from_phone LIKE :search OR d.phone LIKE :search)";
        }

        // Urutan
        if (isset($postData['order'])) {
            $orderColumnIndex = $postData['order'][0]['column'];
            // Hindari SQL injection pada nama kolom (mapping manual)
            $orderMap = [
                1 => 'm.id',
                2 => 'm.created_at',
                3 => 'd.phone',
                4 => 'm.to_phone', // Target (asumsi)
                6 => 'm.msg_type',
                7 => 'm.message',
                8 => 'm.status',
                9 => 'm.direction'
            ];
            
            $orderBy = $orderMap[$orderColumnIndex] ?? 'm.id';
            $orderDir = $postData['order'][0]['dir'] === 'asc' ? 'ASC' : 'DESC';
            $sql .= " ORDER BY {$orderBy} {$orderDir}";
        } else {
            $sql .= " ORDER BY m.id DESC";
        }

        // Pagination
        if ($postData['length'] != -1) {
            $start = intval($postData['start']);
            $length = intval($postData['length']);
            $sql .= " LIMIT {$start}, {$length}";
        }

        $this->db->query($sql);
        if (!empty($postData['device_id'])) {
            $this->db->bind('device_id', $postData['device_id']);
        }
        if (!empty($postData['search']['value'])) {
            $this->db->bind('search', "%{$searchValue}%");
        }

        return $this->db->resultSet();
    }

    public function countFiltered($userId, $postData)
    {
        $sql = "SELECT COUNT(m.id) as total 
                FROM {$this->table} m
                LEFT JOIN devices d ON m.device_id = d.id
                WHERE m.user_id IN ($userId)";

        if (!empty($postData['device_id'])) {
            $sql .= " AND m.device_id = :device_id";
        }

        if (!empty($postData['search']['value'])) {
            $searchValue = $postData['search']['value'];
            $sql .= " AND (m.message LIKE :search OR m.to_phone LIKE :search OR m.from_phone LIKE :search OR d.phone LIKE :search)";
        }

        $this->db->query($sql);
        if (!empty($postData['device_id'])) {
            $this->db->bind('device_id', $postData['device_id']);
        }
        if (!empty($postData['search']['value'])) {
            $this->db->bind('search', "%{$searchValue}%");
        }
        
        return $this->db->single()['total'];
    }

    public function countAll($userId)
    {
        $this->db->query("SELECT COUNT(id) as total FROM {$this->table} WHERE user_id IN ($userId)");
        return $this->db->single()['total'];
    }

    public function deleteMessage($id, $userId)
    {
        $this->db->query("DELETE FROM {$this->table} WHERE id = :id AND user_id IN ($userId)");
        $this->db->bind('id', $id);
        $this->db->execute();
        return $this->db->rowCount();
    }

    public function bulkDelete($ids, $userId)
    {
        if (empty($ids)) return 0;
        $inQuery = implode(',', array_fill(0, count($ids), '?'));
        // Database class di MVC ini biasanya pake PDO ber-nama parameter. 
        // Karena WAGTW `Database` class punya bind khusus, mending pake loop.
        $deleted = 0;
        foreach($ids as $id) {
            $this->db->query("DELETE FROM {$this->table} WHERE id = :id AND user_id IN ($userId)");
            $this->db->bind('id', $id);
            $this->db->execute();
            $deleted += $this->db->rowCount();
        }
        return $deleted;
    }

    public function deleteAll($userId)
    {
        $this->db->query("DELETE FROM {$this->table} WHERE user_id IN ($userId)");
        $this->db->execute();
        return $this->db->rowCount();
    }
    
    public function getMessageById($id, $userId)
    {
        $this->db->query("SELECT m.*, d.session_id FROM {$this->table} m LEFT JOIN devices d ON m.device_id = d.id WHERE m.id = :id AND m.user_id IN ($userId)");
        $this->db->bind('id', $id);
        return $this->db->single();
    }
}
