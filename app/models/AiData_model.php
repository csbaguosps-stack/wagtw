<?php

class AiData_model {
    private $db;

    public function __construct()
    {
        $this->db = new Database;
    }

    // ─── AI Data (Knowledge Base) ─────────────────────────────────────────────

    public function getByDevice($deviceId, $userId)
    {
        $this->db->query("SELECT * FROM device_ai_data WHERE device_id = :device_id AND user_id IN ($userId) ORDER BY id DESC");
        $this->db->bind('device_id', $deviceId);
        return $this->db->resultSet();
    }

    public function getById($id, $deviceId, $userId)
    {
        $this->db->query("SELECT * FROM device_ai_data WHERE id = :id AND device_id = :device_id AND user_id IN ($userId) LIMIT 1");
        $this->db->bind('id',        $id);
        $this->db->bind('device_id', $deviceId);
        return $this->db->single();
    }

    public function add($data)
    {
        $this->db->query("INSERT INTO device_ai_data (device_id, user_id, title, content, created_at, updated_at)
                          VALUES (:device_id, :user_id, :title, :content, NOW(), NOW())");
        $this->db->bind('device_id', $data['device_id']);
        $this->db->bind('user_id',   $data['user_id']);
        $this->db->bind('title',     $data['title']);
        $this->db->bind('content',   $data['content']);
        $this->db->execute();
        return $this->db->rowCount();
    }

    public function update($id, $deviceId, $userId, $data)
    {
        $this->db->query("UPDATE device_ai_data SET title = :title, content = :content, updated_at = NOW()
                          WHERE id = :id AND device_id = :device_id AND user_id IN ($userId)");
        $this->db->bind('title',     $data['title']);
        $this->db->bind('content',   $data['content']);
        $this->db->bind('id',        $id);
        $this->db->bind('device_id', $deviceId);
        $this->db->execute();
        return $this->db->rowCount();
    }

    public function delete($id, $deviceId, $userId)
    {
        $this->db->query("DELETE FROM device_ai_data WHERE id = :id AND device_id = :device_id AND user_id IN ($userId)");
        $this->db->bind('id',        $id);
        $this->db->bind('device_id', $deviceId);
        $this->db->execute();
        return $this->db->rowCount();
    }

    public function countByDevice($deviceId, $userId)
    {
        $this->db->query("SELECT COUNT(*) as total FROM device_ai_data WHERE device_id = :device_id AND user_id IN ($userId)");
        $this->db->bind('device_id', $deviceId);
        $row = $this->db->single();
        return $row ? intval($row['total']) : 0;
    }

    // ─── Device AI Settings ───────────────────────────────────────────────────

    public function getSettings($deviceId, $userId)
    {
        $this->db->query("SELECT * FROM device_ai_settings WHERE device_id = :device_id AND user_id IN ($userId) LIMIT 1");
        $this->db->bind('device_id', $deviceId);
        return $this->db->single();
    }

    public function upsertSettings($data)
    {
        $this->db->query("INSERT INTO device_ai_settings
                            (device_id, user_id, ai_enabled, activated_by_user_id, activated_at, search_enabled, target_reply, ai_server_id, model, style, brand, ai_name, language, rules, reply_mode, created_at, updated_at)
                          VALUES
                            (:device_id, :user_id, :ai_enabled, :activated_by_user_id, :activated_at, :search_enabled, :target_reply, :ai_server_id, :model, :style, :brand, :ai_name, :language, :rules, :reply_mode, NOW(), NOW())
                          ON DUPLICATE KEY UPDATE
                            ai_enabled            = VALUES(ai_enabled),
                            activated_by_user_id  = VALUES(activated_by_user_id),
                            activated_at          = IF(VALUES(ai_enabled) = 1 AND ai_enabled = 0, NOW(), activated_at),
                            search_enabled        = VALUES(search_enabled),
                            target_reply          = VALUES(target_reply),
                            ai_server_id          = VALUES(ai_server_id),
                            model                 = VALUES(model),
                            style                 = VALUES(style),
                            brand                 = VALUES(brand),
                            ai_name               = VALUES(ai_name),
                            language              = VALUES(language),
                            rules                 = VALUES(rules),
                            reply_mode            = VALUES(reply_mode),
                            updated_at            = NOW()");
        $this->db->bind('device_id',           $data['device_id']);
        $this->db->bind('user_id',             $data['user_id']);
        $this->db->bind('ai_enabled',          $data['ai_enabled']);
        $this->db->bind('activated_by_user_id',$data['activated_by_user_id']);
        $this->db->bind('activated_at',        $data['ai_enabled'] ? date('Y-m-d H:i:s') : null);
        $this->db->bind('search_enabled',      $data['search_enabled']);
        $this->db->bind('target_reply',        $data['target_reply']);
        $this->db->bind('ai_server_id',        $data['ai_server_id']);
        $this->db->bind('model',               $data['model']);
        $this->db->bind('style',               $data['style']);
        $this->db->bind('brand',               $data['brand']);
        $this->db->bind('ai_name',             $data['ai_name']);
        $this->db->bind('language',            $data['language']);
        $this->db->bind('rules',               $data['rules']);
        $this->db->bind('reply_mode',          $data['reply_mode']);
        $this->db->execute();
        return $this->db->rowCount();
    }

    // ─── Monitoring: ambil semua device yang AI-nya aktif (untuk super admin) ──

    public function getActiveAiByAllUsers()
    {
        $this->db->query("
            SELECT
                das.*,
                d.name        AS device_name,
                d.session_id,
                d.status      AS device_status,
                u.username    AS owner_username,
                act.username  AS activated_by_username,
                ai.label      AS server_label,
                ai.model      AS server_model
            FROM device_ai_settings das
            LEFT JOIN devices  d   ON d.id   = das.device_id
            LEFT JOIN users    u   ON u.id   = das.user_id
            LEFT JOIN users    act ON act.id = das.activated_by_user_id
            LEFT JOIN ai_servers ai ON ai.id = das.ai_server_id
            WHERE das.ai_enabled = 1
            ORDER BY das.updated_at DESC
        ");
        return $this->db->resultSet();
    }

    public function deactivateDeviceAi($deviceId)
    {
        $this->db->query("UPDATE device_ai_settings 
                          SET ai_enabled = 0, 
                              activated_by_user_id = NULL, 
                              activated_at = NULL, 
                              updated_at = NOW() 
                          WHERE device_id = :device_id");
        $this->db->bind('device_id', $deviceId);
        $this->db->execute();
        return $this->db->rowCount();
    }

    // ─── Template AI WA Methods ──────────────────────────────────────────────

    public static function getDefaultTemplates()
    {
        return [
            'ai_footer_enabled'      => 1,
            'ai_footer_text'         => "────────────────────────\n💬 *Butuh bantuan staf / Admin?*\nKetik *Chat Admin* untuk berbicara langsung dengan tim kami.",
            'ai_handoff_text'        => "Baik, permintaan Anda telah kami teruskan ke *Admin / Staf kami* 👤\n\nAdmin kami akan segera membaca dan merespons chat Anda secara langsung. Sementara ini, Bot AI dinonaktifkan untuk percakapan ini dan akan aktif kembali besok hari.\n\nTerima kasih atas kesabaran Anda! 🙏",
            'ai_reactivate_duration' => 'next_day',
            'ai_reactivate_keyword'  => 'akhiri percakapan, selesai, #selesai, tutup sesi, end chat',
            'ai_reactivate_message'  => "Sesi percakapan dengan Admin telah selesai. Bot AI kami kini aktif kembali untuk melayani Anda. Silakan tanyakan apa saja yang Anda butuhkan! 🤖",
            'ai_reactivate_notify'   => 1
        ];
    }

    public function getTemplateSettings($deviceId, $userId)
    {
        $settings = $this->getSettings($deviceId, $userId);
        $defaults = self::getDefaultTemplates();

        if (!$settings) {
            return array_merge(['device_id' => $deviceId], $defaults);
        }

        return [
            'device_id'              => $deviceId,
            'ai_footer_enabled'      => isset($settings['ai_footer_enabled']) && $settings['ai_footer_enabled'] !== null ? intval($settings['ai_footer_enabled']) : $defaults['ai_footer_enabled'],
            'ai_footer_text'         => !empty($settings['ai_footer_text']) ? $settings['ai_footer_text'] : $defaults['ai_footer_text'],
            'ai_handoff_text'        => !empty($settings['ai_handoff_text']) ? $settings['ai_handoff_text'] : $defaults['ai_handoff_text'],
            'ai_reactivate_duration' => !empty($settings['ai_reactivate_duration']) ? $settings['ai_reactivate_duration'] : $defaults['ai_reactivate_duration'],
            'ai_reactivate_keyword'  => !empty($settings['ai_reactivate_keyword']) ? $settings['ai_reactivate_keyword'] : $defaults['ai_reactivate_keyword'],
            'ai_reactivate_message'  => isset($settings['ai_reactivate_message']) && $settings['ai_reactivate_message'] !== null ? $settings['ai_reactivate_message'] : $defaults['ai_reactivate_message'],
            'ai_reactivate_notify'   => isset($settings['ai_reactivate_notify']) && $settings['ai_reactivate_notify'] !== null ? intval($settings['ai_reactivate_notify']) : $defaults['ai_reactivate_notify'],
        ];
    }

    public function saveTemplateSettings($deviceId, $userId, $data)
    {
        $this->db->query("INSERT INTO device_ai_settings 
                            (device_id, user_id, ai_footer_enabled, ai_footer_text, ai_handoff_text, ai_reactivate_duration, ai_reactivate_keyword, ai_reactivate_message, ai_reactivate_notify, created_at, updated_at)
                          VALUES
                            (:device_id, :user_id, :ai_footer_enabled, :ai_footer_text, :ai_handoff_text, :ai_reactivate_duration, :ai_reactivate_keyword, :ai_reactivate_message, :ai_reactivate_notify, NOW(), NOW())
                          ON DUPLICATE KEY UPDATE
                            ai_footer_enabled      = VALUES(ai_footer_enabled),
                            ai_footer_text         = VALUES(ai_footer_text),
                            ai_handoff_text        = VALUES(ai_handoff_text),
                            ai_reactivate_duration = VALUES(ai_reactivate_duration),
                            ai_reactivate_keyword  = VALUES(ai_reactivate_keyword),
                            ai_reactivate_message  = VALUES(ai_reactivate_message),
                            ai_reactivate_notify   = VALUES(ai_reactivate_notify),
                            updated_at             = NOW()");
        $this->db->bind('device_id',              $deviceId);
        $this->db->bind('user_id',                $userId);
        $this->db->bind('ai_footer_enabled',      $data['ai_footer_enabled']);
        $this->db->bind('ai_footer_text',         $data['ai_footer_text']);
        $this->db->bind('ai_handoff_text',        $data['ai_handoff_text']);
        $this->db->bind('ai_reactivate_duration', $data['ai_reactivate_duration']);
        $this->db->bind('ai_reactivate_keyword',  $data['ai_reactivate_keyword']);
        $this->db->bind('ai_reactivate_message',  $data['ai_reactivate_message']);
        $this->db->bind('ai_reactivate_notify',   $data['ai_reactivate_notify']);
        $this->db->execute();
        return true;
    }

    public function applyTemplateToAllDevices($userIdStr, $actualUserId, $data)
    {
        // Ambil semua device milik user
        $this->db->query("SELECT id FROM devices WHERE user_id IN ($userIdStr)");
        $devices = $this->db->resultSet();
        if (empty($devices)) return 0;

        $count = 0;
        foreach ($devices as $d) {
            $this->saveTemplateSettings($d['id'], $actualUserId, $data);
            $count++;
        }
        return $count;
    }

    public function getMutedContactsByDevice($deviceId, $month = null, $year = null)
    {
        $query = "SELECT * FROM ai_muted_contacts WHERE device_id = :device_id";
        if (!empty($month) && !empty($year) && $month !== 'all') {
            $query .= " AND (MONTH(created_at) = :month AND YEAR(created_at) = :year)";
        } elseif (!empty($year)) {
            $query .= " AND YEAR(created_at) = :year";
        }
        $query .= " ORDER BY id DESC LIMIT 200";

        $this->db->query($query);
        $this->db->bind('device_id', $deviceId);
        if (!empty($month) && !empty($year) && $month !== 'all') {
            $this->db->bind('month', intval($month));
            $this->db->bind('year', intval($year));
        } elseif (!empty($year)) {
            $this->db->bind('year', intval($year));
        }
        return $this->db->resultSet();
    }

    public function unmuteContactById($id, $deviceId)
    {
        $this->db->query("SELECT phone FROM ai_muted_contacts WHERE id = :id AND device_id = :device_id LIMIT 1");
        $this->db->bind('id', $id);
        $this->db->bind('device_id', $deviceId);
        $row = $this->db->single();
        if ($row && !empty($row['phone'])) {
            $this->db->query("DELETE FROM ai_muted_contacts WHERE phone = :phone AND device_id = :device_id");
            $this->db->bind('phone', $row['phone']);
            $this->db->bind('device_id', $deviceId);
            $this->db->execute();
            return $this->db->rowCount();
        }

        $this->db->query("DELETE FROM ai_muted_contacts WHERE id = :id AND device_id = :device_id");
        $this->db->bind('id', $id);
        $this->db->bind('device_id', $deviceId);
        $this->db->execute();
        return $this->db->rowCount();
    }

    public function deleteMutedBulk($ids, $deviceId)
    {
        if (empty($ids) || !is_array($ids)) return 0;
        $cleanIds = array_map('intval', array_filter($ids));
        if (empty($cleanIds)) return 0;

        $idList = implode(',', $cleanIds);

        // Cari nomor telepon dari ID-ID ini untuk membersihkan record berulang
        $this->db->query("SELECT DISTINCT phone FROM ai_muted_contacts WHERE id IN ($idList) AND device_id = :device_id");
        $this->db->bind('device_id', $deviceId);
        $rows = $this->db->resultSet();

        $phones = [];
        if (!empty($rows)) {
            foreach ($rows as $r) {
                if (!empty($r['phone'])) $phones[] = $r['phone'];
            }
        }

        if (!empty($phones)) {
            $quotedPhones = array_map(function($p) {
                return "'" . addslashes($p) . "'";
            }, $phones);
            $phoneList = implode(',', $quotedPhones);
            $this->db->query("DELETE FROM ai_muted_contacts WHERE phone IN ($phoneList) AND device_id = :device_id");
            $this->db->bind('device_id', $deviceId);
            $this->db->execute();
            return $this->db->rowCount();
        }

        $this->db->query("DELETE FROM ai_muted_contacts WHERE id IN ($idList) AND device_id = :device_id");
        $this->db->bind('device_id', $deviceId);
        $this->db->execute();
        return $this->db->rowCount();
    }

    public function deleteAllMutedContacts($deviceId, $month = null, $year = null)
    {
        $query = "DELETE FROM ai_muted_contacts WHERE device_id = :device_id";
        if (!empty($month) && !empty($year) && $month !== 'all') {
            $query .= " AND (MONTH(created_at) = :month AND YEAR(created_at) = :year)";
        } elseif (!empty($year)) {
            $query .= " AND YEAR(created_at) = :year";
        }
        $this->db->query($query);
        $this->db->bind('device_id', $deviceId);
        if (!empty($month) && !empty($year) && $month !== 'all') {
            $this->db->bind('month', intval($month));
            $this->db->bind('year', intval($year));
        } elseif (!empty($year)) {
            $this->db->bind('year', intval($year));
        }
        $this->db->execute();
        return $this->db->rowCount();
    }
}
