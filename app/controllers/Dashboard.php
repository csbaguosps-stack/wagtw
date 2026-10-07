<?php

class Dashboard extends Controller {
    public function __construct()
    {
        if(!isset($_SESSION['user_id'])) {
            header('Location: ' . BASEURL . '/auth');
            exit;
        }
    }

    public function index()
    {
        $data['title'] = 'Dashboard WAGTW';
        $data['user_name'] = $_SESSION['name'] ?? 'Admin';

        // Get Devices Stats
        $db = new Database;
        $visibleIds = $this->getVisibleUserIdsString();
        
        $db->query("SELECT * FROM devices WHERE user_id IN ($visibleIds)");
        $data['devices'] = $db->resultSet();

        $db->query("SELECT status, count(*) as count FROM messages WHERE user_id IN ($visibleIds) GROUP BY status");
        $msgs = $db->resultSet();

        $stats = ['sent' => 0, 'failed' => 0, 'received' => 0];
        foreach($msgs as $m) {
            if($m['status'] == 'sent' || $m['status'] == 'delivered' || $m['status'] == 'read') $stats['sent'] += $m['count'];
            if($m['status'] == 'failed') $stats['failed'] += $m['count'];
        }

        // Received messages
        $db->query("SELECT count(*) as count FROM messages WHERE user_id IN ($visibleIds) AND direction = 'in'");
        $singleRecv = $db->single();
        $stats['received'] = $singleRecv ? intval($singleRecv['count']) : 0;

        $data['stats'] = $stats;

        if(isset($_GET['ajax']) && $_GET['ajax'] == 'true') {
            $this->view('dashboard/index', $data);
        } else {
            $this->view('templates/header', $data);
            $this->view('templates/sidebar', $data);
            $this->view('dashboard/index', $data);
            $this->view('templates/footer');
        }
    }

    public function stats_ajax()
    {
        header('Content-Type: application/json');
        $db = new Database;
        $visibleIds = $this->getVisibleUserIdsString();
        
        $db->query("SELECT * FROM devices WHERE user_id IN ($visibleIds)");
        $devices = $db->resultSet();

        $db->query("SELECT status, count(*) as count FROM messages WHERE user_id IN ($visibleIds) GROUP BY status");
        $msgs = $db->resultSet();

        $stats = ['sent' => 0, 'failed' => 0, 'received' => 0];
        foreach($msgs as $m) {
            if($m['status'] == 'sent' || $m['status'] == 'delivered' || $m['status'] == 'read') $stats['sent'] += intval($m['count']);
            if($m['status'] == 'failed') $stats['failed'] += intval($m['count']);
        }

        $db->query("SELECT count(*) as count FROM messages WHERE user_id IN ($visibleIds) AND direction = 'in'");
        $singleRecv = $db->single();
        $stats['received'] = $singleRecv ? intval($singleRecv['count']) : 0;

        echo json_encode([
            'status' => 'success',
            'total_devices' => count($devices),
            'stats' => $stats,
            'devices' => $devices
        ]);
    }
}
