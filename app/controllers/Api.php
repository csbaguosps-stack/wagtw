<?php

/**
 * Public REST API Controller
 * Kompatibel dengan format integrasi Fonnte untuk aplikasi dan website eksternal.
 * Endpoint:
 *   POST /api/send           -> Kirim pesan WhatsApp
 *   GET  /api/status         -> Cek status device berdasarkan token
 */
class Api extends Controller {

    private $nodeUrl;
    private $apiToken;

    public function __construct()
    {
        // Izinkan CORS agar API bisa dipanggil dari web browser / frontend klien lain
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: Authorization, Content-Type, X-Api-Token, X-Requested-With');
        header('Access-Control-Allow-Methods: POST, GET, OPTIONS');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        header('Content-Type: application/json; charset=utf-8');

        $this->nodeUrl  = defined('WA_ENGINE_URL') ? WA_ENGINE_URL : 'http://127.0.0.1:3001';
        $this->apiToken = defined('WA_API_TOKEN') ? WA_API_TOKEN : 'wagtw_secret_token_2024';
    }

    // ─── Helper: Ekstrak Token Device dari Request ────────────────────────
    private function extractToken()
    {
        $headers = [];
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        } else {
            foreach ($_SERVER as $name => $value) {
                if (substr($name, 0, 5) == 'HTTP_') {
                    $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                }
            }
        }

        // 1. Cek Header Authorization: {TOKEN} atau Bearer {TOKEN}
        $authHeader = $headers['Authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!empty($authHeader)) {
            if (stripos($authHeader, 'Bearer ') === 0) {
                return trim(substr($authHeader, 7));
            }
            return trim($authHeader);
        }

        // 2. Cek X-Api-Token
        if (!empty($headers['X-Api-Token'])) {
            return trim($headers['X-Api-Token']);
        }
        if (!empty($_SERVER['HTTP_X_API_TOKEN'])) {
            return trim($_SERVER['HTTP_X_API_TOKEN']);
        }

        // 3. Cek parameter POST / GET
        if (!empty($_POST['token'])) {
            return trim($_POST['token']);
        }
        if (!empty($_GET['token'])) {
            return trim($_GET['token']);
        }

        // 4. Cek JSON Raw input
        $raw = file_get_contents('php://input');
        if (!empty($raw)) {
            $json = json_decode($raw, true);
            if (!empty($json['token'])) {
                return trim($json['token']);
            }
        }

