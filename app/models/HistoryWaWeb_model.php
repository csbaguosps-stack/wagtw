<?php
/**
 * WAGTW - History WA Web Model
 * Handles conversation aggregation, message histories, and status updates.
 */

class HistoryWaWeb_model {
    private $db;

    public function __construct()
    {
        $this->db = new Database;
    }

    /**
     * Ambil semua device WhatsApp milik user
     */
    public function getDevices($userId)
    {
        $this->db->query("SELECT id, name, phone, session_id, status FROM devices WHERE user_id IN ($userId) ORDER BY id ASC");
        return $this->db->resultSet();
    }

    /**
     * Ambil detail 1 device
     */
    public function getDeviceById($deviceId, $userId)
    {
        $this->db->query("SELECT * FROM devices WHERE id = :id AND user_id IN ($userId) LIMIT 1");
        $this->db->bind('id', $deviceId);
        return $this->db->single();
    }

    /**
     * Ambil daftar percakapan (distinct kontak) beserta pesan terakhirnya, diurutkan dari aktivitas terbaru
     */
    public function getConversations($userId, $deviceId = 0, $search = '')
    {
        $sql = "SELECT 
                    sub.device_id,
                    sub.contact_phone,
                    sub.last_activity,
                    sub.unread_count,
                    sub.total_messages,
                    m.id AS last_msg_id,
                    m.message AS last_message,
                    m.direction AS last_direction,
                    m.status AS last_status,
                    m.msg_type AS last_msg_type,
                    m.created_at AS last_created_at,
                    d.name AS device_name,
                    d.phone AS device_phone,
                    d.session_id,
                    d.status AS device_status
                FROM (
                    SELECT 
                        device_id,
                        CASE WHEN direction = 'in' THEN from_phone ELSE to_phone END AS contact_phone,
                        MAX(id) AS latest_msg_id,
                        MAX(created_at) AS last_activity,
                        SUM(CASE WHEN direction = 'in' AND status != 'read' THEN 1 ELSE 0 END) AS unread_count,
                        COUNT(id) AS total_messages
                    FROM messages
                    WHERE user_id IN ($userId) AND is_deleted = 0";

        if ($deviceId > 0) {
            $sql .= " AND device_id = :device_id";
        }

        if (!empty($search)) {
            $sql .= " AND (from_phone LIKE :search OR to_phone LIKE :search OR message LIKE :search)";
        }

        $sql .= " GROUP BY device_id, CASE WHEN direction = 'in' THEN from_phone ELSE to_phone END
                ) sub
                JOIN messages m ON m.id = sub.latest_msg_id
                LEFT JOIN devices d ON d.id = sub.device_id
                ORDER BY sub.last_activity DESC
                LIMIT 150";

        $this->db->query($sql);
        if ($deviceId > 0) {
            $this->db->bind('device_id', $deviceId);
        }
        if (!empty($search)) {
            $this->db->bind('search', '%' . $search . '%');
        }

        return $this->db->resultSet();
    }

    /**
     * Ambil histori pesan lengkap antara device dan suatu kontak
     */
    public function getMessages($userId, $deviceId, $contactPhone, $limit = 200, $afterId = 0)
    {
        $sql = "SELECT 
                    m.*,
                    d.name AS device_name,
                    d.phone AS device_phone
                FROM messages m
                LEFT JOIN devices d ON d.id = m.device_id
                WHERE m.user_id IN ($userId)
                  AND m.device_id = :device_id
                  AND m.is_deleted = 0
                  AND (
                      (m.direction = 'in' AND m.from_phone = :contact_in)
                      OR
                      (m.direction = 'out' AND m.to_phone = :contact_out)
                  )";

        if ($afterId > 0) {
            $sql .= " AND m.id > :after_id ORDER BY m.id ASC";
        } else {
            $sql .= " ORDER BY m.id DESC LIMIT :limit";
        }

        $this->db->query($sql);
        $this->db->bind('device_id', $deviceId);
        $this->db->bind('contact_in', $contactPhone);
        $this->db->bind('contact_out', $contactPhone);
        if ($afterId > 0) {
            $this->db->bind('after_id', $afterId);
        } else {
            $this->db->bind('limit', (int)$limit, PDO::PARAM_INT);
        }

        $results = $this->db->resultSet();
        if ($afterId === 0) {
            $results = array_reverse($results);
        }
        return $results;
    }

    /**
     * Tandai pesan masuk dari kontak ini sebagai terbaca
     */
    public function markAsRead($userId, $deviceId, $contactPhone)
    {
        $this->db->query("UPDATE messages 
                          SET status = 'read' 
                          WHERE user_id IN ($userId) 
                            AND device_id = :device_id 
                            AND direction = 'in' 
                            AND from_phone = :contact 
                            AND status != 'read'");
        $this->db->bind('device_id', $deviceId);
        $this->db->bind('contact', $contactPhone);
        $this->db->execute();
        return $this->db->rowCount();
    }

    /**
     * Hapus seluruh percakapan dengan kontak tertentu
     */
    public function deleteConversation($userId, $deviceId, $contactPhone)
    {
        $this->db->query("DELETE FROM messages 
                          WHERE user_id IN ($userId) 
                            AND device_id = :device_id 
                            AND (
                                (direction = 'in' AND from_phone = :contact_in)
                                OR
                                (direction = 'out' AND to_phone = :contact_out)
                            )");
        $this->db->bind('device_id', $deviceId);
        $this->db->bind('contact_in', $contactPhone);
        $this->db->bind('contact_out', $contactPhone);
        $this->db->execute();
        return $this->db->rowCount();
    }

    /**
     * Hapus satu pesan spesifik
     */
    public function deleteMessage($id, $userId)
    {
        $this->db->query("DELETE FROM messages WHERE id = :id AND user_id IN ($userId)");
        $this->db->bind('id', $id);
        $this->db->execute();
        return $this->db->rowCount();
    }
}
