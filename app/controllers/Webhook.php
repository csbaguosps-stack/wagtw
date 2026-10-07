<?php

/**
 * Webhook Controller
 * Dokumentasi Panduan Integrasi Webhook Response & REST API Fonnte-style
 */
class Webhook extends Controller {

    public function __construct()
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASEURL . '/auth');
            exit;
        }
    }

    public function index()
    {
        $data['title'] = 'Panduan Webhook & Response API';
        
        $visibleIds = $this->getVisibleUserIdsString();
        $devices = $this->model('Device_model')->getAllDevicesByUser($visibleIds);

        // Pastikan setiap device punya token agar bisa langsung digunakan di panduan
        foreach ($devices as &$dev) {
            if (empty($dev['token'])) {
                $token = bin2hex(random_bytes(20));
                $this->model('Device_model')->updateToken($dev['id'], $visibleIds, $token);
                $dev['token'] = $token;
            }
        }
        unset($dev);

        $data['devices'] = $devices;
        $data['base_url'] = BASEURL;
        $data['api_send_url'] = BASEURL . '/api/send';

        if (isset($_GET['ajax']) && $_GET['ajax'] == 'true') {
            $this->view('webhook/index', $data);
        } else {
            $this->view('templates/header', $data);
            $this->view('templates/sidebar', $data);
            $this->view('webhook/index', $data);
            $this->view('templates/footer');
        }
    }

    /**
     * AJAX: Live Webhook Tester Simulator
     * Menguji endpoint Webhook URL user dengan mengirimkan payload simulasi
     */
    public function testWebhook()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Method POST required']);
            return;
        }

        $url = trim($_POST['webhook_url'] ?? '');
        $sender = preg_replace('/[^0-9]/', '', $_POST['sender'] ?? '6281234567890');
        $message = trim($_POST['message'] ?? 'Halo, cek status order #1029');
        $name = trim($_POST['name'] ?? 'Budi Santoso');
        $devicePhone = trim($_POST['device_phone'] ?? '6285100000000');

        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            echo json_encode(['status' => 'error', 'message' => 'URL Webhook tidak valid! Gunakan format http:// atau https://']);
            return;
        }

        $payload = [
            'event'     => 'message',
            'device'    => $devicePhone ?: '6285100000000',
            'sender'    => $sender ?: '6281234567890',
            'message'   => $message,
            'name'      => $name ?: 'Pengirim Test',
            'is_group'  => false,
            'timestamp' => time(),
            'wa_msg_id' => 'WAGTW_TEST_' . strtoupper(bin2hex(random_bytes(4)))
        ];

        $payloadJson = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payloadJson,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'User-Agent: WAGTW-Webhook-Engine/2.0',
                'X-Wagtw-Event: message'
            ],
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        $startTime = microtime(true);
        $responseBody = curl_exec($ch);
        $latencyMs = round((microtime(true) - $startTime) * 1000, 2);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($responseBody === false || !empty($curlErr)) {
            echo json_encode([
                'status'        => 'error',
                'message'       => 'Koneksi gagal: ' . ($curlErr ?: 'Server tidak merespons'),
                'http_code'     => $httpCode,
                'latency_ms'    => $latencyMs,
                'sent_payload'  => $payload
            ]);
            return;
        }

        // Cek apakah response berupa JSON yang berisi balasan (Direct Response)
        $parsedJson = json_decode($responseBody, true);
        $detectedReply = null;
        $isValidDirectReply = false;

        if (is_array($parsedJson)) {
            if (!empty($parsedJson['message'])) {
                $detectedReply = $parsedJson['message'];
                $isValidDirectReply = true;
            } elseif (!empty($parsedJson['reply'])) {
                $detectedReply = $parsedJson['reply'];
                $isValidDirectReply = true;
            } elseif (!empty($parsedJson['response']) && is_string($parsedJson['response'])) {
                $detectedReply = $parsedJson['response'];
                $isValidDirectReply = true;
            }
        }

        echo json_encode([
            'status'                => 'success',
            'http_code'             => $httpCode,
            'latency_ms'            => $latencyMs,
            'raw_response'          => $responseBody,
            'parsed_json'           => $parsedJson,
            'detected_reply'        => $detectedReply,
            'is_valid_direct_reply' => $isValidDirectReply,
            'sent_payload'          => $payload
        ]);
    }

    /**
     * AJAX: Simpan Webhook URL untuk device tertentu
     */
    public function saveWebhookUrl()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $id = intval($_POST['id'] ?? 0);
        $url = trim($_POST['webhook_url'] ?? '');

        if (!$id) {
            echo json_encode(['status' => 'error', 'message' => 'Device ID tidak valid!']);
            return;
        }

        if (!empty($url) && !filter_var($url, FILTER_VALIDATE_URL)) {
            echo json_encode(['status' => 'error', 'message' => 'Format URL tidak valid! Harus menggunakan http:// atau https://']);
            return;
        }

        $visibleIds = $this->getVisibleUserIdsString();
        $this->model('Device_model')->updateWebhookUrl($id, $visibleIds, $url);

        echo json_encode([
            'status'  => 'success',
            'message' => 'URL Webhook berhasil diperbarui!'
        ]);
    }
}
