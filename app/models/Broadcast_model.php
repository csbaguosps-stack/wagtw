<?php

class Broadcast_model {
    private $db;

    public function __construct()
    {
        $this->db = new Database;
    }

    public function getAll($userId)
    {
        $this->db->query("SELECT c.*, d.name as device_name, d.phone as device_phone FROM campaigns c 
                          LEFT JOIN devices d ON d.id = c.device_id
                          WHERE c.user_id IN ($userId) ORDER BY c.id DESC");
        return $this->db->resultSet();
    }


    public function getActiveCampaigns()
    {
        // For the Node.js API to fetch running campaigns (used by the worker wrapper)
        $this->db->query("SELECT * FROM campaigns WHERE status = 'running'");
        return $this->db->resultSet();
    }

    public function getById($id, $userId)
    {
        $this->db->query("SELECT c.*, d.name as device_name, d.phone as device_phone FROM campaigns c 
                          LEFT JOIN devices d ON d.id = c.device_id
                          WHERE c.id = :id AND c.user_id IN ($userId) LIMIT 1");
        $this->db->bind('id', $id);
        return $this->db->single();
    }

    public function createCampaign($data, $recipients)
    {
        try {
            $this->db->beginTransaction();

            $this->db->query("INSERT INTO campaigns (user_id, device_id, name, msg_type, message, media_path, delay_min, delay_max, batch_send, batch_sleep, status, scheduled_at, total, created_at) 
                              VALUES (:user_id, :device_id, :name, :msg_type, :message, :media_path, :delay_min, :delay_max, :batch_send, :batch_sleep, 'draft', :scheduled_at, :total, NOW())");
            
            $this->db->bind('user_id',     $data['user_id']);
            $this->db->bind('device_id',   $data['device_id']);
            $this->db->bind('name',        $data['name']);
            $this->db->bind('msg_type',    $data['msg_type'] ?? 'text');
            $this->db->bind('message',     $data['message']);
            $this->db->bind('media_path',  $data['media_path'] ?? null);
            $this->db->bind('delay_min',   $data['delay_min'] ?? 5);
            $this->db->bind('delay_max',   $data['delay_max'] ?? 15);
            $this->db->bind('batch_send',  $data['batch_send'] ?? 0);
            $this->db->bind('batch_sleep', $data['batch_sleep'] ?? 0);
            $this->db->bind('scheduled_at',$data['scheduled_at'] ?? null);
            $this->db->bind('total',       count($recipients));
            $this->db->execute();

            $campaignId = $this->db->lastInsertId();

            if ($campaignId) {
                // Batch insert recipients in chunks of 100 for high speed and reliability
                $chunks = array_chunk($recipients, 100);
                foreach ($chunks as $chunk) {
                    $placeholders = [];
                    $params = [];
                    foreach ($chunk as $idx => $row) {
                        $phone = trim($row['phone'] ?? '');
                        $name = trim($row['name'] ?? '');
                        if (empty($phone)) continue;

                        $placeholders[] = "(:cid_{$idx}, :phone_{$idx}, :name_{$idx}, 'pending')";
                        $params["cid_{$idx}"] = $campaignId;
                        $params["phone_{$idx}"] = $phone;
                        $params["name_{$idx}"] = $name;
                    }

                    if (!empty($placeholders)) {
                        $sql = "INSERT INTO campaign_recipients (campaign_id, phone, name, status) VALUES " . implode(', ', $placeholders);
                        $this->db->query($sql);
                        foreach ($params as $k => $v) {
                            $this->db->bind($k, $v);
                        }
                        $this->db->execute();
                    }
                }

                $this->db->commit();
                return $campaignId;
            }

            $this->db->rollBack();
            return false;
        } catch (Exception $e) {
            try { $this->db->rollBack(); } catch (Exception $re) {}
            error_log('[createCampaign Error] ' . $e->getMessage());
            return false;
        }
    }

    public function updateCampaign($id, $userId, $data, $recipients = null)
    {
        try {
            $campaign = $this->getById($id, $userId);
            if (!$campaign) {
                return ['status' => 'error', 'message' => 'Campaign tidak ditemukan atau akses ditolak.'];
            }

            if ($campaign['status'] === 'running') {
                return ['status' => 'error', 'message' => 'Campaign sedang berjalan. Harap jeda terlebih dahulu sebelum mengedit.'];
            }

            // Media path handling
            $mediaPath = $data['media_path'] ?? $campaign['media_path'];
            if (!empty($data['remove_media'])) {
                $mediaPath = null;
            }
            $msgType = $mediaPath ? 'image' : 'text';

            // If recipients are provided, update recipients table
            $newTotal = $campaign['total'];
            if ($recipients !== null) {
                // If nothing was sent yet (e.g. draft or fresh campaign)
                if (intval($campaign['sent']) === 0 && intval($campaign['failed']) === 0) {
                    $this->db->query("DELETE FROM campaign_recipients WHERE campaign_id = :id");
                    $this->db->bind('id', $id);
                    $this->db->execute();

                    foreach ($recipients as $row) {
                        $this->db->query("INSERT INTO campaign_recipients (campaign_id, phone, name, status) VALUES (:campaign_id, :phone, :name, 'pending')");
                        $this->db->bind('campaign_id', $id);
                        $this->db->bind('phone',       trim($row['phone']));
                        $this->db->bind('name',        trim($row['name'] ?? ''));
                        $this->db->execute();
                    }
                    $newTotal = count($recipients);
                } else {
                    // Campaign partially sent/failed - delete pending, insert new ones not yet sent
                    $this->db->query("DELETE FROM campaign_recipients WHERE campaign_id = :id AND status = 'pending'");
                    $this->db->bind('id', $id);
                    $this->db->execute();

                    $this->db->query("SELECT phone FROM campaign_recipients WHERE campaign_id = :id");
                    $this->db->bind('id', $id);
                    $existingRows = $this->db->resultSet();
                    $existingPhones = array_map(function($r) { return $r['phone']; }, $existingRows);

                    foreach ($recipients as $row) {
                        $cleanPhone = trim($row['phone']);
                        if (!in_array($cleanPhone, $existingPhones)) {
                            $this->db->query("INSERT INTO campaign_recipients (campaign_id, phone, name, status) VALUES (:campaign_id, :phone, :name, 'pending')");
                            $this->db->bind('campaign_id', $id);
                            $this->db->bind('phone',       $cleanPhone);
                            $this->db->bind('name',        trim($row['name'] ?? ''));
                            $this->db->execute();
                        }
                    }

                    $this->db->query("SELECT COUNT(*) as cnt FROM campaign_recipients WHERE campaign_id = :id");
                    $this->db->bind('id', $id);
                    $cntRow = $this->db->single();
                    $newTotal = intval($cntRow['cnt'] ?? count($recipients));
                }
            }

            // Update campaigns row
            $sql = "UPDATE campaigns SET 
                    device_id = :device_id,
                    name = :name,
                    msg_type = :msg_type,
                    message = :message,
                    media_path = :media_path,
                    delay_min = :delay_min,
                    delay_max = :delay_max,
                    batch_send = :batch_send,
                    batch_sleep = :batch_sleep,
                    total = :total,
                    updated_at = NOW()
                    WHERE id = :id AND user_id IN ($userId)";

            $this->db->query($sql);
            $this->db->bind('device_id',   $data['device_id']);
            $this->db->bind('name',        $data['name']);
            $this->db->bind('msg_type',    $msgType);
            $this->db->bind('message',     $data['message']);
            $this->db->bind('media_path',  $mediaPath);
            $this->db->bind('delay_min',   $data['delay_min'] ?? 5);
            $this->db->bind('delay_max',   $data['delay_max'] ?? 15);
            $this->db->bind('batch_send',  $data['batch_send'] ?? 0);
            $this->db->bind('batch_sleep', $data['batch_sleep'] ?? 0);
            $this->db->bind('total',       $newTotal);
            $this->db->bind('id',          $id);
            $this->db->execute();

            return ['status' => 'success', 'message' => 'Campaign berhasil diperbarui.'];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Gagal memperbarui campaign: ' . $e->getMessage()];
        }
    }

    public function changeStatus($id, $userId, $status)
    {
        $this->db->query("UPDATE campaigns SET status = :status WHERE id = :id AND user_id IN ($userId)");
        $this->db->bind('status', $status);
        $this->db->bind('id', $id);
        return $this->db->execute();
    }

    public function delete($id, $userId)
    {
        // Must verify ownership before deleting children
        $campaign = $this->getById($id, $userId);
        if (!$campaign) return false;

        $this->db->query("DELETE FROM campaign_recipients WHERE campaign_id = :id");
        $this->db->bind('id', $id);
        $this->db->execute();
        
        $this->db->query("DELETE FROM campaigns WHERE id = :id AND user_id IN ($userId)");
        $this->db->bind('id', $id);
        $this->db->execute();
        return $this->db->rowCount();
    }

    public function getRecipientsByCampaign($campaignId)
    {
        $this->db->query("SELECT * FROM campaign_recipients WHERE campaign_id = :id ORDER BY id ASC");
        $this->db->bind('id', $campaignId);
        return $this->db->resultSet();
    }
}
