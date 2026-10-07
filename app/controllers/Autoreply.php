<?php

class Autoreply extends Controller {
    public function __construct()
    {
        if(!isset($_SESSION['user_id'])) {
            header('Location: ' . BASEURL . '/auth');
            exit;
        }
    }

    public function index()
    {
        $data['title'] = 'Autoreply WAGTW';
        $data['autoreplies'] = $this->model('Autoreply_model')->getAllByUser($this->getVisibleUserIdsString());
        $data['devices'] = $this->model('Device_model')->getAllDevicesByUser($this->getVisibleUserIdsString());

        if(isset($_GET['ajax']) && $_GET['ajax'] == 'true') {
            $this->view('autoreply/index', $data);
        } else {
            $this->view('templates/header', $data);
            $this->view('templates/sidebar', $data);
            $this->view('autoreply/index', $data);
            $this->view('templates/footer');
        }
    }

    public function add()
    {
        header('Content-Type: application/json');
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            $targetReply = in_array($_POST['target_reply'] ?? '', ['private', 'group', 'both']) ? $_POST['target_reply'] : 'both';

            $postData = [
                'user_id' => $_SESSION['user_id'],
                'device_id' => !empty($_POST['device_id']) ? $_POST['device_id'] : null,
                'name' => $_POST['name'] ?? '',
                'trigger_word' => $_POST['trigger_word'] ?? '',
                'match_type' => $_POST['match_type'] ?? 'contains',
                'target_reply' => $targetReply,
                'reply_type' => $_POST['reply_type'] ?? 'text',
                'reply_text' => $_POST['reply_text'] ?? '',
                'delay' => $_POST['delay'] ?? 0,
                'set_read' => isset($_POST['set_read']) ? 1 : 0,
                'set_typing' => isset($_POST['set_typing']) ? 1 : 0
            ];

            if(empty($postData['trigger_word'])) {
                echo json_encode(['status' => 'error', 'message' => 'Keyword tidak boleh kosong!']);
                return;
            }

            try {
                if($this->model('Autoreply_model')->addAutoreply($postData) > 0) {
                    echo json_encode(['status' => 'success', 'message' => 'Autoreply berhasil ditambahkan!']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Gagal menambahkan autoreply!']);
                }
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => 'Error DB: ' . $e->getMessage()]);
            }
        }
    }

    public function delete()
    {
        header('Content-Type: application/json');
        if(isset($_POST['id'])) {
            if($this->model('Autoreply_model')->deleteAutoreply($_POST['id'], $this->getVisibleUserIdsString()) > 0) {
                echo json_encode(['status' => 'success', 'message' => 'Autoreply berhasil dihapus.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus autoreply.']);
            }
        }
    }

    public function get_business_settings()
    {
        header('Content-Type: application/json');
        if (isset($_GET['device_id'])) {
            $deviceId = $_GET['device_id'];
            $device = $this->model('Device_model')->getDeviceById($deviceId, $this->getVisibleUserIdsString());
            if ($device) {
                echo json_encode([
                    'status' => 'success',
                    'data' => [
                        'welcome_enabled' => $device['welcome_enabled'],
                        'welcome_message' => $device['welcome_message'],
                        'away_enabled' => $device['away_enabled'],
                        'away_message' => $device['away_message'],
                        'work_hours' => $device['work_hours']
                    ]
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Device tidak ditemukan']);
            }
        }
    }

    public function save_business_settings()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['device_id'])) {
            $deviceId = $_POST['device_id'];
            $device = $this->model('Device_model')->getDeviceById($deviceId, $this->getVisibleUserIdsString());
            
            if ($device) {
                $db = new Database();
                $db->query("UPDATE devices SET 
                            welcome_enabled = :welcome_enabled, 
                            welcome_message = :welcome_message, 
                            away_enabled = :away_enabled, 
                            away_message = :away_message, 
                            work_hours = :work_hours 
                            WHERE id = :id AND user_id IN (" . $this->getVisibleUserIdsString() . ")");
                            
                $db->bind('welcome_enabled', isset($_POST['welcome_enabled']) ? 1 : 0);
                $db->bind('welcome_message', $_POST['welcome_message'] ?? null);
                $db->bind('away_enabled', isset($_POST['away_enabled']) ? 1 : 0);
                $db->bind('away_message', $_POST['away_message'] ?? null);
                $db->bind('work_hours', $_POST['work_hours'] ?? null);
                $db->bind('id', $deviceId);
                $db->execute();
                
                echo json_encode(['status' => 'success', 'message' => 'Pengaturan bisnis berhasil disimpan']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Device tidak valid atau Anda tidak memiliki akses']);
            }
        }
    }
}
