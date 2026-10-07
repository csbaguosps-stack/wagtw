<?php

class Broadcast extends Controller {

    public function __construct()
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASEURL . '/auth');
            exit;
        }
    }

    public function index()
    {
        $data['title'] = 'Broadcast - WAGTW';
        $data['campaigns'] = $this->model('Broadcast_model')->getAll($this->getVisibleUserIdsString());
        $data['devices'] = $this->model('Device_model')->getAllDevicesByUser($this->getVisibleUserIdsString());

        if (isset($_GET['ajax']) && $_GET['ajax'] == 'true') {
            $this->view('broadcast/index', $data);
        } else {
            $this->view('templates/header', $data);
            $this->view('templates/sidebar', $data);
            $this->view('broadcast/index', $data);
            $this->view('templates/footer');
        }
    }

    public function create()
    {
        $data['title'] = 'Buat Broadcast Baru';
        $data['devices'] = $this->model('Device_model')->getAllDevicesByUser($this->getVisibleUserIdsString());

        if (isset($_GET['ajax']) && $_GET['ajax'] == 'true') {
            $this->view('broadcast/create', $data);
        } else {
            $this->view('templates/header', $data);
            $this->view('templates/sidebar', $data);
            $this->view('broadcast/create', $data);
            $this->view('templates/footer');
        }
    }

    public function download_template($type = 'xlsx')
    {
        $type = strtolower($type);
        if ($type === 'csv') {
            $filename = 'contoh_broadcast_target.csv';
            $filePath = dirname(__DIR__, 2) . '/public/templates/' . $filename;
            header('Content-Description: File Transfer');
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');

            if (file_exists($filePath)) {
                readfile($filePath);
            } else {
                echo "\xEF\xBB\xBF";
                echo "Nomor Telepon,Nama Target\r\n";
                echo "081234567890,Budi Santoso\r\n";
                echo "085712345678,Siti Rahmawati\r\n";
                echo "087812345679,Ahmad Fauzi\r\n";
                echo "628991234567,Dewi Lestari\r\n";
                echo "081398765432,Rian Pratama\r\n";
            }
            exit;
        } else {
            $filename = 'contoh_broadcast_target.xlsx';
            $filePath = dirname(__DIR__, 2) . '/public/templates/' . $filename;
            header('Content-Description: File Transfer');
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');

            if (file_exists($filePath)) {
                header('Content-Length: ' . filesize($filePath));
                readfile($filePath);
                exit;
            } else {
                $b64 = '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';
                $bin = base64_decode($b64);
                header('Content-Length: ' . strlen($bin));
                echo $bin;
                exit;
            }
        }
    }

    public function store()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $targetText = trim($_POST['targets'] ?? '');
        $message    = trim($_POST['message'] ?? '');
        $name       = trim($_POST['name'] ?? '');
        $deviceId   = intval($_POST['device_id'] ?? 0);
        
        if (empty($targetText) || empty($message) || empty($name) || !$deviceId) {
            echo json_encode(['status' => 'error', 'message' => 'Lengkapi form yang wajib! (Device, Nama Campaign, Target, Pesan)']);
            return;
        }

        // Parse TargetText (Expected format per line: Phone, Name)
        $lines = explode("\n", str_replace("\r", "", $targetText));
        $recipients = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            $parts = explode(',', $line);
            $phone = preg_replace('/[^0-9]/', '', $parts[0]);
            
            // Clean up to local 62 if starts with 0 or 8
            if (strpos($phone, '0') === 0) {
                $phone = '62' . substr($phone, 1);
            } elseif (strpos($phone, '8') === 0 && strlen($phone) >= 9) {
                $phone = '62' . $phone;
            }

            if (empty($phone) || strlen($phone) < 9) continue;

            $recipientName = count($parts) > 1 ? trim(implode(',', array_slice($parts, 1))) : '';
            $recipients[] = [
                'phone' => $phone,
                'name'  => $recipientName
            ];
        }

        if (empty($recipients)) {
            echo json_encode(['status' => 'error', 'message' => 'Tidak ada target nomor valid yang ditemukan!']);
            return;
        }

        // File handler (if media)
        $mediaPath = null;
        if (!empty($_FILES['media']['name'])) {
            $file = $_FILES['media'];
            if ($file['error'] == 0) {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $newName = time() . '_' . uniqid() . '.' . $ext;
                $uploadDir = dirname(dirname(__DIR__)) . '/public/uploads/broadcasts/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $targetFile = $uploadDir . $newName;

                if (move_uploaded_file($file['tmp_name'], $targetFile)) {
                    $mediaPath = 'uploads/broadcasts/' . $newName;
                }
            }
        }

        $campaignData = [
            'user_id'    => $_SESSION['user_id'],
            'device_id'  => $deviceId,
            'name'       => $name,
            'msg_type'   => $mediaPath ? 'image' : 'text', // simplification mapping
            'message'    => $message,
            'media_path' => $mediaPath,
            'delay_min'  => intval($_POST['delay_min'] ?? 5),
            'delay_max'  => intval($_POST['delay_max'] ?? 15),
            'batch_send' => intval($_POST['batch_send'] ?? 0),
            'batch_sleep'=> intval($_POST['batch_sleep'] ?? 0),
        ];

        $insertId = $this->model('Broadcast_model')->createCampaign($campaignData, $recipients);

        if ($insertId) {
            echo json_encode(['status' => 'success', 'message' => 'Broadcast berhasil dibuat.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan broadcast ke database.']);
        }
    }

    public function action()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $id = intval($_POST['id'] ?? 0);
        $action = $_POST['action'] ?? '';
        
        if (!$id || !$action) {
            echo json_encode(['status' => 'error', 'message' => 'Parameter tidak valid.']);
            return;
        }

        $model = $this->model('Broadcast_model');

        if ($action === 'delete') {
            if ($model->delete($id, $this->getVisibleUserIdsString())) {
                echo json_encode(['status' => 'success', 'message' => 'Broadcast terhapus.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus.']);
            }
            return;
        }

        // Start / Pause maps to status
        $status = '';
        if ($action === 'start') $status = 'running';
        if ($action === 'pause') $status = 'paused';

        if ($status && $model->changeStatus($id, $this->getVisibleUserIdsString(), $status)) {
            echo json_encode(['status' => 'success', 'message' => 'Status diubah menjadi ' . $status]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal mengubah status.']);
        }
    }

    public function recipients()
    {
        header('Content-Type: application/json');
        $id = intval($_GET['id'] ?? 0);
        if (!$id) {
            echo json_encode(['status' => 'error', 'message' => 'ID Campaign tidak valid']);
            return;
        }

        $campaign = $this->model('Broadcast_model')->getById($id, $this->getVisibleUserIdsString());
        if (!$campaign) {
            echo json_encode(['status' => 'error', 'message' => 'Campaign tidak ditemukan atau akses ditolak.']);
            return;
        }

        $recipients = $this->model('Broadcast_model')->getRecipientsByCampaign($id);

        echo json_encode([
            'status'     => 'success',
            'campaign'   => $campaign,
            'recipients' => $recipients
        ]);
    }

    // ─── Endpoint ringan untuk real-time polling dari frontend ───
    // GET /broadcast/progress          → semua campaign milik user
    // GET /broadcast/progress?id=X     → satu campaign tertentu
    public function progress()
    {
        header('Content-Type: application/json');
        $model   = $this->model('Broadcast_model');
        $userIds = $this->getVisibleUserIdsString();

        $id = intval($_GET['id'] ?? 0);

        if ($id) {
            // Single campaign progress
            $c = $model->getById($id, $userIds);
            if (!$c) {
                echo json_encode(['status' => 'error', 'message' => 'Not found']);
                return;
            }
            echo json_encode([
                'status'   => 'success',
                'campaigns' => [[
                    'id'     => (int)$c['id'],
                    'sent'   => (int)$c['sent'],
                    'failed' => (int)$c['failed'],
                    'total'  => (int)$c['total'],
                    'cstatus'=> $c['status'], // campaign status (running/done/paused/draft)
                ]]
            ]);
        } else {
            // All campaigns progress (hanya kolom yang diperlukan)
            $all = $model->getAll($userIds);
            $result = array_map(function($c) {
                return [
                    'id'     => (int)$c['id'],
                    'sent'   => (int)$c['sent'],
                    'failed' => (int)$c['failed'],
                    'total'  => (int)$c['total'],
                    'cstatus'=> $c['status'],
                ];
            }, $all);
            echo json_encode(['status' => 'success', 'campaigns' => $result]);
        }
    }

    public function get()
    {
        header('Content-Type: application/json');
        $id = intval($_GET['id'] ?? 0);
        if (!$id) {
            echo json_encode(['status' => 'error', 'message' => 'ID Campaign tidak valid']);
            return;
        }

        $campaign = $this->model('Broadcast_model')->getById($id, $this->getVisibleUserIdsString());
        if (!$campaign) {
            echo json_encode(['status' => 'error', 'message' => 'Campaign tidak ditemukan atau akses ditolak.']);
            return;
        }

        $recipients = $this->model('Broadcast_model')->getRecipientsByCampaign($id);

        $lines = [];
        foreach ($recipients as $r) {
            if (!empty($r['name'])) {
                $lines[] = $r['phone'] . ', ' . $r['name'];
            } else {
                $lines[] = $r['phone'];
            }
        }
        $targetText = implode("\n", $lines);

        echo json_encode([
            'status'      => 'success',
            'campaign'    => $campaign,
            'recipients'  => $recipients,
            'target_text' => $targetText
        ]);
    }

    public function update()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $id         = intval($_POST['id'] ?? 0);
        $name       = trim($_POST['name'] ?? '');
        $deviceId   = intval($_POST['device_id'] ?? 0);
        $message    = trim($_POST['message'] ?? '');
        $targetText = trim($_POST['targets'] ?? '');

        if (!$id || empty($name) || !$deviceId || empty($message)) {
            echo json_encode(['status' => 'error', 'message' => 'Lengkapi form yang wajib! (Device, Nama Campaign, Pesan)']);
            return;
        }

        $recipients = null;
        if (!empty($targetText)) {
            $lines = explode("\n", str_replace("\r", "", $targetText));
            $recipients = [];
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;
                
                $parts = explode(',', $line);
                $phone = preg_replace('/[^0-9]/', '', $parts[0]);
                
                if (strpos($phone, '0') === 0) {
                    $phone = '62' . substr($phone, 1);
                }

                if (empty($phone)) continue;

                $recipientName = isset($parts[1]) ? trim($parts[1]) : '';
                $recipients[] = [
                    'phone' => $phone,
                    'name'  => $recipientName
                ];
            }

            if (empty($recipients)) {
                echo json_encode(['status' => 'error', 'message' => 'Tidak ada target nomor valid yang ditemukan!']);
                return;
            }
        }

        // File handler (if new media uploaded)
        $mediaPath = null;
        if (!empty($_FILES['media']['name'])) {
            $file = $_FILES['media'];
            if ($file['error'] == 0) {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $newName = time() . '_' . uniqid() . '.' . $ext;
                $uploadDir = dirname(dirname(__DIR__)) . '/public/uploads/broadcasts/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $targetFile = $uploadDir . $newName;

                if (move_uploaded_file($file['tmp_name'], $targetFile)) {
                    $mediaPath = 'uploads/broadcasts/' . $newName;
                }
            }
        }

        $updateData = [
            'device_id'    => $deviceId,
            'name'         => $name,
            'message'      => $message,
            'delay_min'    => intval($_POST['delay_min'] ?? 5),
            'delay_max'    => intval($_POST['delay_max'] ?? 15),
            'batch_send'   => intval($_POST['batch_send'] ?? 0),
            'batch_sleep'  => intval($_POST['batch_sleep'] ?? 0),
            'remove_media' => !empty($_POST['remove_media'])
        ];

        if ($mediaPath !== null) {
            $updateData['media_path'] = $mediaPath;
        }

        $result = $this->model('Broadcast_model')->updateCampaign($id, $this->getVisibleUserIdsString(), $updateData, $recipients);

        echo json_encode($result);
    }
}