        return null;
    }

    // ─── Helper: Ambil input (mendukung Form-Urlencoded & JSON Body) ────────
    private function getInputs()
    {
        $raw = file_get_contents('php://input');
        $inputs = [];
        if (!empty($raw)) {
            $json = json_decode($raw, true);
            if (is_array($json)) {
                $inputs = $json;
            }
        }
        return array_merge($_GET, $_POST, $inputs);
    }

    // ─── Helper: cURL ke WAGTW Node.js Engine ─────────────────────────────
    private function callNode($method, $path, $body = null)
    {
        $urls = [$this->nodeUrl . $path];
        if (strpos($this->nodeUrl, '127.0.0.1:3001') === false) {
            $urls[] = 'http://127.0.0.1:3001' . $path;
        }

        $lastErr = null;
        $lastCode = 0;

        foreach ($urls as $url) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST  => $method,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'X-Api-Token: ' . $this->apiToken,
                ],
                CURLOPT_TIMEOUT        => 15,
                CURLOPT_CONNECTTIMEOUT => 8,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
            ]);

            if ($body !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
            }

            $response = curl_exec($ch);
            $err      = curl_error($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($response !== false && !$err && $httpCode < 500) {
                $decoded = json_decode($response, true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }

            $lastErr = $err ?: "HTTP status $httpCode";
            $lastCode = $httpCode;
        }

        return ['status' => 'error', 'message' => "Node engine unreachable ($lastErr)"];
    }

    // ─── POST /api/send ───────────────────────────────────────────────────
    public function send()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['status' => false, 'reason' => 'Method not allowed. Use POST.']);
                return;
            }

            $token = $this->extractToken();
            if (empty($token)) {
                echo json_encode([
                    'status' => false,
                    'reason' => 'token invalid',
                    'detail' => 'Header Authorization atau parameter token wajib diisi.'
                ]);
                return;
            }

            $device = $this->model('Device_model')->getDeviceByToken($token);
            if (!$device) {
                echo json_encode([
                    'status' => false,
                    'reason' => 'token invalid',
                    'detail' => 'Device dengan token ini tidak ditemukan.'
                ]);
                return;
            }

            if ($device['status'] !== 'connected') {
                echo json_encode([
                    'status' => false,
                    'reason' => 'device not connected',
                    'detail' => 'Device WhatsApp "' . $device['name'] . '" sedang ' . $device['status'] . '. Silakan scan QR terlebih dahulu.'
                ]);
                return;
            }

            $inputs  = $this->getInputs();
            $target  = trim($inputs['target'] ?? $inputs['to'] ?? '');
            $message = trim($inputs['message'] ?? '');
            $countryCode = trim($inputs['countryCode'] ?? '62');

            if (empty($target) || empty($message)) {
                echo json_encode([
                    'status' => false,
                    'reason' => 'input invalid',
                    'detail' => 'Parameter target dan message wajib diisi.'
                ]);
                return;
            }

            // Sanitasi nomor tujuan (Fonnte style)
            $targets = array_filter(array_map('trim', explode(',', $target)));
            $results = [];
            $hasSuccess = false;

            foreach ($targets as $dest) {
                $cleanDest = $dest;
                // Jika bukan grup (tidak mengandung '-' atau '@g.us')
                // PHP 7.4 compatible check (avoid str_contains)
                if (strpos($dest, '-') === false && strpos($dest, '@g.us') === false) {
                    $cleanDest = preg_replace('/[^0-9]/', '', $dest);
                    // Ganti awalan 0 atau + dengan country code (avoid str_starts_with)
                    if (substr($cleanDest, 0, 1) === '0' && $countryCode !== '0') {
                        $cleanDest = $countryCode . substr($cleanDest, 1);
                    }
                }

                // Kirim ke engine Node.js
                $res = $this->callNode('POST', '/api/send', [
                    'sessionId' => $device['session_id'],
                    'to'        => $cleanDest,
                    'message'   => $message
                ]);

                if ($res && isset($res['status']) && $res['status'] === 'success') {
                    $hasSuccess = true;
                    $results[] = [
                        'target'  => $cleanDest,
                        'status'  => true,
                        'message' => 'Pesan berhasil dikirim'
                    ];
                } else {
                    $results[] = [
                        'target'  => $cleanDest,
                        'status'  => false,
                        'message' => $res['message'] ?? 'Gagal mengirim pesan via WhatsApp Engine'
                    ];
                }
            }

            if ($hasSuccess) {
                echo json_encode([
                    'status'    => true,
                    'message'   => 'Pesan berhasil diproses',
                    'device'    => $device['phone'],
                    'results'   => $results,
                    'requestid' => time() . rand(100, 999)
                ]);
            } else {
                echo json_encode([
                    'status'    => false,
                    'reason'    => 'engine error',
                    'results'   => $results,
                    'detail'    => 'Tidak ada pesan yang berhasil dikirim ke WhatsApp engine.'
                ]);
            }
        } catch (\Throwable $e) {
            error_log('[WAGTW API Send Error] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            echo json_encode([
                'status' => false,
                'reason' => 'server error',
                'detail' => 'Terjadi kesalahan internal: ' . $e->getMessage()
            ]);
        }
    }

    // ─── GET /api/status ──────────────────────────────────────────────────
    public function status()
    {
        $token = $this->extractToken();
        if (empty($token)) {
            echo json_encode(['status' => false, 'reason' => 'token invalid']);
            return;
        }

        $device = $this->model('Device_model')->getDeviceByToken($token);
        if (!$device) {
            echo json_encode(['status' => false, 'reason' => 'token invalid']);
            return;
        }

        echo json_encode([
            'status'      => true,
            'device_id'   => $device['id'],
            'device_name' => $device['name'],
            'phone'       => $device['phone'],
            'connection'  => $device['status'],
            'webhook_url' => $device['webhook_url'] ?? null
        ]);
    }

    // ─── Alias index -> panduan singkat jika diakses langsung ────────────
    public function index()
    {
        echo json_encode([
            'status'  => true,
            'service' => 'WAGTW WhatsApp Gateway API v2.0',
            'doc_url' => BASEURL . '/webhook',
            'endpoints' => [
                'POST /api/send'   => 'Kirim pesan WA (Headers: Authorization: TOKEN, Body: target, message)',
                'GET  /api/status' => 'Cek status device (Headers: Authorization: TOKEN)'
            ]
        ]);
    }
}
