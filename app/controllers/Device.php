<?php

/**
 * Device Controller
 * Proxy ke WAGTW Node.js Baileys Engine di port 3001
 */
class Device extends Controller {

    private $nodeUrl;
    private $apiToken;

    public function __construct()
    {
        $this->nodeUrl  = defined('WA_ENGINE_URL') ? WA_ENGINE_URL : 'http://127.0.0.1:3001';
        $this->apiToken = defined('WA_API_TOKEN') ? WA_API_TOKEN : 'wagtw_secret_token_2024';

        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASEURL . '/auth');
            exit;
        }
    }

    // ─── index() ─────────────────────────────────────────────────────────
    public function index()
    {
        $data['title']   = 'Manajemen Device - WAGTW';
        
        $visibleIdsStr = $this->getVisibleUserIdsString();
        $visibleIdsArray = explode(',', $visibleIdsStr);
        
        // Cek filter
        $filterUser = isset($_GET['filter_user']) ? $_GET['filter_user'] : 'all';
        if ($filterUser !== 'all' && in_array($filterUser, $visibleIdsArray)) {
            // Filter specific user that we are allowed to see
            $queryIds = intval($filterUser);
        } else {
            $queryIds = $visibleIdsStr;
        }
        
        $data['devices'] = $this->model('Device_model')->getAllDevicesByUser($queryIds);

        // Ambil daftar user untuk dropdown filter (hanya jika admin/super_admin)
        $data['filter_users'] = [];
        $role = $_SESSION['role'] ?? 'user';
        if ($role !== 'user') {
            $data['filter_users'] = $this->model('Setting_model')->getAllUsersForAdmin($_SESSION['user_id'], $role);
        }
        $data['current_filter'] = $filterUser;

        // Sinkronisasi status socket riil Baileys Node Engine dengan database
        $engineStatus = $this->callNode('GET', '/api/status');
        $engineOnline = ($engineStatus && isset($engineStatus['status']) && $engineStatus['status'] === 'ok');
        $data['engine_ok'] = $engineOnline;

        if ($engineOnline && isset($engineStatus['activeSessions']) && is_array($engineStatus['activeSessions'])) {
            $activeMap = [];
            foreach ($engineStatus['activeSessions'] as $as) {
                if (!empty($as['sessionId'])) {
                    $activeMap[$as['sessionId']] = $as['status'] ?? 'unknown';
                }
            }

            foreach ($data['devices'] as &$dev) {
                $sid = $dev['session_id'];
                $nodeState = $activeMap[$sid] ?? null;

                if ($nodeState === 'connected') {
                    if ($dev['status'] !== 'connected') {
                        $this->model('Device_model')->updateStatus($sid, 'connected');
                        $dev['status'] = 'connected';
                    }
                } elseif ($nodeState === 'qr') {
                    if ($dev['status'] !== 'qr') {
                        $this->model('Device_model')->updateStatus($sid, 'qr');
                        $dev['status'] = 'qr';
                    }
                } else {
                    // Sesi belum aktif di memori engine atau terputus
                    if ($dev['status'] === 'connected') {
                        $this->model('Device_model')->updateStatus($sid, 'disconnected');
                        $dev['status'] = 'disconnected';
                    }
                }
            }
            unset($dev);
        }

        if (isset($_GET['ajax']) && $_GET['ajax'] == 'true') {
            $this->view('device/index', $data);
        } else {
            $this->view('templates/header', $data);
            $this->view('templates/sidebar', $data);
            $this->view('device/index', $data);
            $this->view('templates/footer');
        }
    }

    // ─── engineStatus() — dipanggil AJAX async dari frontend ────────────
    public function engineStatus()
    {
        header('Content-Type: application/json');
        $result = $this->callNode('GET', '/api/status');
        $ok = ($result && isset($result['status']) && $result['status'] === 'ok');
        $sessions = [];

        if ($ok && isset($result['activeSessions']) && is_array($result['activeSessions'])) {
            foreach ($result['activeSessions'] as $as) {
                if (!empty($as['sessionId'])) {
                    $sessions[$as['sessionId']] = [
                        'status' => $as['status'] ?? 'unknown',
                        'phone'  => $as['phone'] ?? null,
                        'hasQR'  => !empty($as['hasQR']),
                    ];
                }
            }
        }

        echo json_encode([
            'engine_ok' => $ok,
            'sessions'  => $sessions
        ]);
    }

    // ─── add() ───────────────────────────────────────────────────────────
    public function add()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $name = trim($_POST['name'] ?? '');
        if (empty($name)) {
            echo json_encode(['status' => 'error', 'message' => 'Nama device wajib diisi!']);
            return;
        }

        $session_id = 'WA' . time() . rand(100, 999);
        $data = [
            'user_id'    => $_SESSION['user_id'],
            'name'       => $name,
            'session_id' => $session_id,
            'phone'      => null,
            'status'     => 'pending',
        ];

        if ($this->model('Device_model')->addDevice($data) > 0) {
            echo json_encode([
                'status'     => 'success',
                'message'    => 'Device ditambahkan. Klik Scan QR untuk menghubungkan.',
                'session_id' => $session_id,
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan device!']);
        }
    }

    // ─── delete() ────────────────────────────────────────────────────────
    public function delete()
    {
        header('Content-Type: application/json');
        if (!isset($_POST['id'])) return;

        $device = $this->model('Device_model')->getDeviceById($_POST['id'], $this->getVisibleUserIdsString());
        if ($device) {
            $this->callNode('POST', '/api/session/logout/' . urlencode($device['session_id']));
        }

        if ($this->model('Device_model')->deleteDevice($_POST['id'], $this->getVisibleUserIdsString()) > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Device berhasil dihapus.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus device.']);
        }
    }

    // ─── startSession() ──────────────────────────────────────────────────
    public function startSession()
    {
        header('Content-Type: application/json');
        $sessionId = $_POST['session_id'] ?? '';
        $forceNew  = !empty($_POST['force_new']);
        if (empty($sessionId)) {
            echo json_encode(['status' => 'error', 'message' => 'session_id diperlukan']); return;
        }
        $result = $this->callNode('POST', '/api/session/start', ['sessionId' => $sessionId, 'forceNew' => $forceNew]);
        if ($result === null) {
            echo json_encode(['status' => 'engine_offline', 'message' => 'WAGTW Engine offline. Jalankan: node server.js']);
            return;
        }
        echo json_encode($result);
    }

    // ─── getQR() ─────────────────────────────────────────────────────────
    public function getQR()
    {
        header('Content-Type: application/json');
        $sessionId = $_GET['session_id'] ?? '';
        if (empty($sessionId)) {
            echo json_encode(['status' => 'error', 'message' => 'session_id diperlukan']); return;
        }
        $result = $this->callNode('GET', '/api/session/qr/' . urlencode($sessionId));
        if ($result === null) {
            echo json_encode(['status' => 'engine_offline', 'message' => 'Engine offline']);
            return;
        }
        echo json_encode($result);
    }

    // ─── getStatus() ─────────────────────────────────────────────────────
    public function getStatus()
    {
        header('Content-Type: application/json');
        $sessionId = $_GET['session_id'] ?? '';
        if (empty($sessionId)) {
            echo json_encode(['status' => 'error', 'message' => 'session_id diperlukan']); return;
        }
        $result = $this->callNode('GET', '/api/session/status/' . urlencode($sessionId));
        if ($result === null) {
            echo json_encode(['status' => 'engine_offline', 'message' => 'Engine offline']);
            return;
        }
        echo json_encode($result);
    }

    // ─── requestPairingCode() ────────────────────────────────────────────
    public function requestPairingCode()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Metode request harus POST']);
            return;
        }

        $sessionId   = $_POST['session_id'] ?? '';
        $phoneNumber = $_POST['phone_number'] ?? '';

        if (empty($sessionId) || empty($phoneNumber)) {
            echo json_encode(['status' => 'error', 'message' => 'session_id dan nomor telepon diperlukan']);
            return;
        }

        $result = $this->callNode('POST', '/api/session/pairing-code', [
            'sessionId'   => $sessionId,
            'phoneNumber' => $phoneNumber,
        ]);

        if ($result === null) {
            echo json_encode(['status' => 'engine_offline', 'message' => 'WAGTW Engine offline. Harap nyalakan Node engine.']);
            return;
        }

        echo json_encode($result);
    }

    // ─── logoutSession() ─────────────────────────────────────────────────
    public function logoutSession()
    {
        header('Content-Type: application/json');
        $sessionId = $_POST['session_id'] ?? '';
        if (empty($sessionId)) {
            echo json_encode(['status' => 'error', 'message' => 'session_id diperlukan']); return;
        }
        $result = $this->callNode('POST', '/api/session/logout/' . urlencode($sessionId));
        if ($result === null) {
            echo json_encode(['status' => 'engine_offline', 'message' => 'Engine offline']);
            return;
        }
        echo json_encode($result);
    }

    // ─── sendMessage() ───────────────────────────────────────────────────
    public function sendMessage()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $sessionId = $_POST['session_id'] ?? '';
        $to        = preg_replace('/[^0-9]/', '', $_POST['to'] ?? '');
        $message   = $_POST['message'] ?? '';

        if (empty($sessionId) || empty($to) || empty($message)) {
            echo json_encode(['status' => 'error', 'message' => 'session_id, to, dan message diperlukan.']); return;
        }

        $result = $this->callNode('POST', '/api/send', [
            'sessionId' => $sessionId,
            'to'        => $to,
            'message'   => $message,
        ]);

        if ($result === null) {
            echo json_encode(['status' => 'engine_offline', 'message' => 'Engine offline.']); return;
        }
        echo json_encode($result);
    }

    // ─── getToken() ───────────────────────────────────────────────────────
    public function getToken()
    {
        header('Content-Type: application/json');
        $id = intval($_GET['id'] ?? 0);
        if (!$id) { echo json_encode(['status' => 'error', 'message' => 'ID tidak valid.']); return; }

        $device = $this->model('Device_model')->getDeviceById($id, $this->getVisibleUserIdsString());
        if (!$device) { echo json_encode(['status' => 'error', 'message' => 'Device tidak ditemukan.']); return; }

        // Generate token jika belum ada
        $token = $device['token'] ?? null;
        if (empty($token)) {
            $token = bin2hex(random_bytes(20));
            $this->model('Device_model')->updateToken($id, $this->getVisibleUserIdsString(), $token);
        }
        echo json_encode([
            'status'      => 'success', 
            'token'       => $token, 
            'device_name' => $device['name'],
            'webhook_url' => $device['webhook_url'] ?? ''
        ]);
    }

    // ─── regenerateToken() ────────────────────────────────────────────────
    public function regenerateToken()
    {
        header('Content-Type: application/json');
        $id = intval($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['status' => 'error', 'message' => 'ID tidak valid.']); return; }

        $token = bin2hex(random_bytes(20));
        $this->model('Device_model')->updateToken($id, $this->getVisibleUserIdsString(), $token);
        echo json_encode(['status' => 'success', 'token' => $token]);
    }

    // ─── updateWebhook() ──────────────────────────────────────────────────
    public function updateWebhook()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $id  = intval($_POST['id'] ?? 0);
        $url = trim($_POST['webhook_url'] ?? '');

        if (!$id) { echo json_encode(['status' => 'error', 'message' => 'ID device tidak valid.']); return; }

        if (!empty($url) && !filter_var($url, FILTER_VALIDATE_URL)) {
            echo json_encode(['status' => 'error', 'message' => 'Format URL Webhook tidak valid! Gunakan https:// atau http://']);
            return;
        }

        $this->model('Device_model')->updateWebhookUrl($id, $this->getVisibleUserIdsString(), $url);
        echo json_encode(['status' => 'success', 'message' => 'URL Webhook berhasil disimpan!']);
    }

    // ─── editDevice() ─────────────────────────────────────────────────────
    public function editDevice()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $id   = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');

        if (!$id || empty($name)) {
            echo json_encode(['status' => 'error', 'message' => 'ID dan nama wajib diisi.']); return;
        }

        $this->model('Device_model')->updateName($id, $this->getVisibleUserIdsString(), $name);
        echo json_encode(['status' => 'success', 'message' => 'Nama device berhasil diperbarui.']);
    }

    // ─── aiSettings() — GET: ambil, POST: simpan ─────────────────────────
    public function aiSettings()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $deviceId  = intval($_POST['device_id'] ?? 0);
            $aiEnabled = isset($_POST['ai_enabled']) ? 1 : 0;
            $searchEnabled = isset($_POST['search_enabled']) ? 1 : 0;
            $targetReply = in_array($_POST['target_reply'] ?? '', ['private', 'group', 'both']) ? $_POST['target_reply'] : 'both';
            $serverId  = !empty($_POST['ai_server_id']) ? intval($_POST['ai_server_id']) : null;
            $model     = $_POST['model'] ?? null;
            $style     = isset($_POST['style']) && is_array($_POST['style']) ? json_encode($_POST['style']) : '[]';
            $brand     = trim($_POST['brand'] ?? '');
            $aiName    = trim($_POST['ai_name'] ?? '');
            $language  = trim($_POST['language'] ?? '');
            $rules     = trim($_POST['rules'] ?? '');
            $replyMode = in_array($_POST['reply_mode'] ?? '', ['auto','manual','autoreply']) ? $_POST['reply_mode'] : 'manual';

            if (!$deviceId) { echo json_encode(['status' => 'error', 'message' => 'Device tidak valid.']); return; }

            $this->model('AiData_model')->upsertSettings([
                'device_id'           => $deviceId,
                'user_id'             => $_SESSION['user_id'],
                'ai_enabled'          => $aiEnabled,
                'activated_by_user_id'=> $aiEnabled ? $_SESSION['user_id'] : null, // track siapa yang mengaktifkan AI
                'search_enabled'      => $searchEnabled,
                'target_reply'        => $targetReply,
                'ai_server_id'        => $serverId,
                'model'               => $model,
                'style'               => $style,
                'brand'               => $brand,
                'ai_name'             => $aiName,
                'language'            => $language,
                'rules'               => $rules,
                'reply_mode'          => $replyMode,
            ]);
            echo json_encode(['status' => 'success', 'message' => 'Pengaturan AI berhasil disimpan!']);
            return;
        }

        // GET
        $deviceId = intval($_GET['device_id'] ?? 0);
        $settings = $this->model('AiData_model')->getSettings($deviceId, $this->getVisibleUserIdsString());
        
        // Ambil server dan model aktif yang didaftarkan Super Admin untuk digunakan user
        $servers  = $this->model('AiServer_model')->getActiveServersForUser();
        $dbModels = $this->model('AiServer_model')->getActiveModelsGlobal();
        $models = [];
        if ($dbModels) {
            foreach ($dbModels as $m) {
                $models[$m['model_name']] = $m['label'];
            }
        }
        
        echo json_encode(['status' => 'success', 'settings' => $settings, 'servers' => $servers, 'models' => $models]);
    }

    // ─── aiData() — GET: list, POST: tambah ──────────────────────────────
    public function aiData()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $deviceId = intval($_POST['device_id'] ?? 0);
            $title    = trim($_POST['title'] ?? '');
            $content  = trim($_POST['content'] ?? '');

            if (!$deviceId || empty($title) || empty($content)) {
                echo json_encode(['status' => 'error', 'message' => 'Device, judul, dan data wajib diisi.']); return;
            }
            if (mb_strlen($content) > 50000) {
                echo json_encode(['status' => 'error', 'message' => 'Data maksimal 50.000 karakter.']); return;
            }

            $this->model('AiData_model')->add([
                'device_id' => $deviceId,
                'user_id'   => $_SESSION['user_id'],
                'title'     => $title,
                'content'   => $content,
            ]);
            echo json_encode(['status' => 'success', 'message' => 'Data AI berhasil ditambahkan!']);
            return;
        }

        // GET
        $deviceId = intval($_GET['device_id'] ?? 0);
        $list     = $this->model('AiData_model')->getByDevice($deviceId, $this->getVisibleUserIdsString());
        echo json_encode(['status' => 'success', 'data' => $list]);
    }

    // ─── aiDataGet() — GET single item untuk edit ─────────────────────────
    public function aiDataGet()
    {
        header('Content-Type: application/json');
        $id       = intval($_GET['id'] ?? 0);
        $deviceId = intval($_GET['device_id'] ?? 0);
        $item     = $this->model('AiData_model')->getById($id, $deviceId, $this->getVisibleUserIdsString());
        if ($item) {
            echo json_encode(['status' => 'success', 'data' => $item]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Data tidak ditemukan.']);
        }
    }

    // ─── aiDataEdit() ─────────────────────────────────────────────────────
    public function aiDataEdit()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $id       = intval($_POST['id'] ?? 0);
        $deviceId = intval($_POST['device_id'] ?? 0);
        $title    = trim($_POST['title'] ?? '');
        $content  = trim($_POST['content'] ?? '');

        if (!$id || !$deviceId || empty($title) || empty($content)) {
            echo json_encode(['status' => 'error', 'message' => 'Semua field wajib diisi.']); return;
        }

        $this->model('AiData_model')->update($id, $deviceId, $this->getVisibleUserIdsString(), [
            'title'   => $title,
            'content' => $content,
        ]);
        echo json_encode(['status' => 'success', 'message' => 'Data AI berhasil diperbarui!']);
    }

    // ─── aiDataDelete() ───────────────────────────────────────────────────
    public function aiDataDelete()
    {
        header('Content-Type: application/json');
        $id       = intval($_POST['id'] ?? 0);
        $deviceId = intval($_POST['device_id'] ?? 0);
        if (!$id || !$deviceId) { echo json_encode(['status' => 'error', 'message' => 'ID tidak valid.']); return; }

        $this->model('AiData_model')->delete($id, $deviceId, $this->getVisibleUserIdsString());
        echo json_encode(['status' => 'success', 'message' => 'Data AI berhasil dihapus.']);
    }

    // ─── reconnect() ──────────────────────────────────────────────────────
    public function reconnect()
    {
        header('Content-Type: application/json');
        $sessionId = $_POST['session_id'] ?? '';
        if (empty($sessionId)) { echo json_encode(['status' => 'error', 'message' => 'session_id diperlukan']); return; }

        $result = $this->callNode('POST', '/api/session/reconnect', ['sessionId' => $sessionId]);
        if ($result === null) {
            echo json_encode(['status' => 'engine_offline', 'message' => 'WAGTW Engine offline. Jalankan: node server.js']); return;
        }

        if (isset($result['status']) && $result['status'] === 'need_qr') {
            $this->model('Device_model')->updateStatus($sessionId, 'disconnected');
        } elseif (isset($result['status']) && $result['status'] === 'success') {
            // Reconnect dikirim
        }

        echo json_encode($result);
    }

    // ─── autoReconnectCron() ───────────────────────────────────────────────
    // URL ini dapat dipanggil via Cron Job cPanel / Task Scheduler setiap 1 jam:
    // curl http://domain/wagtw/public/device/autoReconnectCron
    public function autoReconnectCron()
    {
        header('Content-Type: application/json');
        $result = $this->callNode('POST', '/api/reconnect-all', []);
        if ($result === null) {
            echo json_encode(['status' => 'engine_offline', 'message' => 'WAGTW Engine offline.']); return;
        }
        echo json_encode($result);
    }

    // ─── disconnect() ─────────────────────────────────────────────────────
    public function disconnect()
    {
        header('Content-Type: application/json');
        $sessionId = $_POST['session_id'] ?? '';
        if (empty($sessionId)) { echo json_encode(['status' => 'error', 'message' => 'session_id diperlukan']); return; }

        $result = $this->callNode('POST', '/api/session/logout/' . urlencode($sessionId));
        if ($result === null) {
            // Update status ke disconnected di DB
            $this->model('Device_model')->updateStatus($sessionId, 'disconnected');
            echo json_encode(['status' => 'success', 'message' => 'Device terputus.']); return;
        }
        $this->model('Device_model')->updateStatus($sessionId, 'disconnected');
        echo json_encode(['status' => 'success', 'message' => 'Device berhasil diputus.']);
    }
}
