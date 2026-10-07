<?php

class AiServer_model {
    private $db;

    public function __construct()
    {
        $this->db = new Database;
    }

    // ─── AI Servers CRUD ──────────────────────────────────────────────────────

    public function getAllByUser($userId)
    {
        $this->db->query("SELECT * FROM ai_servers WHERE user_id IN ($userId) ORDER BY priority DESC, id ASC");
        return $this->db->resultSet();
    }

    public function getById($id, $userId)
    {
        $this->db->query("SELECT * FROM ai_servers WHERE id = :id AND user_id IN ($userId) LIMIT 1");
        $this->db->bind('id', $id);
        return $this->db->single();
    }

    public function getActiveServers($userId)
    {
        $this->db->query("SELECT * FROM ai_servers WHERE user_id IN ($userId) AND is_active = 1 ORDER BY priority DESC, id ASC");
        return $this->db->resultSet();
    }

    public function getActiveServersForUser()
    {
        // Mengambil seluruh server aktif yang didaftarkan Super Admin untuk dropdown pilihan user
        $this->db->query("SELECT id, label, model, is_active, priority FROM ai_servers WHERE is_active = 1 ORDER BY priority DESC, id ASC");
        return $this->db->resultSet();
    }

    public function addServer($data)
    {
        $this->db->query("INSERT INTO ai_servers (user_id, label, api_key, model, is_active, priority) VALUES (:user_id, :label, :api_key, :model, :is_active, :priority)");
        $this->db->bind('user_id',   $data['user_id']);
        $this->db->bind('label',     $data['label']);
        $this->db->bind('api_key',   $data['api_key']);
        $this->db->bind('model',     $data['model']);
        $this->db->bind('is_active', $data['is_active']);
        $this->db->bind('priority',  $data['priority'] ?? 0);
        $this->db->execute();
        return $this->db->rowCount();
    }

    public function updateServer($id, $userId, $data)
    {
        $this->db->query("UPDATE ai_servers SET label=:label, api_key=:api_key, model=:model, is_active=:is_active, priority=:priority WHERE id=:id AND user_id IN ($userId)");
        $this->db->bind('label',     $data['label']);
        $this->db->bind('api_key',   $data['api_key']);
        $this->db->bind('model',     $data['model']);
        $this->db->bind('is_active', $data['is_active']);
        $this->db->bind('priority',  $data['priority'] ?? 0);
        $this->db->bind('id',        $id);
        $this->db->execute();
        return $this->db->rowCount();
    }

    public function deleteServer($id, $userId)
    {
        $this->db->query("DELETE FROM ai_servers WHERE id=:id AND user_id IN ($userId)");
        $this->db->bind('id',      $id);
        $this->db->execute();
        return $this->db->rowCount();
    }

    public function toggleActive($id, $userId)
    {
        $this->db->query("UPDATE ai_servers SET is_active = IF(is_active=1, 0, 1) WHERE id=:id AND user_id IN ($userId)");
        $this->db->bind('id',      $id);
        $this->db->execute();
        return $this->db->rowCount();
    }

    // ─── Device AI Settings ───────────────────────────────────────────────────

