<?php

class Setting_model {
    private $table = 'users';
    private $db;

    public function __construct()
    {
        $this->db = new Database;
    }

    public function getAllUsersForAdmin($userId, $role)
    {
        if ($role === 'super_admin') {
            $this->db->query("SELECT u.*, p.name as parent_name FROM " . $this->table . " u LEFT JOIN " . $this->table . " p ON u.parent_id = p.id ORDER BY u.id DESC");
            return $this->db->resultSet();
        } else if ($role === 'admin') {
            // Admin only sees users created by them
            $this->db->query("SELECT * FROM " . $this->table . " WHERE parent_id = :parent_id ORDER BY id DESC");
            $this->db->bind('parent_id', $userId);
            return $this->db->resultSet();
        }
        return [];
    }
    
    public function getAdminsForDropdown()
    {
        $this->db->query("SELECT id, name FROM " . $this->table . " WHERE role = 'admin' ORDER BY name ASC");
        return $this->db->resultSet();
    }

    public function getUserById($id, $viewerId, $viewerRole)
    {
        if ($viewerRole === 'super_admin') {
            $this->db->query("SELECT * FROM " . $this->table . " WHERE id = :id LIMIT 1");
            $this->db->bind('id', $id);
            return $this->db->single();
        } else if ($viewerRole === 'admin') {
            $this->db->query("SELECT * FROM " . $this->table . " WHERE id = :id AND parent_id = :parent_id LIMIT 1");
            $this->db->bind('id', $id);
            $this->db->bind('parent_id', $viewerId);
            return $this->db->single();
        }
        return false;
    }

    public function addUser($data)
    {
        $this->db->query("INSERT INTO " . $this->table . " (name, username, email, password, role, parent_id, status, created_at) 
                          VALUES (:name, :username, :email, :password, :role, :parent_id, :status, NOW())");
        $this->db->bind('name', $data['name']);
        $this->db->bind('username', $data['username']);
        $this->db->bind('email', $data['email']);
        $this->db->bind('password', password_hash($data['password'], PASSWORD_DEFAULT));
        $this->db->bind('role', $data['role']);
        $this->db->bind('parent_id', $data['parent_id']);
        $this->db->bind('status', $data['status']);

        $this->db->execute();
        return $this->db->rowCount();
    }

    public function updateUser($id, $data)
    {
        // Allow updating everything except password if empty
        if (!empty($data['password'])) {
            $this->db->query("UPDATE " . $this->table . " SET name=:name, username=:username, email=:email, password=:password, role=:role, parent_id=:parent_id, status=:status, updated_at=NOW() WHERE id=:id");
            $this->db->bind('password', password_hash($data['password'], PASSWORD_DEFAULT));
        } else {
            $this->db->query("UPDATE " . $this->table . " SET name=:name, username=:username, email=:email, role=:role, parent_id=:parent_id, status=:status, updated_at=NOW() WHERE id=:id");
        }
        
        $this->db->bind('name', $data['name']);
        $this->db->bind('username', $data['username']);
        $this->db->bind('email', $data['email']);
        $this->db->bind('role', $data['role']);
        $this->db->bind('parent_id', $data['parent_id']);
        $this->db->bind('status', $data['status']);
        $this->db->bind('id', $id);

        $this->db->execute();
        return $this->db->rowCount();
    }

    public function deleteUser($id)
    {
        // Proteksi tingkat database: pastikan user dengan role super_admin TIDAK PERNAH terhapus
        $this->db->query("DELETE FROM " . $this->table . " WHERE id = :id AND role != 'super_admin'");
        $this->db->bind('id', $id);
        $this->db->execute();
        return $this->db->rowCount();
    }

    public function updateProfile($userId, $data)
    {
        if (!empty($data['password'])) {
            $this->db->query("UPDATE " . $this->table . " SET name=:name, email=:email, password=:password, updated_at=NOW() WHERE id=:id");
            $this->db->bind('password', password_hash($data['password'], PASSWORD_DEFAULT));
        } else {
            $this->db->query("UPDATE " . $this->table . " SET name=:name, email=:email, updated_at=NOW() WHERE id=:id");
        }
        
        $this->db->bind('name', $data['name']);
        $this->db->bind('email', $data['email']);
        $this->db->bind('id', $userId);
        
        $this->db->execute();
        return $this->db->rowCount();
    }

    public function updateProfilePicture($userId, $filename)
    {
        $this->db->query("UPDATE " . $this->table . " SET profile_picture=:picture, updated_at=NOW() WHERE id=:id");
        $this->db->bind('picture', $filename);
        $this->db->bind('id', $userId);
        $this->db->execute();
        return $this->db->rowCount();
    }

    // ─── Global / App Settings (settings table) ──────────────────────────────
    public function getSetting($key, $default = '')
    {
        try {
            $this->db->query("SELECT `value` FROM settings WHERE `key` = :key LIMIT 1");
            $this->db->bind('key', $key);
            $res = $this->db->single();
            return $res ? $res['value'] : $default;
        } catch (Exception $e) {
            return $default;
        }
    }

    public function getAllSettings()
    {
        try {
            $this->db->query("SELECT `key`, `value` FROM settings");
            $rows = $this->db->resultSet();
            $settings = [];
            foreach ($rows as $r) {
                $settings[$r['key']] = $r['value'];
            }
            return $settings;
        } catch (Exception $e) {
            return [];
        }
    }

    public function updateSetting($key, $value)
    {
        try {
            $this->db->query("SELECT id FROM settings WHERE `key` = :key LIMIT 1");
            $this->db->bind('key', $key);
            $existing = $this->db->single();

            if ($existing) {
                $this->db->query("UPDATE settings SET `value` = :value, updated_at = NOW() WHERE `key` = :key");
                $this->db->bind('value', $value);
                $this->db->bind('key', $key);
                $this->db->execute();
            } else {
                $this->db->query("INSERT INTO settings (`key`, `value`, created_at, updated_at) VALUES (:key, :value, NOW(), NOW())");
                $this->db->bind('key', $key);
                $this->db->bind('value', $value);
                $this->db->execute();
            }
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}

