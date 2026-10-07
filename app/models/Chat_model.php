<?php

class Chat_model {
    private $table = 'messages';
    private $db;

    public function __construct()
    {
        $this->db = new Database;
    }

    // Mengambil daftar nomor/kontak yang dichat oleh device (riwayat terakhir)
    public function getContactsByDevice($deviceId, $userId)
    {
        $sql = "SELECT 
                    CASE WHEN direction = 'in' THEN from_phone ELSE to_phone END AS phone_number,
                    MAX(created_at) AS last_message_time,
                    MAX(id) AS latest_id
                FROM " . $this->table . "
                WHERE device_id = :device_id AND user_id IN ($userId)
                GROUP BY phone_number
                ORDER BY last_message_time DESC";
                
        $this->db->query($sql);
        $this->db->bind('device_id', $deviceId);
        $contacts = $this->db->resultSet();
        
        // Ambil isi pesan terakhir 
        foreach($contacts as &$c) {
            $this->db->query("SELECT message, direction, status FROM " . $this->table . " WHERE id = :id");
            $this->db->bind('id', $c['latest_id']);
            $msg = $this->db->single();
            $c['last_message'] = $msg['message'] ?? '...';
            $c['direction'] = $msg['direction'] ?? '';
            $c['status'] = $msg['status'] ?? '';
            
            // Deteksi Group (biasanya ada tanda hubung '-' di ID nya karena JID format group)
            $c['is_group'] = strpos($c['phone_number'], '-') !== false;
        }
        
        return $contacts;
    }

    // Mengambil riwayat percakapan antara Device dan spesifik 1 nomor kontak
    public function getConversation($deviceId, $userId, $phone, $limit = 100)
    {
        $sql = "SELECT * FROM " . $this->table . " 
                WHERE device_id = :device_id AND user_id IN ($userId) 
                AND (from_phone = :phone OR to_phone = :phone)
                ORDER BY id DESC LIMIT :limit";
                
        $this->db->query($sql);
        $this->db->bind('device_id', $deviceId);
        $this->db->bind('phone', $phone);
        $this->db->bind('limit', $limit);

        return $this->db->resultSet();
    }
    
    public function clearChatByPhone($deviceId, $userId, $phone) 
    {
        $this->db->query("DELETE FROM " . $this->table . " WHERE device_id = :device_id AND user_id IN ($userId) AND (from_phone = :phone OR to_phone = :phone)");
        $this->db->bind('device_id', $deviceId);
        $this->db->bind('phone', $phone);
        $this->db->execute();
        return $this->db->rowCount();
    }

    // Ambil pesan baru saja (id > last_id) untuk incremental polling
    public function getNewMessages($deviceId, $userId, $phone, $lastId)
    {
        $sql = "SELECT * FROM " . $this->table . "
                WHERE device_id = :device_id AND user_id IN ($userId)
                AND id > :last_id
                AND (from_phone = :phone OR to_phone = :phone)
                ORDER BY id ASC
                LIMIT 50";

        $this->db->query($sql);
        $this->db->bind('device_id', $deviceId);
        $this->db->bind('phone', $phone);
        $this->db->bind('last_id', $lastId);
        return $this->db->resultSet();
    }
}
