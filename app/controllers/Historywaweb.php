<?php
/**
 * WAGTW - History WA Web Controller
 * Provides an authentic WhatsApp Web experience with live chat history, contact feeds, and instant replies.
 * 
 * Coding by cs.baguosps@gmail.com
 * Copyright (c) 2024-2026. All rights reserved.
 */

class Historywaweb extends Controller {

    public function __construct()
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASEURL . '/auth');
            exit;
        }
    }

    /**
     * Tampilan utama History WA Web
     */
    public function index()
    {
        $data['title'] = 'History WA Web - WAGTW';
        
        $model = $this->model('HistoryWaWeb_model');
        $userId = $this->getVisibleUserIdsString();
        
        $data['devices'] = $model->getDevices($userId);
        
        if (isset($_GET['ajax']) && $_GET['ajax'] == 'true') {
            $this->view('historywaweb/index', $data);
        } else {
            $this->view('templates/header', $data);
            $this->view('templates/sidebar', $data);
            $this->view('historywaweb/index', $data);
            $this->view('templates/footer');
        }
    }

    /**
     * AJAX: Ambil daftar percakapan (Chat list di panel kiri)
     */
    public function getConversations()
    {
        header('Content-Type: application/json');
        
        $model    = $this->model('HistoryWaWeb_model');
        $userId   = $this->getVisibleUserIdsString();
        $deviceId = intval($_GET['device_id'] ?? 0);
        $search   = trim($_GET['search'] ?? '');

        $rawList = $model->getConversations($userId, $deviceId, $search);
        $conversations = [];

        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        $avatarGradients = [
            'bg-gradient-to-tr from-emerald-500 to-teal-600',
            'bg-gradient-to-tr from-blue-500 to-cyan-600',
            'bg-gradient-to-tr from-indigo-500 to-purple-600',
            'bg-gradient-to-tr from-violet-500 to-fuchsia-600',
            'bg-gradient-to-tr from-amber-500 to-orange-600',
            'bg-gradient-to-tr from-rose-500 to-pink-600',
            'bg-gradient-to-tr from-teal-500 to-emerald-700'
        ];

        foreach ($rawList as $c) {
            $contact = trim($c['contact_phone']);
            if (empty($contact)) continue;

            // Format tanggal/jam
            $createdAt = $c['last_created_at'];
            $msgDate   = date('Y-m-d', strtotime($createdAt));
            if ($msgDate === $today) {
                $timeFormatted = date('H:i', strtotime($createdAt));
            } elseif ($msgDate === $yesterday) {
                $timeFormatted = 'Kemarin';
            } else {
                $timeFormatted = date('d/m/y', strtotime($createdAt));
            }

            // Warna Avatar unik konsisten berdasarkan nomor HP
            $hash = crc32($contact);
            $gradientIndex = abs($hash) % count($avatarGradients);
            $avatarColor = $avatarGradients[$gradientIndex];

            // Inisial avatar (2 digit terakhir nomor atau ikon)
            $cleanNum = preg_replace('/[^0-9]/', '', $contact);
            $avatarText = strlen($cleanNum) >= 2 ? substr($cleanNum, -2) : 'WA';

            // Format nomor kontak tampil
            $formattedPhone = $this->formatPhoneNumber($contact);

            // Preview teks pesan
            $msgText = $c['last_message'] ?? '';
            $msgPreview = mb_substr($msgText, 0, 48) . (mb_strlen($msgText) > 48 ? '...' : '');

            $conversations[] = [
                'device_id'       => (int)$c['device_id'],
                'device_name'     => $c['device_name'] ?: 'Device #' . $c['device_id'],
                'device_phone'    => $c['device_phone'] ?: '',
                'device_status'   => $c['device_status'] ?? 'disconnected',
                'contact_phone'   => $contact,
                'contact_display' => $formattedPhone,
                'avatar_color'    => $avatarColor,
                'avatar_text'     => $avatarText,
                'last_message'    => $msgPreview,
                'last_msg_type'   => $c['last_msg_type'] ?? 'text',
                'last_direction'  => $c['last_direction'],
                'last_status'     => $c['last_status'] ?? 'sent',
                'time_formatted'  => $timeFormatted,
                'raw_time'        => $createdAt,
                'unread_count'    => (int)$c['unread_count'],
                'total_messages'  => (int)$c['total_messages']
            ];
        }

        echo json_encode([
            'status' => 'success',
            'count'  => count($conversations),
            'data'   => $conversations
        ]);
    }

    /**
     * AJAX: Ambil histori pesan lengkap untuk suatu kontak (Panel kanan chat)
     */
    public function getMessages()
    {
        header('Content-Type: application/json');

        $model    = $this->model('HistoryWaWeb_model');
        $userId   = $this->getVisibleUserIdsString();
        $deviceId = intval($_GET['device_id'] ?? 0);
        $contact  = trim($_GET['contact'] ?? '');
        $afterId  = intval($_GET['after_id'] ?? 0);

        if (empty($contact)) {
            echo json_encode(['status' => 'error', 'message' => 'Parameter contact wajib diisi!']);
            return;
        }

        // Tandai pesan sebagai sudah dibaca jika bukan request polling after_id
        if ($afterId === 0) {
            $model->markAsRead($userId, $deviceId, $contact);
        }

        $device = ($deviceId > 0) ? $model->getDeviceById($deviceId, $userId) : null;
        $rawMessages = $model->getMessages($userId, $deviceId, $contact, 200, $afterId);

        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        $formatted = [];
        foreach ($rawMessages as $m) {
            $created = $m['created_at'];
            $dateOnly = date('Y-m-d', strtotime($created));

            if ($dateOnly === $today) {
                $dateLabel = 'HARI INI';
            } elseif ($dateOnly === $yesterday) {
                $dateLabel = 'KEMARIN';
            } else {
                $dateLabel = strtoupper(date('d F Y', strtotime($created)));
            }

            $formatted[] = [
                'id'            => (int)$m['id'],
                'wa_msg_id'     => $m['wa_msg_id'] ?? null,
                'direction'     => $m['direction'], // 'in' atau 'out'
                'message'       => $m['message'] ?? '',
                'msg_type'      => $m['msg_type'] ?? 'text',
                'status'        => $m['status'] ?? 'sent',
                'created_at'    => $created,
                'time'          => date('H:i', strtotime($created)),
                'date_key'      => $dateOnly,
                'date_label'    => $dateLabel,
                'from_phone'    => $m['from_phone'],
                'to_phone'      => $m['to_phone']
            ];
        }

        echo json_encode([
            'status'   => 'success',
            'contact'  => $contact,
            'contact_display' => $this->formatPhoneNumber($contact),
            'device'   => [
                'id'        => $device['id'] ?? $deviceId,
                'name'      => $device['name'] ?? 'Device #' . $deviceId,
                'phone'     => $device['phone'] ?? '',
                'status'    => $device['status'] ?? 'disconnected'
            ],
            'messages' => $formatted
        ]);
    }

    /**
     * AJAX: Kirim balasan langsung dari WhatsApp Web UI
     */
    public function sendMessage()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $deviceId = intval($_POST['device_id'] ?? 0);
        $contact  = trim($_POST['contact'] ?? '');
        $message  = trim($_POST['message'] ?? '');

        if (!$deviceId || empty($contact) || empty($message)) {
            echo json_encode(['status' => 'error', 'message' => 'Device, nomor kontak, dan isi pesan wajib diisi!']);
            return;
        }

        $cleanTo = preg_replace('/[^0-9]/', '', $contact);
        $userId  = $this->getVisibleUserIdsString();
        $device  = $this->model('HistoryWaWeb_model')->getDeviceById($deviceId, $userId);

        if (!$device) {
            echo json_encode(['status' => 'error', 'message' => 'Device WhatsApp tidak ditemukan!']);
            return;
        }

        if (empty($device['session_id'])) {
            echo json_encode(['status' => 'error', 'message' => 'Session WhatsApp pada device ini tidak valid!']);
            return;
        }

        // Panggil Node.js engine Baileys untuk mengirim pesan
        $nodeResult = $this->callNode('POST', '/api/send', [
            'sessionId' => $device['session_id'],
            'to'        => $cleanTo,
            'message'   => $message,
        ]);

        if ($nodeResult === null) {
            echo json_encode(['status' => 'error', 'message' => 'WhatsApp Gateway Engine offline atau tidak merespon.']);
            return;
        }

        if (isset($nodeResult['status']) && $nodeResult['status'] === 'success') {
            echo json_encode([
                'status'  => 'success',
                'message' => 'Pesan berhasil terkirim!',
                'data'    => $nodeResult
            ]);
        } else {
            $errMsg = $nodeResult['message'] ?? 'Gagal mengirim pesan melalui WhatsApp Gateway.';
            echo json_encode(['status' => 'error', 'message' => $errMsg]);
        }
    }

    /**
     * AJAX: Hapus seluruh riwayat percakapan dengan kontak tertentu
     */
    public function deleteConversation()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $deviceId = intval($_POST['device_id'] ?? 0);
        $contact  = trim($_POST['contact'] ?? '');
        $userId   = $this->getVisibleUserIdsString();

        if (!$deviceId || empty($contact)) {
            echo json_encode(['status' => 'error', 'message' => 'Parameter tidak valid!']);
            return;
        }

        $deleted = $this->model('HistoryWaWeb_model')->deleteConversation($userId, $deviceId, $contact);
        if ($deleted > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Percakapan berhasil dihapus!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Percakapan tidak ditemukan atau sudah dihapus.']);
        }
    }

    /**
     * AJAX: Hapus satu pesan spesifik
     */
    public function deleteMessage()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $id     = intval($_POST['id'] ?? 0);
        $userId = $this->getVisibleUserIdsString();

        if (!$id) {
            echo json_encode(['status' => 'error', 'message' => 'ID pesan tidak valid!']);
            return;
        }

        $deleted = $this->model('HistoryWaWeb_model')->deleteMessage($id, $userId);
        if ($deleted > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Pesan berhasil dihapus!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus pesan.']);
        }
    }

    /**
     * Helper pemformat nomor telepon agar nyaman dibaca ala WhatsApp
     */
    private function formatPhoneNumber($phone)
    {
        $digits = preg_replace('/[^0-9]/', '', $phone);
        if (substr($digits, 0, 2) === '62') {
            return '+62 ' . substr($digits, 2, 3) . '-' . substr($digits, 5, 4) . '-' . substr($digits, 9);
        }
        return '+' . $digits;
    }
}
