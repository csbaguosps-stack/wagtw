<?php

class MessageHistory extends Controller {

    public function __construct()
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASEURL . '/auth');
            exit;
        }
    }

    public function index()
    {
        $data['title'] = 'Message History - WAGTW';
        
        // Ambil daftar device untuk filter
        $db = new Database;
        $db->query("SELECT id, name, phone FROM devices WHERE user_id IN (" . $this->getVisibleUserIdsString() . ")");
        $data['devices'] = $db->resultSet();
        
        if (isset($_GET['ajax']) && $_GET['ajax'] == 'true') {
            $this->view('messagehistory/index', $data);
        } else {
            $this->view('templates/header', $data);
            $this->view('templates/sidebar', $data);
            $this->view('messagehistory/index', $data);
            $this->view('templates/footer');
        }
    }

    public function ajax_list()
    {
        header('Content-Type: application/json');
        
        $model = $this->model('MessageHistory_model');
        $userId = $this->getVisibleUserIdsString();
        $postData = $_POST;

        $list = $model->getDatatables($userId, $postData);
        $data = [];

        foreach ($list as $msg) {
            $row = [];
            
            // Checkbox for bulk delete
            $row[] = '<input type="checkbox" class="cb-child" value="'.$msg['id'].'">';
            
            // ID
            $row[] = $msg['id'];
            
            // Time
            $row[] = date('d/m/Y H:i', strtotime($msg['created_at']));
            
            // Device
            $row[] = $msg['device_phone'] ? $msg['device_phone'] : '-';
            
            // Target
            $target = $msg['direction'] === 'out' ? $msg['to_phone'] : $msg['from_phone'];
            $row[] = $target;
            
            // Contact
            $row[] = 'unknown'; // Nomor asli disembunyikan WhatsApp
            // Type
            $row[] = $msg['msg_type'] ?? 'text';
            
            // Message
            $message = $msg['message'] ?? '';
            $messagePreview = mb_substr($message, 0, 100) . (mb_strlen($message) > 100 ? '...' : '');
            $messageAttributes = [
                'message' => $message,
                'time' => date('d/m/Y H:i', strtotime($msg['created_at'])),
                'device' => $msg['device_phone'] ?: '-',
                'direction' => $msg['direction'] === 'out' ? 'Keluar' : 'Masuk',
                'number' => $target ?: '-',
                'type' => $msg['msg_type'] ?? 'text',
                'status' => $msg['status'] ?? '-',
            ];
            $messageButton = '<button type="button" class="message-detail-trigger max-w-full text-left text-emerald-700 hover:text-emerald-900 hover:underline cursor-pointer"';
            foreach ($messageAttributes as $attribute => $value) {
                $messageButton .= ' data-' . $attribute . '="' . htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';
            }
            $row[] = $messageButton . ' title="Klik untuk melihat detail pesan">' . htmlspecialchars($messagePreview, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</button>';
            
            // Url
            $row[] = ''; 
            
            // Status
            $statusBadge = '';
            switch($msg['status']) {
                case 'sent': $statusBadge = '<span class="text-xs bg-gray-100 text-gray-700 px-2 py-1 rounded">sent</span>'; break;
                case 'delivered': $statusBadge = '<span class="text-xs bg-blue-50 text-blue-600 px-2 py-1 rounded">delivered</span>'; break;
                case 'read': $statusBadge = '<span class="text-xs bg-emerald-50 text-emerald-600 px-2 py-1 rounded">read</span>'; break;
                default: $statusBadge = '<span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded">'.$msg['status'].'</span>';
            }
            $row[] = $statusBadge;
            
            // State
            $stateLabel = $msg['direction'] === 'out' ? 'out' : 'in';
            $stateClass = $msg['direction'] === 'out' ? 'bg-orange-50 text-orange-600' : 'bg-purple-50 text-purple-600';
            $row[] = '<span class="text-xs '.$stateClass.' px-2 py-1 rounded">'.$stateLabel.'</span>';
            
            // Action
            $btnResend = '';
            if ($msg['direction'] === 'out') {
                $btnResend = '<button onclick="resendMessage('.$msg['id'].')" class="text-xs bg-emerald-500 text-white px-2 py-1 rounded mr-1 hover:bg-emerald-600"><i class="fas fa-paper-plane mr-1"></i>Resend</button>';
            }
            $btnDelete = '<button onclick="deleteMessage('.$msg['id'].')" class="text-xs bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600"><i class="fas fa-trash mr-1"></i>Delete</button>';
            
            $row[] = '<div class="flex items-center">' . $btnResend . $btnDelete . '</div>';
            
            $data[] = $row;
        }

        $output = [
            "draw" => isset($_POST['draw']) ? intval($_POST['draw']) : 0,
            "recordsTotal" => $model->countAll($userId),
            "recordsFiltered" => $model->countFiltered($userId, $postData),
            "data" => $data,
        ];
        
        echo json_encode($output);
    }

    public function delete()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        
        $id = intval($_POST['id'] ?? 0);
        $deleted = $this->model('MessageHistory_model')->deleteMessage($id, $this->getVisibleUserIdsString());
        
        echo json_encode(['status' => $deleted ? 'success' : 'error']);
    }

    public function bulk_delete()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        
        $ids = $_POST['ids'] ?? [];
        if (empty($ids)) {
            echo json_encode(['status' => 'error', 'message' => 'Tidak ada pesan yang dipilih']);
            return;
        }
        
        $deleted = $this->model('MessageHistory_model')->bulkDelete($ids, $this->getVisibleUserIdsString());
        echo json_encode(['status' => 'success', 'message' => "$deleted pesan berhasil dihapus"]);
    }

    public function delete_all()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        
        $deleted = $this->model('MessageHistory_model')->deleteAll($this->getVisibleUserIdsString());
        echo json_encode(['status' => 'success', 'message' => "Semua ($deleted) riwayat pesan berhasil dihapus"]);
    }
    
    public function resend()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        
        $id = intval($_POST['id'] ?? 0);
        $msg = $this->model('MessageHistory_model')->getMessageById($id, $this->getVisibleUserIdsString());
        
        if (!$msg || $msg['direction'] !== 'out') {
            echo json_encode(['status' => 'error', 'message' => 'Pesan tidak valid untuk dikirim ulang.']);
            return;
        }
        
        // Kirim API ke Node.js
        $sessionId = $msg['session_id'];
        $to = $msg['to_phone'];
        $message = $msg['message'];
        
        $nodeUrl  = defined('WA_ENGINE_URL') ? WA_ENGINE_URL : 'http://127.0.0.1:3001';
        $apiToken = defined('WA_API_TOKEN') ? WA_API_TOKEN : 'wagtw_secret_token_2024';
        
        $url = $nodeUrl . '/api/send'; 
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-Api-Token: ' . $apiToken
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'sessionId' => $sessionId,
            'to'        => $to,
            'message'   => $message,
        ]));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $httpCode >= 400) {
            echo json_encode(['status' => 'error', 'message' => 'Gagal terhubung ke engine Node.js']);
            return;
        }
        
        echo json_encode(['status' => 'success', 'message' => 'Pesan berhasil dikirim ulang!']);
    }

    public function check_new()
    {
        header('Content-Type: application/json');
        $sinceId = intval($_GET['since_id'] ?? 0);
        $userId = $this->getVisibleUserIdsString();
        
        $db = new Database;
        if ($sinceId > 0) {
            $db->query("SELECT id, session_id, from_phone, to_phone, message, direction, status, created_at FROM messages WHERE user_id IN ($userId) AND id > :since_id ORDER BY id DESC LIMIT 10");
            $db->bind('since_id', $sinceId);
            $rows = $db->resultSet();
            $latestId = !empty($rows) ? intval($rows[0]['id']) : $sinceId;
            echo json_encode([
                'status' => 'success',
                'latest_id' => $latestId,
                'has_new' => !empty($rows),
                'count' => count($rows),
                'new_messages' => $rows
            ]);
        } else {
            $db->query("SELECT id FROM messages WHERE user_id IN ($userId) ORDER BY id DESC LIMIT 1");
            $single = $db->single();
            $latestId = $single ? intval($single['id']) : 0;
            echo json_encode([
                'status' => 'success',
                'latest_id' => $latestId,
                'has_new' => false,
                'count' => 0,
                'new_messages' => []
            ]);
        }
    }

    public function export()
    {
        header('Content-Type: application/json');
        $userId = $this->getVisibleUserIdsString();
        $deviceId = isset($_GET['device_id']) && $_GET['device_id'] !== '' ? intval($_GET['device_id']) : null;
        
        $db = new Database;
        $sql = "SELECT m.id, m.created_at, d.phone as device_phone, m.from_phone, m.to_phone, m.msg_type, m.message, m.status, m.direction
                FROM messages m 
                LEFT JOIN devices d ON d.id = m.device_id 
                WHERE m.user_id IN ($userId)";
        if ($deviceId) {
            $sql .= " AND m.device_id = $deviceId";
        }
        $sql .= " ORDER BY m.id DESC LIMIT 10000";
        $db->query($sql);
        $list = $db->resultSet();
        
        echo json_encode([
            'status' => 'success',
            'data'   => $list
        ]);
    }

    public function download_export($format = 'csv')
    {
        $userId = $this->getVisibleUserIdsString();
        $deviceId = isset($_GET['device_id']) && $_GET['device_id'] !== '' ? intval($_GET['device_id']) : null;
        
        $db = new Database;
        $sql = "SELECT m.id, m.created_at, d.phone as device_phone, m.from_phone, m.to_phone, m.msg_type, m.message, m.status, m.direction
                FROM messages m 
                LEFT JOIN devices d ON d.id = m.device_id 
                WHERE m.user_id IN ($userId)";
        if ($deviceId) {
            $sql .= " AND m.device_id = $deviceId";
        }
        $sql .= " ORDER BY m.id DESC LIMIT 10000";
        $db->query($sql);
        $list = $db->resultSet();
        
        $dateStr = date('Y-m-d_His');
        $format = strtolower($format);
        
        if ($format === 'csv') {
            $filename = "Riwayat_Pesan_{$dateStr}.csv";
            header('Content-Description: File Transfer');
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            
            // UTF-8 BOM for Excel compatibility
            echo "\xEF\xBB\xBF";
            
            $output = fopen('php://output', 'w');
            fputcsv($output, ['ID', 'Waktu', 'Device Pengirim', 'Nomor Tujuan / Sumber', 'Tipe', 'Isi Pesan', 'Arah', 'Status']);
            
            foreach ($list as $m) {
                $target = $m['direction'] === 'out' ? $m['to_phone'] : $m['from_phone'];
                $dir = $m['direction'] === 'out' ? 'Keluar (Out)' : 'Masuk (In)';
                fputcsv($output, [
                    $m['id'],
                    $m['created_at'],
                    $m['device_phone'] ? "'" . $m['device_phone'] : '-',
                    $target ? "'" . $target : '-',
                    $m['msg_type'] ?? 'text',
                    $m['message'] ?? '',
                    $dir,
                    $m['status'] ?? ''
                ]);
            }
            fclose($output);
            exit;
        } else {
            $filename = "Riwayat_Pesan_{$dateStr}.xls";
            header('Content-Type: application/vnd.ms-excel; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            
            echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";
            echo ' <Styles>' . "\n";
            echo '  <Style ss:ID="Header"><Font ss:Bold="1" ss:Color="#FFFFFF"/><Interior ss:Color="#10B981" ss:Pattern="Solid"/></Style>' . "\n";
            echo '  <Style ss:ID="StringStyle"><NumberFormat ss:Format="@"/></Style>' . "\n";
            echo ' </Styles>' . "\n";
            echo ' <Worksheet ss:Name="Riwayat Pesan">' . "\n";
            echo '  <Table>' . "\n";
            echo '   <Column ss:Width="50"/>' . "\n";
            echo '   <Column ss:Width="120"/>' . "\n";
            echo '   <Column ss:Width="120"/>' . "\n";
            echo '   <Column ss:Width="130"/>' . "\n";
            echo '   <Column ss:Width="70"/>' . "\n";
            echo '   <Column ss:Width="260"/>' . "\n";
            echo '   <Column ss:Width="90"/>' . "\n";
            echo '   <Column ss:Width="80"/>' . "\n";
            
            echo '   <Row ss:StyleID="Header">' . "\n";
            foreach (['ID', 'Waktu', 'Device Pengirim', 'Nomor Tujuan / Sumber', 'Tipe', 'Isi Pesan', 'Arah', 'Status'] as $h) {
                echo '    <Cell><Data ss:Type="String">' . htmlspecialchars($h) . '</Data></Cell>' . "\n";
            }
            echo '   </Row>' . "\n";
            
            foreach ($list as $m) {
                $target = $m['direction'] === 'out' ? $m['to_phone'] : $m['from_phone'];
                $dir = $m['direction'] === 'out' ? 'Keluar (Out)' : 'Masuk (In)';
                echo '   <Row>' . "\n";
                echo '    <Cell><Data ss:Type="Number">' . intval($m['id']) . '</Data></Cell>' . "\n";
                echo '    <Cell ss:StyleID="StringStyle"><Data ss:Type="String">' . htmlspecialchars($m['created_at'] ?? '') . '</Data></Cell>' . "\n";
                echo '    <Cell ss:StyleID="StringStyle"><Data ss:Type="String">' . htmlspecialchars($m['device_phone'] ?? '-') . '</Data></Cell>' . "\n";
                echo '    <Cell ss:StyleID="StringStyle"><Data ss:Type="String">' . htmlspecialchars($target ?? '-') . '</Data></Cell>' . "\n";
                echo '    <Cell><Data ss:Type="String">' . htmlspecialchars($m['msg_type'] ?? 'text') . '</Data></Cell>' . "\n";
                echo '    <Cell><Data ss:Type="String">' . htmlspecialchars($m['message'] ?? '') . '</Data></Cell>' . "\n";
                echo '    <Cell><Data ss:Type="String">' . htmlspecialchars($dir) . '</Data></Cell>' . "\n";
                echo '    <Cell><Data ss:Type="String">' . htmlspecialchars($m['status'] ?? '') . '</Data></Cell>' . "\n";
                echo '   </Row>' . "\n";
            }
            echo '  </Table>' . "\n";
            echo ' </Worksheet>' . "\n";
            echo '</Workbook>' . "\n";
            exit;
        }
    }
}