    public function getDeviceSettings($deviceId, $userId)
    {
        $this->db->query("SELECT ads.*, ai.label as server_label, ai.model as server_model 
                          FROM ai_device_settings ads
                          LEFT JOIN ai_servers ai ON ai.id = ads.ai_server_id
                          WHERE ads.device_id = :device_id AND ads.user_id IN ($userId) LIMIT 1");
        $this->db->bind('device_id', $deviceId);
        return $this->db->single();
    }

    public function getAllDeviceSettings($userId)
    {
        $this->db->query("SELECT ads.*, d.name as device_name, d.session_id, d.status as device_status,
                          ai.label as server_label, ai.model as server_model
                          FROM ai_device_settings ads
                          LEFT JOIN devices d ON d.id = ads.device_id
                          LEFT JOIN ai_servers ai ON ai.id = ads.ai_server_id
                          WHERE ads.user_id IN ($userId)");
        return $this->db->resultSet();
    }

    public function upsertDeviceSettings($data)
    {
        $this->db->query("INSERT INTO ai_device_settings (user_id, device_id, ai_enabled, ai_server_id, ai_rules, reply_mode)
                          VALUES (:user_id, :device_id, :ai_enabled, :ai_server_id, :ai_rules, :reply_mode)
                          ON DUPLICATE KEY UPDATE
                            ai_enabled   = VALUES(ai_enabled),
                            ai_server_id = VALUES(ai_server_id),
                            ai_rules     = VALUES(ai_rules),
                            reply_mode   = VALUES(reply_mode)");
        $this->db->bind('user_id',      $data['user_id']);
        $this->db->bind('device_id',    $data['device_id']);
        $this->db->bind('ai_enabled',   $data['ai_enabled']);
        $this->db->bind('ai_server_id', $data['ai_server_id']);
        $this->db->bind('ai_rules',     $data['ai_rules']);
        $this->db->bind('reply_mode',   $data['reply_mode']);
        $this->db->execute();
        return $this->db->rowCount();
    }
    // ─── AI Models CRUD ───────────────────────────────────────────────────────

    public function getAllModelsByUser($userId)
    {
        $this->db->query("SELECT * FROM ai_models WHERE user_id IN ($userId) ORDER BY priority DESC, id ASC");
        return $this->db->resultSet();
    }

    public function getActiveModels($userId)
    {
        $this->db->query("SELECT * FROM ai_models WHERE user_id IN ($userId) AND is_active = 1 ORDER BY priority DESC, id ASC");
        return $this->db->resultSet();
    }

    public function getActiveModelsGlobal()
    {
        // Mengambil seluruh model aktif yang didaftarkan Super Admin untuk digunakan oleh semua user
        $this->db->query("SELECT id, label, model_name, is_active, priority FROM ai_models WHERE is_active = 1 ORDER BY priority DESC, id ASC");
        return $this->db->resultSet();
    }

    public function getModelById($id, $userId)
    {
        $this->db->query("SELECT * FROM ai_models WHERE id = :id AND user_id IN ($userId) LIMIT 1");
        $this->db->bind('id', $id);
        return $this->db->single();
    }

    public function addModel($data)
    {
        $this->db->query("INSERT INTO ai_models (user_id, label, model_name, is_active, priority) VALUES (:user_id, :label, :model_name, :is_active, :priority)");
        $this->db->bind('user_id',    $data['user_id']);
        $this->db->bind('label',      $data['label']);
        $this->db->bind('model_name', $data['model_name']);
        $this->db->bind('is_active',  $data['is_active']);
        $this->db->bind('priority',   $data['priority'] ?? 0);
        $this->db->execute();
        return $this->db->rowCount();
    }

    public function updateModel($id, $userId, $data)
    {
        $this->db->query("UPDATE ai_models SET label=:label, model_name=:model_name, is_active=:is_active, priority=:priority WHERE id=:id AND user_id IN ($userId)");
        $this->db->bind('label',      $data['label']);
        $this->db->bind('model_name', $data['model_name']);
        $this->db->bind('is_active',  $data['is_active']);
        $this->db->bind('priority',   $data['priority'] ?? 0);
        $this->db->bind('id',         $id);
        $this->db->execute();
        return $this->db->rowCount();
    }

    public function deleteModel($id, $userId)
    {
        $this->db->query("DELETE FROM ai_models WHERE id=:id AND user_id IN ($userId)");
        $this->db->bind('id',      $id);
        $this->db->execute();
        return $this->db->rowCount();
    }

    public function toggleActiveModel($id, $userId)
    {
        $this->db->query("UPDATE ai_models SET is_active = IF(is_active=1, 0, 1) WHERE id=:id AND user_id IN ($userId)");
        $this->db->bind('id',      $id);
        $this->db->execute();
        return $this->db->rowCount();
    }

    // ─── AI Search Engines CRUD ───────────────────────────────────────────────

    public function getAllSearchEnginesByUser($userId)
    {
        $this->db->query("SELECT * FROM ai_search_engines WHERE user_id IN ($userId) ORDER BY priority DESC, id ASC");
        return $this->db->resultSet();
    }

    public function getSearchEngineById($id, $userId)
    {
        $this->db->query("SELECT * FROM ai_search_engines WHERE id = :id AND user_id IN ($userId) LIMIT 1");
        $this->db->bind('id', $id);
        return $this->db->single();
    }

    public function addSearchEngine($data)
    {
        $this->db->query("INSERT INTO ai_search_engines (user_id, provider, label, api_key, is_active, priority) VALUES (:user_id, :provider, :label, :api_key, :is_active, :priority)");
        $this->db->bind('user_id',   $data['user_id']);
        $this->db->bind('provider',  $data['provider']);
        $this->db->bind('label',     $data['label']);
        $this->db->bind('api_key',   $data['api_key']);
        $this->db->bind('is_active', $data['is_active']);
        $this->db->bind('priority',  $data['priority'] ?? 0);
        $this->db->execute();
        return $this->db->rowCount();
    }

    public function updateSearchEngine($id, $userId, $data)
    {
        $this->db->query("UPDATE ai_search_engines SET provider=:provider, label=:label, api_key=:api_key, is_active=:is_active, priority=:priority WHERE id=:id AND user_id IN ($userId)");
        $this->db->bind('provider',  $data['provider']);
        $this->db->bind('label',     $data['label']);
        $this->db->bind('api_key',   $data['api_key']);
        $this->db->bind('is_active', $data['is_active']);
        $this->db->bind('priority',  $data['priority'] ?? 0);
        $this->db->bind('id',        $id);
        $this->db->execute();
        return $this->db->rowCount();
    }

    public function deleteSearchEngine($id, $userId)
    {
        $this->db->query("DELETE FROM ai_search_engines WHERE id=:id AND user_id IN ($userId)");
        $this->db->bind('id',      $id);
        $this->db->execute();
        return $this->db->rowCount();
    }

    public function toggleActiveSearchEngine($id, $userId)
    {
        $this->db->query("UPDATE ai_search_engines SET is_active = IF(is_active=1, 0, 1) WHERE id=:id AND user_id IN ($userId)");
        $this->db->bind('id',      $id);
        $this->db->execute();
        return $this->db->rowCount();
    }

    public function incrementSearchPull($provider, $userId)
    {
        $this->db->query("UPDATE ai_search_engines SET pull_count = pull_count + 1 WHERE provider=:provider AND user_id IN ($userId)");
        $this->db->bind('provider', $provider);
        $this->db->execute();
        return $this->db->rowCount();
    }

    public function resetSearchStats($userId)
    {
        $this->db->query("UPDATE ai_search_engines SET pull_count=0, reset_at=NOW() WHERE user_id IN ($userId)");
        $this->db->execute();
        return $this->db->rowCount();
    }
}
