<?php

class Aiserver extends Controller {

    public function __construct()
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASEURL . '/auth');
            exit;
        }

        // Hanya super_admin yang berhak mengelola Server AI, Model, dan API Key
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Akses ditolak: Hanya Super Admin yang berhak mendaftarkan dan mengelola Server AI.']);
                exit;
            }
            header('Location: ' . BASEURL . '/dashboard');
            exit;
        }
    }

    // ─── index() ───────────────────────────────────────────────────────────
    public function index()
    {
        $data['title']   = 'Manajemen Server AI';
        $data['servers'] = $this->model('AiServer_model')->getAllByUser($this->getVisibleUserIdsString());
        $data['models']  = $this->model('AiServer_model')->getAllModelsByUser($this->getVisibleUserIdsString());
        $data['search_engines'] = $this->model('AiServer_model')->getAllSearchEnginesByUser($this->getVisibleUserIdsString());
        // Monitoring: semua device yang AI-nya aktif beserta info user yang mengaktifkan
        $data['ai_activations'] = $this->model('AiData_model')->getActiveAiByAllUsers();

        if (isset($_GET['ajax']) && $_GET['ajax'] == 'true') {
            $this->view('aiserver/index', $data);
        } else {
            $this->view('templates/header', $data);
            $this->view('templates/sidebar', $data);
            $this->view('aiserver/index', $data);
            $this->view('templates/footer');
        }
    }

    // ─── add() ───────────────────────────────────────────────────────────────
    public function add()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $rawApiKey = trim($_POST['api_key'] ?? '');
        $rawLabel  = trim($_POST['label'] ?? '');
        $model     = $_POST['model'] ?? 'llama3-8b-8192';
        $isActive  = isset($_POST['is_active']) ? 1 : 0;
        $priority  = intval($_POST['priority'] ?? 0);

        if (empty($rawApiKey)) {
            echo json_encode(['status' => 'error', 'message' => 'API Key wajib diisi!']);
            return;
        }

        // Pisahkan jika user memasukkan banyak key sekaligus (per baris atau koma)
        $lines = preg_split('/[\r\n,]+/', $rawApiKey);
        $keys  = [];
        foreach ($lines as $line) {
            $k = trim($line);
            if (!empty($k)) {
                $keys[] = $k;
            }
        }

        if (empty($keys)) {
            echo json_encode(['status' => 'error', 'message' => 'Tidak ada API Key yang valid!']);
            return;
        }

        // Hitung server yang sudah ada untuk penomoran default
        $existingServers = $this->model('AiServer_model')->getAllByUser($this->getVisibleUserIdsString());
        $currentCount    = count($existingServers);

        $inserted   = 0;
        $firstLabel = '';
        foreach ($keys as $idx => $key) {
            $num = $currentCount + $idx + 1;

            if (!empty($rawLabel)) {
                // Jika user mengisi custom label
                if (count($keys) === 1) {
                    $itemLabel = $rawLabel;
                } else {
                    $itemLabel = $rawLabel . ' ' . ($idx + 1);
                }
            } else {
                // Default nama otomatis
                $itemLabel = 'Groq Key ' . $num;
            }

            if ($idx === 0) $firstLabel = $itemLabel;

            $data = [
                'user_id'   => $_SESSION['user_id'],
                'label'     => $itemLabel,
                'api_key'   => $key,
                'model'     => $model,
                'is_active' => $isActive,
                'priority'  => $priority,
            ];

            if ($this->model('AiServer_model')->addServer($data) > 0) {
                $inserted++;
            }
        }

        if ($inserted > 0) {
            $msg = (count($keys) > 1) 
                ? "$inserted API Key berhasil ditambahkan!" 
                : "Server AI ($firstLabel) berhasil ditambahkan!";
            echo json_encode(['status' => 'success', 'message' => $msg]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menambahkan server AI!']);
        }
    }

    // ─── edit() ──────────────────────────────────────────────────────────────
    public function edit()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $id        = intval($_POST['id'] ?? 0);
        $rawLabel  = trim($_POST['label'] ?? '');
        $apiKey    = trim($_POST['api_key'] ?? '');
        $model     = $_POST['model'] ?? 'llama3-8b-8192';
        $isActive  = isset($_POST['is_active']) ? 1 : 0;
        $priority  = intval($_POST['priority'] ?? 0);

        if (empty($apiKey)) {
            echo json_encode(['status' => 'error', 'message' => 'API Key wajib diisi!']);
            return;
        }

        // Jika label dikosongkan saat edit, berikan default label
        if (empty($rawLabel)) {
            $rawLabel = 'Groq Key #' . $id;
        }

        $data = [
            'label'     => $rawLabel,
            'api_key'   => $apiKey,
            'model'     => $model,
            'is_active' => $isActive,
            'priority'  => $priority,
        ];

        if ($this->model('AiServer_model')->updateServer($id, $this->getVisibleUserIdsString(), $data) >= 0) {
            echo json_encode(['status' => 'success', 'message' => 'Server AI berhasil diperbarui!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui server AI!']);
        }
    }

    // ─── delete() ────────────────────────────────────────────────────────────
    public function delete()
    {
        header('Content-Type: application/json');
        $id = intval($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['status' => 'error', 'message' => 'ID tidak valid.']); return; }

        if ($this->model('AiServer_model')->deleteServer($id, $this->getVisibleUserIdsString()) > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Server AI berhasil dihapus.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus server AI.']);
        }
    }

    // ─── toggleActive() ──────────────────────────────────────────────────────
    public function toggleActive()
    {
        header('Content-Type: application/json');
        $id = intval($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['status' => 'error', 'message' => 'ID tidak valid.']); return; }

        $this->model('AiServer_model')->toggleActive($id, $this->getVisibleUserIdsString());
        $server = $this->model('AiServer_model')->getById($id, $this->getVisibleUserIdsString());
        echo json_encode(['status' => 'success', 'is_active' => $server['is_active']]);
    }

    // ─── getServer() — ambil data 1 server untuk edit modal ─────────────────
    public function getServer()
    {
        header('Content-Type: application/json');
        $id = intval($_GET['id'] ?? 0);
        $server = $this->model('AiServer_model')->getById($id, $this->getVisibleUserIdsString());
        if ($server) {
            echo json_encode(['status' => 'success', 'data' => $server]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Server tidak ditemukan.']);
        }
    }
    // ─── AI MODELS ENDPOINTS ──────────────────────────────────────────────────

    public function addModel()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $data = [
            'user_id'    => $_SESSION['user_id'],
            'label'      => trim($_POST['label'] ?? ''),
            'model_name' => trim($_POST['model_name'] ?? ''),
            'is_active'  => isset($_POST['is_active']) ? 1 : 0,
            'priority'   => intval($_POST['priority'] ?? 0),
        ];

        if (empty($data['label']) || empty($data['model_name'])) {
            echo json_encode(['status' => 'error', 'message' => 'Label dan Model ID wajib diisi!']);
            return;
        }

        if ($this->model('AiServer_model')->addModel($data) > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Model AI berhasil ditambahkan!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menambahkan model AI!']);
        }
    }

    public function editModel()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $id = intval($_POST['id'] ?? 0);
        $data = [
            'label'      => trim($_POST['label'] ?? ''),
            'model_name' => trim($_POST['model_name'] ?? ''),
            'is_active'  => isset($_POST['is_active']) ? 1 : 0,
            'priority'   => intval($_POST['priority'] ?? 0),
        ];

        if (empty($data['label']) || empty($data['model_name'])) {
            echo json_encode(['status' => 'error', 'message' => 'Label dan Model ID wajib diisi!']);
            return;
        }

        if ($this->model('AiServer_model')->updateModel($id, $this->getVisibleUserIdsString(), $data) >= 0) {
            echo json_encode(['status' => 'success', 'message' => 'Model AI berhasil diperbarui!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui model AI!']);
        }
    }

    public function deleteModel()
    {
        header('Content-Type: application/json');
        $id = intval($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['status' => 'error', 'message' => 'ID tidak valid.']); return; }

        if ($this->model('AiServer_model')->deleteModel($id, $this->getVisibleUserIdsString()) > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Model AI berhasil dihapus.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus model AI.']);
        }
    }

    public function toggleActiveModel()
    {
        header('Content-Type: application/json');
        $id = intval($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['status' => 'error', 'message' => 'ID tidak valid.']); return; }

        $this->model('AiServer_model')->toggleActiveModel($id, $this->getVisibleUserIdsString());
        $model = $this->model('AiServer_model')->getModelById($id, $this->getVisibleUserIdsString());
        echo json_encode(['status' => 'success', 'is_active' => $model['is_active']]);
    }

    public function getModel()
    {
        header('Content-Type: application/json');
        $id = intval($_GET['id'] ?? 0);
        $model = $this->model('AiServer_model')->getModelById($id, $this->getVisibleUserIdsString());
        if ($model) {
            echo json_encode(['status' => 'success', 'data' => $model]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Model tidak ditemukan.']);
        }
    }

    // ─── AI SEARCH ENGINES ENDPOINTS ───────────────────────────────────────────

    public function addSearch()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $data = [
            'user_id'   => $_SESSION['user_id'],
            'provider'  => trim($_POST['provider'] ?? ''),
            'label'     => trim($_POST['label'] ?? ''),
            'api_key'   => trim($_POST['api_key'] ?? ''),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'priority'  => intval($_POST['priority'] ?? 0),
        ];

        if (empty($data['label']) || empty($data['provider'])) {
            echo json_encode(['status' => 'error', 'message' => 'Label dan Provider wajib diisi!']);
            return;
        }

        if ($this->model('AiServer_model')->addSearchEngine($data) > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Search Engine berhasil ditambahkan!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menambahkan search engine!']);
        }
    }

    public function editSearch()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $id = intval($_POST['id'] ?? 0);
        $data = [
            'provider'  => trim($_POST['provider'] ?? ''),
            'label'     => trim($_POST['label'] ?? ''),
            'api_key'   => trim($_POST['api_key'] ?? ''),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'priority'  => intval($_POST['priority'] ?? 0),
        ];

        if (empty($data['label']) || empty($data['provider'])) {
            echo json_encode(['status' => 'error', 'message' => 'Label dan Provider wajib diisi!']);
            return;
        }

        if ($this->model('AiServer_model')->updateSearchEngine($id, $this->getVisibleUserIdsString(), $data) >= 0) {
            echo json_encode(['status' => 'success', 'message' => 'Search Engine berhasil diperbarui!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui search engine!']);
        }
    }

    public function deleteSearch()
    {
        header('Content-Type: application/json');
        $id = intval($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['status' => 'error', 'message' => 'ID tidak valid.']); return; }

        if ($this->model('AiServer_model')->deleteSearchEngine($id, $this->getVisibleUserIdsString()) > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Search Engine berhasil dihapus.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus search engine.']);
        }
    }

    public function toggleActiveSearch()
    {
        header('Content-Type: application/json');
        $id = intval($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['status' => 'error', 'message' => 'ID tidak valid.']); return; }

        $this->model('AiServer_model')->toggleActiveSearchEngine($id, $this->getVisibleUserIdsString());
        $se = $this->model('AiServer_model')->getSearchEngineById($id, $this->getVisibleUserIdsString());
        echo json_encode(['status' => 'success', 'is_active' => $se['is_active']]);
    }

    public function getSearch()
    {
        header('Content-Type: application/json');
        $id = intval($_GET['id'] ?? 0);
        $se = $this->model('AiServer_model')->getSearchEngineById($id, $this->getVisibleUserIdsString());
        if ($se) {
            echo json_encode(['status' => 'success', 'data' => $se]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Search Engine tidak ditemukan.']);
        }
    }

    public function resetSearchStats()
    {
        header('Content-Type: application/json');
        $this->model('AiServer_model')->resetSearchStats($this->getVisibleUserIdsString());
        echo json_encode(['status' => 'success', 'message' => 'Statistik tarikan berhasil direset!', 'reset_at' => date('d M Y, H:i') . ' WIB']);
    }

    // ─── testApi() ───────────────────────────────────────────────────────────
    public function testApi()
    {
        header('Content-Type: application/json');
        $apiKey = trim($_POST['api_key'] ?? '');
        $model  = $_POST['model'] ?? 'llama3-8b-8192';

        if (empty($apiKey)) {
            echo json_encode(['status' => 'error', 'message' => 'API Key kosong!']);
            return;
        }

        $payload = json_encode([
            'model'    => $model,
            'messages' => [['role' => 'user', 'content' => 'Say "OK" in one word.']],
            'max_tokens' => 10,
        ]);

        $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_TIMEOUT        => 15,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err      = curl_error($ch);
        curl_close($ch);

        if ($err || !$response) {
            echo json_encode(['status' => 'error', 'message' => 'Tidak bisa terhubung ke Groq: ' . $err]);
            return;
        }

        $result = json_decode($response, true);
        if ($httpCode === 200 && isset($result['choices'][0])) {
            $reply = $result['choices'][0]['message']['content'] ?? 'OK';
            echo json_encode(['status' => 'success', 'message' => 'API Key valid! Respon: "' . $reply . '"']);
        } else {
            $errMsg = $result['error']['message'] ?? 'Unknown error (HTTP ' . $httpCode . ')';
            echo json_encode(['status' => 'error', 'message' => 'Groq Error: ' . $errMsg]);
        }
    }

    // ─── deleteActivation() — Hapus / Nonaktifkan AI device oleh Super Admin ───
    public function deleteActivation()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $deviceId = intval($_POST['device_id'] ?? 0);
        if (!$deviceId) {
            echo json_encode(['status' => 'error', 'message' => 'ID Device tidak valid.']);
            return;
        }

        if ($this->model('AiData_model')->deactivateDeviceAi($deviceId) >= 0) {
            echo json_encode(['status' => 'success', 'message' => 'Aktivasi AI pada device berhasil dihapus / dinonaktifkan!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus aktivasi AI.']);
        }
    }
}
