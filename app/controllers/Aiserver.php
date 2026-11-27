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
        // Riwayat error API Key mati / gagal
        $data['api_logs']      = $this->model('AiServer_model')->getLogs(100);
        $data['api_log_count'] = count($data['api_logs']);

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
        
        // Utamakan model pertama yang aktif di Model AI
        $activeModels = $this->model('AiServer_model')->getActiveModelsGlobal();
        $primaryModel = !empty($activeModels) ? $activeModels[0]['model_name'] : '';
        $model        = (!empty($_POST['model']) && !in_array($_POST['model'], ['llama3-8b-8192', 'llama3-70b-8192'])) ? trim($_POST['model']) : $primaryModel;
        
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

        // Deduplikasi input jika ada key yang sama di textarea
        $keys = array_values(array_unique($keys));

        if (empty($keys)) {
            echo json_encode(['status' => 'error', 'message' => 'Tidak ada API Key yang valid!']);
            return;
        }

        // Ambil server yang sudah ada untuk penomoran dan validasi duplikasi
        $existingServers = $this->model('AiServer_model')->getAllByUser($this->getVisibleUserIdsString());
        $currentCount    = count($existingServers);

        // Map key yang sudah ada agar tidak disimpan berulang kali
        $existingKeyMap = [];
        foreach ($existingServers as $es) {
            $existingKeyMap[trim($es['api_key'])] = $es['label'];
        }

        $inserted   = 0;
        $skipped    = 0;
        $firstLabel = '';
        foreach ($keys as $idx => $key) {
            // Cek apakah key sudah pernah tersimpan sebelumnya
            if (isset($existingKeyMap[$key])) {
                $skipped++;
                continue;
            }

            $num = $currentCount + $inserted + 1;

            if (!empty($rawLabel)) {
                // Jika user mengisi custom label
                if (count($keys) === 1) {
                    $itemLabel = $rawLabel;
                } else {
                    $itemLabel = $rawLabel . ' ' . ($inserted + 1);
                }
            } else {
                // Default nama otomatis
                $itemLabel = 'Groq Key ' . $num;
            }

            if ($inserted === 0) $firstLabel = $itemLabel;

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
                $existingKeyMap[$key] = $itemLabel;
            }
        }

        if ($inserted > 0) {
            $msg = ($inserted > 1) 
                ? "$inserted API Key berhasil ditambahkan!" 
                : "Server AI ($firstLabel) berhasil ditambahkan!";
            if ($skipped > 0) {
                $msg .= " ($skipped API Key duplikat dilewati)";
            }
            echo json_encode(['status' => 'success', 'message' => $msg]);
        } else {
            if ($skipped > 0) {
                echo json_encode(['status' => 'error', 'message' => 'API Key ini sudah pernah disimpan sebelumnya (duplikat)!']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Gagal menambahkan server AI!']);
            }
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
        
        $activeModels = $this->model('AiServer_model')->getActiveModelsGlobal();
        $primaryModel = !empty($activeModels) ? $activeModels[0]['model_name'] : '';
        $model        = (!empty($_POST['model']) && !in_array($_POST['model'], ['llama3-8b-8192', 'llama3-70b-8192'])) ? trim($_POST['model']) : $primaryModel;
        
        $isActive  = isset($_POST['is_active']) ? 1 : 0;
        $priority  = intval($_POST['priority'] ?? 0);

        if (empty($apiKey)) {
            echo json_encode(['status' => 'error', 'message' => 'API Key wajib diisi!']);
            return;
        }

        // Cek duplikasi API Key pada server lain
        $duplicate = $this->model('AiServer_model')->isKeyExists($apiKey, $this->getVisibleUserIdsString(), $id);
        if ($duplicate) {
            echo json_encode(['status' => 'error', 'message' => 'API Key ini sudah digunakan oleh server (' . ($duplicate['label'] ?? 'lain') . ')!']);
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

        // Cek duplikasi Model ID
        if ($this->model('AiServer_model')->isModelExists($data['model_name'], $this->getVisibleUserIdsString())) {
            echo json_encode(['status' => 'error', 'message' => 'Model ID (' . $data['model_name'] . ') sudah terdaftar!']);
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

        // Cek duplikasi Model ID pada model lain
        if ($this->model('AiServer_model')->isModelExists($data['model_name'], $this->getVisibleUserIdsString(), $id)) {
            echo json_encode(['status' => 'error', 'message' => 'Model ID (' . $data['model_name'] . ') sudah digunakan oleh model lain!']);
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
        $apiKey   = trim($_POST['api_key'] ?? '');
        $model    = trim($_POST['model'] ?? '');
        $serverId = intval($_POST['server_id'] ?? 0) ?: null;
        $label    = trim($_POST['label'] ?? 'Manual Test Key');

        // Selalu ambil model-model yang aktif di tabel ai_models (Model AI)
        $activeModels = $this->model('AiServer_model')->getActiveModelsGlobal();
        $primaryModel = !empty($activeModels) ? $activeModels[0]['model_name'] : '';
        $activeModelNames = array_column($activeModels ?: [], 'model_name');

        // Jika model kosong, atau decommissioned, atau tidak ada dalam daftar model aktif:
        $decommissioned = ['llama3-8b-8192', 'llama3-70b-8192', 'llama-3.1-70b-versatile'];
        if (empty($model) || in_array($model, $decommissioned) || (!empty($activeModelNames) && !in_array($model, $activeModelNames))) {
            $model = $primaryModel;
        }

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

        $maskedKey = (strlen($apiKey) > 16) ? substr($apiKey, 0, 10) . '...' . substr($apiKey, -6) : '***';

        if ($err || !$response) {
            $errMsg = 'Tidak bisa terhubung ke Groq: ' . ($err ?: 'No response');
            $this->model('AiServer_model')->addLog($serverId, $label, $maskedKey, $model, 'CONN_ERR', $errMsg);
            $newCount = $this->model('AiServer_model')->getLogCount();
            echo json_encode(['status' => 'error', 'message' => $errMsg, 'logged' => true, 'log_count' => $newCount, 'model' => $model]);
            return;
        }

        $result = json_decode($response, true);
        if ($httpCode === 200 && isset($result['choices'][0])) {
            $reply = $result['choices'][0]['message']['content'] ?? 'OK';
            echo json_encode(['status' => 'success', 'message' => 'API Key valid! (Model diuji: ' . $model . ') Respon: "' . $reply . '"', 'model' => $model]);
        } else {
            $errMsg = $result['error']['message'] ?? ('HTTP ' . $httpCode . ' Error');
            $statusCode = $httpCode ? (string)$httpCode : 'ERR';
            $this->model('AiServer_model')->addLog($serverId, $label, $maskedKey, $model, $statusCode, $errMsg);
            $newCount = $this->model('AiServer_model')->getLogCount();
            echo json_encode(['status' => 'error', 'message' => 'Groq Error (' . $statusCode . '): ' . $errMsg, 'logged' => true, 'log_count' => $newCount, 'model' => $model]);
        }
    }

    // ─── deleteLog() — Hapus 1 baris riwayat API mati ─────────────────────────
    public function deleteLog()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $id = intval($_POST['id'] ?? 0);
        if (!$id) {
            echo json_encode(['status' => 'error', 'message' => 'ID riwayat tidak valid!']);
            return;
        }

        if ($this->model('AiServer_model')->deleteLog($id) >= 0) {
            $count = $this->model('AiServer_model')->getLogCount();
            echo json_encode(['status' => 'success', 'message' => 'Riwayat berhasil dihapus.', 'count' => $count]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus riwayat.']);
        }
    }

    // ─── deleteAllLogs() — Hapus seluruh riwayat API mati ─────────────────────
    public function deleteAllLogs()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $this->model('AiServer_model')->deleteAllLogs();
        echo json_encode(['status' => 'success', 'message' => 'Seluruh riwayat API mati berhasil dibersihkan!']);
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
