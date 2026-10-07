<?php

class Aitemplate extends Controller {

    public function __construct()
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASEURL . '/auth');
            exit;
        }
    }

    public function index()
    {
        $data['title'] = 'Template AI WA';
        $userIdStr = $this->getVisibleUserIdsString();

        $devices = $this->model('Device_model')->getAllDevicesByUser($userIdStr);
        $data['devices'] = $devices;

        // Tentukan device yang dipilih (default device pertama jika ada)
        $selectedDeviceId = isset($_GET['device_id']) ? intval($_GET['device_id']) : (count($devices) > 0 ? $devices[0]['id'] : 0);
        $data['selected_device_id'] = $selectedDeviceId;

        // Tentukan filter bulan dan tahun (default bulan & tahun saat ini)
        $currentMonth = isset($_GET['month']) ? ($_GET['month'] === 'all' ? 'all' : intval($_GET['month'])) : intval(date('n'));
        $currentYear  = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
        $data['current_month'] = $currentMonth;
        $data['current_year']  = $currentYear;

        // Ambil pengaturan template untuk device terpilih
        $data['templates'] = $this->model('AiData_model')->getTemplateSettings($selectedDeviceId, $userIdStr);

        // Ambil daftar kontak yang sedang di-mute untuk device terpilih dengan filter bulan & tahun
        $data['muted_contacts'] = $selectedDeviceId ? $this->model('AiData_model')->getMutedContactsByDevice($selectedDeviceId, $currentMonth, $currentYear) : [];

        // Ambil default templates untuk reset
        $data['default_templates'] = AiData_model::getDefaultTemplates();

        if (isset($_GET['ajax']) && $_GET['ajax'] == 'true') {
            $this->view('aitemplate/index', $data);
        } else {
            $this->view('templates/header', $data);
            $this->view('templates/sidebar', $data);
            $this->view('aitemplate/index', $data);
            $this->view('templates/footer');
        }
    }

    public function getSettings()
    {
        header('Content-Type: application/json');
        $deviceId = intval($_GET['device_id'] ?? 0);
        $month = isset($_GET['month']) ? ($_GET['month'] === 'all' ? 'all' : intval($_GET['month'])) : intval(date('n'));
        $year  = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
        $userIdStr = $this->getVisibleUserIdsString();

        if (!$deviceId) {
            echo json_encode(['status' => 'error', 'message' => 'Device ID tidak valid']);
            return;
        }

        $templates = $this->model('AiData_model')->getTemplateSettings($deviceId, $userIdStr);
        $mutedContacts = $this->model('AiData_model')->getMutedContactsByDevice($deviceId, $month, $year);

        echo json_encode([
            'status' => 'success',
            'templates' => $templates,
            'muted_contacts' => $mutedContacts,
            'filtered_month' => $month,
            'filtered_year'  => $year
        ]);
    }

    public function getMutedContacts()
    {
        header('Content-Type: application/json');
        $deviceId = intval($_GET['device_id'] ?? 0);
        $month = isset($_GET['month']) ? ($_GET['month'] === 'all' ? 'all' : intval($_GET['month'])) : intval(date('n'));
        $year  = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

        if (!$deviceId) {
            echo json_encode(['status' => 'error', 'message' => 'Device ID tidak valid']);
            return;
        }

        $mutedContacts = $this->model('AiData_model')->getMutedContactsByDevice($deviceId, $month, $year);
        echo json_encode([
            'status' => 'success',
            'muted_contacts' => $mutedContacts,
            'filtered_month' => $month,
            'filtered_year'  => $year
        ]);
    }

    public function save()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Metode request tidak didukung']);
            return;
        }

        $deviceId = intval($_POST['device_id'] ?? 0);
        $applyToAll = isset($_POST['apply_to_all']) && $_POST['apply_to_all'] == '1';
        $userIdStr = $this->getVisibleUserIdsString();
        $actualUserId = $_SESSION['user_id'];

        if (!$deviceId && !$applyToAll) {
            echo json_encode(['status' => 'error', 'message' => 'Silakan pilih device terlebih dahulu']);
            return;
        }

        $footerEnabled = isset($_POST['ai_footer_enabled']) ? intval($_POST['ai_footer_enabled']) : 1;
        $footerText = trim($_POST['ai_footer_text'] ?? '');
        $handoffText = trim($_POST['ai_handoff_text'] ?? '');
        $duration = trim($_POST['ai_reactivate_duration'] ?? 'next_day');
        $keyword = trim($_POST['ai_reactivate_keyword'] ?? 'akhiri percakapan, selesai, #selesai, tutup sesi, end chat');
        $message = trim($_POST['ai_reactivate_message'] ?? '');
        $notify = isset($_POST['ai_reactivate_notify']) ? intval($_POST['ai_reactivate_notify']) : 1;

        $validDurations = ['1_hour', '2_hours', '3_hours', '6_hours', '12_hours', '24_hours', 'next_day', 'manual'];
        if (!in_array($duration, $validDurations)) {
            $duration = 'next_day';
        }

        if (empty($keyword)) {
            $keyword = 'akhiri percakapan, selesai, #selesai, tutup sesi, end chat';
        }

        $saveData = [
            'ai_footer_enabled'      => $footerEnabled,
            'ai_footer_text'         => $footerText,
            'ai_handoff_text'        => $handoffText,
            'ai_reactivate_duration' => $duration,
            'ai_reactivate_keyword'  => $keyword,
            'ai_reactivate_message'  => $message,
            'ai_reactivate_notify'   => $notify
        ];

        if ($applyToAll) {
            $count = $this->model('AiData_model')->applyTemplateToAllDevices($userIdStr, $actualUserId, $saveData);
            echo json_encode([
                'status' => 'success',
                'message' => "Pengaturan Template AI berhasil diterapkan ke seluruh device ({$count} device)!"
            ]);
            return;
        }

        $this->model('AiData_model')->saveTemplateSettings($deviceId, $actualUserId, $saveData);
        echo json_encode([
            'status' => 'success',
            'message' => 'Pengaturan Template AI WA berhasil disimpan!'
        ]);
    }

    public function resetDefaults()
    {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'defaults' => AiData_model::getDefaultTemplates()
        ]);
    }

    public function unmuteContact()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Metode request tidak didukung']);
            return;
        }

        $id = intval($_POST['id'] ?? 0);
        $deviceId = intval($_POST['device_id'] ?? 0);

        if (!$id || !$deviceId) {
            echo json_encode(['status' => 'error', 'message' => 'Parameter tidak valid']);
            return;
        }

        $deleted = $this->model('AiData_model')->unmuteContactById($id, $deviceId);
        if ($deleted) {
            echo json_encode(['status' => 'success', 'message' => 'Bot AI berhasil diaktifkan kembali untuk kontak ini!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal mengaktifkan AI atau sesi kontak sudah tidak aktif']);
        }
    }

    public function deleteMutedBulk()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Metode request tidak didukung']);
            return;
        }

        $deviceId = intval($_POST['device_id'] ?? 0);
        $ids = $_POST['ids'] ?? [];

        if (!$deviceId || empty($ids) || !is_array($ids)) {
            echo json_encode(['status' => 'error', 'message' => 'Tidak ada kontak yang dipilih untuk dihapus']);
            return;
        }

        $deletedCount = $this->model('AiData_model')->deleteMutedBulk($ids, $deviceId);
        if ($deletedCount > 0) {
            echo json_encode([
                'status' => 'success',
                'message' => "Berhasil mengaktifkan kembali Bot AI untuk {$deletedCount} kontak terpilih!",
                'deleted_count' => $deletedCount
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Tidak ada kontak yang berhasil diaktifkan']);
        }
    }

    public function deleteAllMuted()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Metode request tidak didukung']);
            return;
        }

        $deviceId = intval($_POST['device_id'] ?? 0);
        $month = isset($_POST['month']) ? ($_POST['month'] === 'all' ? 'all' : intval($_POST['month'])) : null;
        $year  = isset($_POST['year']) ? intval($_POST['year']) : null;

        if (!$deviceId) {
            echo json_encode(['status' => 'error', 'message' => 'Device ID tidak valid']);
            return;
        }

        $deletedCount = $this->model('AiData_model')->deleteAllMutedContacts($deviceId, $month, $year);
        echo json_encode([
            'status' => 'success',
            'message' => "Berhasil mengaktifkan kembali Bot AI untuk seluruh kontak ({$deletedCount} kontak dibersihkan)!",
            'deleted_count' => $deletedCount
        ]);
    }
}
