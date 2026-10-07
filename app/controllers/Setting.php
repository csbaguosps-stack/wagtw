<?php

class Setting extends Controller {

    public function __construct()
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASEURL . '/auth');
            exit;
        }
    }

    public function index()
    {
        $data['title'] = 'Pengaturan WAGTW';
        $role = $_SESSION['role'] ?? 'user';
        $data['role'] = $role;
        $data['users'] = [];
        $data['admins'] = [];
        
        $settingModel = $this->model('Setting_model');
        
        if ($role === 'super_admin' || $role === 'admin') {
            $data['users'] = $settingModel->getAllUsersForAdmin($_SESSION['user_id'], $role);
            if ($role === 'super_admin') {
                $data['admins'] = $settingModel->getAdminsForDropdown();
                $data['app_settings'] = $settingModel->getAllSettings();
            }
        }

        
        // Load current user profile info
        $userModel = $this->model('User_model');
        $currentUser = $userModel->getUserByUsername($_SESSION['name']); // Fallback logic
        // But better fetch by ID directly. Assuming we can query it easily.
        $db = new Database;
        $db->query("SELECT * FROM users WHERE id = :id");
        $db->bind('id', $_SESSION['user_id']);
        $data['profile'] = $db->single();

        if (isset($_GET['ajax']) && $_GET['ajax'] == 'true') {
            $this->view('setting/index', $data);
        } else {
            $this->view('templates/header', $data);
            $this->view('templates/sidebar', $data);
            $this->view('setting/index', $data);
            $this->view('templates/footer');
        }
    }

    public function updateProfile()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'password' => $_POST['password'] ?? ''
        ];
        
        if (empty($data['name']) || empty($data['email'])) {
            echo json_encode(['status' => 'error', 'message' => 'Nama dan Email wajib diisi!']);
            return;
        }
        
        if ($this->model('Setting_model')->updateProfile($_SESSION['user_id'], $data) >= 0) {
            $_SESSION['name'] = $data['name'];
            echo json_encode(['status' => 'success', 'message' => 'Profil berhasil diperbarui.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui profil.']);
        }
    }

    public function uploadPhoto()
    {
        header('Content-Type: application/json');
        if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['status' => 'error', 'message' => 'Pilih foto yang valid!']);
            return;
        }

        $file = $_FILES['photo'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (!in_array($ext, $allowed)) {
            echo json_encode(['status' => 'error', 'message' => 'Hanya format JPG, PNG, WEBP diperbolehkan.']);
            return;
        }

        $targetDir = dirname(dirname(__DIR__)) . '/public/uploads/profiles/';
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        
        $newFilename = 'prof_' . $_SESSION['user_id'] . '_' . time() . '.jpg';
        $targetFile = $targetDir . $newFilename;

        // Compress and convert to JPG
        if ($this->compressImage($file['tmp_name'], $targetFile, 60)) {
            // Update DB
            $this->model('Setting_model')->updateProfilePicture($_SESSION['user_id'], $newFilename);
            $_SESSION['profile_picture'] = $newFilename;
            echo json_encode(['status' => 'success', 'message' => 'Foto profil berhasil diunggah.', 'filename' => $newFilename]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal memproses gambar.']);
        }
    }

    private function compressImage($source, $destination, $quality)
    {
        $info = getimagesize($source);
        if ($info['mime'] == 'image/jpeg') 
            $image = imagecreatefromjpeg($source);
        elseif ($info['mime'] == 'image/gif') 
            $image = imagecreatefromgif($source);
        elseif ($info['mime'] == 'image/png') 
            $image = imagecreatefrompng($source);
        elseif ($info['mime'] == 'image/webp')
            $image = imagecreatefromwebp($source);
        else return false;
        
        // Auto rotate based on EXIF
        if (function_exists('exif_read_data') && $info['mime'] == 'image/jpeg') {
            $exif = @exif_read_data($source);
            if ($exif && isset($exif['Orientation'])) {
                $ort = $exif['Orientation'];
                switch($ort) {
                    case 3: $image = imagerotate($image, 180, 0); break;
                    case 6: $image = imagerotate($image, -90, 0); break;
                    case 8: $image = imagerotate($image, 90, 0); break;
                }
            }
        }
        
        // Resize if too large
        $width = imagesx($image);
        $height = imagesy($image);
        $maxSize = 400; // Small profile picture
        
        if ($width > $maxSize || $height > $maxSize) {
            $ratio = min($maxSize / $width, $maxSize / $height);
            $newWidth = $width * $ratio;
            $newHeight = $height * $ratio;
            
            $newImage = imagecreatetruecolor($newWidth, $newHeight);
            if ($info['mime'] == 'image/png' || $info['mime'] == 'image/webp') {
                imagealphablending($newImage, false);
                imagesavealpha($newImage, true);
                $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
                imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
            } else {
                $white = imagecolorallocate($newImage, 255, 255, 255);
                imagefill($newImage, 0, 0, $white);
            }
            imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            $image = $newImage;
        }

        imagejpeg($image, $destination, $quality);
        return true;
    }

    public function getUser()
    {
        header('Content-Type: application/json');
        $role = $_SESSION['role'] ?? 'user';
        if ($role === 'user') return;
        
        $id = intval($_GET['id'] ?? 0);
        $user = $this->model('Setting_model')->getUserById($id, $_SESSION['user_id'], $role);
        
        if ($user) {
            unset($user['password']); // don't send password hash
            echo json_encode(['status' => 'success', 'data' => $user]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'User tidak ditemukan atau tidak ada akses.']);
        }
    }

    public function addUser()
    {
        header('Content-Type: application/json');
        $role = $_SESSION['role'] ?? 'user';
        if ($role === 'user') return;
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'username' => trim($_POST['username'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'role' => $_POST['role'] ?? 'user',
            'parent_id' => $_POST['parent_id'] ?? null,
            'status' => $_POST['status'] ?? 'active'
        ];
        
        if (empty($data['name']) || empty($data['username']) || empty($data['password'])) {
            echo json_encode(['status' => 'error', 'message' => 'Nama, Username, dan Password wajib diisi!']);
            return;
        }
        
        // Role check
        if ($role === 'admin') {
            $data['role'] = 'user'; // Admin can only create users
            $data['parent_id'] = $_SESSION['user_id']; // Users created by Admin belong to Admin
        } else if ($role === 'super_admin') {
            if ($data['role'] === 'super_admin') $data['role'] = 'admin'; // Prevent creating another super_admin from UI
            if (empty($data['parent_id'])) $data['parent_id'] = null;
        }
        
        // Check duplicate username/email
        $db = new Database;
        $db->query("SELECT id FROM users WHERE username = :username OR email = :email");
        $db->bind('username', $data['username']);
        $db->bind('email', $data['email']);
        if ($db->single()) {
            echo json_encode(['status' => 'error', 'message' => 'Username atau Email sudah dipakai!']);
            return;
        }
        
        if ($this->model('Setting_model')->addUser($data) > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Pengguna berhasil ditambahkan.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menambahkan pengguna.']);
        }
    }

    public function editUser()
    {
        header('Content-Type: application/json');
        $role = $_SESSION['role'] ?? 'user';
        if ($role === 'user') return;
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        
        $id = intval($_POST['id'] ?? 0);
        $userObj = $this->model('Setting_model')->getUserById($id, $_SESSION['user_id'], $role);
        if (!$userObj) {
            echo json_encode(['status' => 'error', 'message' => 'Akses ditolak atau User tidak ditemukan.']);
            return;
        }
        
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'username' => trim($_POST['username'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'role' => $_POST['role'] ?? 'user',
            'parent_id' => $_POST['parent_id'] ?? null,
            'status' => $_POST['status'] ?? 'active'
        ];
        
        if (empty($data['name']) || empty($data['username'])) {
            echo json_encode(['status' => 'error', 'message' => 'Nama dan Username wajib diisi!']);
            return;
        }
        
        // Checks
        if ($userObj['role'] === 'super_admin') {
            // Akun Super Admin tidak boleh diturunkan role-nya atau dinonaktifkan
            $data['role']      = 'super_admin';
            $data['status']    = 'active';
            $data['parent_id'] = null;
        } else if ($role === 'admin') {
            $data['role'] = 'user'; // Admin cannot elevate privilege
            $data['parent_id'] = $_SESSION['user_id'];
        } else if ($role === 'super_admin') {
            if ($data['role'] === 'super_admin') $data['role'] = 'admin';
            if (empty($data['parent_id'])) $data['parent_id'] = null;
        }
        
        if ($this->model('Setting_model')->updateUser($id, $data) >= 0) {
            echo json_encode(['status' => 'success', 'message' => 'Pengguna berhasil diperbarui.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui pengguna.']);
        }
    }

    public function deleteUser()
    {
        header('Content-Type: application/json');
        $role = $_SESSION['role'] ?? 'user';
        if ($role === 'user') return;
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        
        $id = intval($_POST['id'] ?? 0);
        if (!$id) {
            echo json_encode(['status' => 'error', 'message' => 'ID pengguna tidak valid.']);
            return;
        }

        // Cegah menghapus akun sendiri yang sedang aktif login
        if ($id === intval($_SESSION['user_id'] ?? 0)) {
            echo json_encode(['status' => 'error', 'message' => 'Anda tidak dapat menghapus akun Anda sendiri!']);
            return;
        }

        $userObj = $this->model('Setting_model')->getUserById($id, $_SESSION['user_id'], $role);
        if (!$userObj) {
            echo json_encode(['status' => 'error', 'message' => 'Akses ditolak atau User tidak ditemukan.']);
            return;
        }

        // PROTEKSI KRITIS: Super Admin dilindungi sistem dan tidak dapat dihapus
        if ($userObj['role'] === 'super_admin') {
            echo json_encode(['status' => 'error', 'message' => 'Tindakan Ditolak: Akun Super Admin dilindungi oleh sistem dan tidak dapat dihapus!']);
            return;
        }
        
        if ($this->model('Setting_model')->deleteUser($id) > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Pengguna berhasil dihapus.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus pengguna.']);
        }
    }

    // ─── Super Admin: Upload & Ubah Logo / Thumbnail ─────────────────────────
    public function uploadLogo()
    {
        header('Content-Type: application/json');
        $role = $_SESSION['role'] ?? 'user';
        if ($role !== 'super_admin') {
            echo json_encode(['status' => 'error', 'message' => 'Akses ditolak. Hanya Super Admin yang dapat mengubah logo.']);
            return;
        }

        if (!isset($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['status' => 'error', 'message' => 'Pilih file logo yang valid!']);
            return;
        }

        $file = $_FILES['logo'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'svg', 'gif', 'ico'];

        if (!in_array($ext, $allowed)) {
            echo json_encode(['status' => 'error', 'message' => 'Format logo harus berupa JPG, PNG, WEBP, SVG, atau GIF.']);
            return;
        }

        $targetDir = dirname(dirname(__DIR__)) . '/public/uploads/branding/';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $newFilename = 'logo_' . time() . '.' . $ext;
        $targetFile = $targetDir . $newFilename;

        $success = false;
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $success = $this->compressLogo($file['tmp_name'], $targetFile, $ext);
        } else {
            $success = move_uploaded_file($file['tmp_name'], $targetFile);
        }

        if ($success) {
            $this->model('Setting_model')->updateSetting('app_logo', $newFilename);
            $_SESSION['app_logo'] = $newFilename;
            echo json_encode([
                'status' => 'success',
                'message' => 'Logo sistem berhasil diperbarui!',
                'filename' => $newFilename,
                'logo_url' => BASEURL . '/uploads/branding/' . $newFilename
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal memproses dan menyimpan file logo.']);
        }
    }

    // ─── Super Admin: Ubah Pengaturan Branding ────────────────────────────────
    public function updateBranding()
    {
        header('Content-Type: application/json');
        $role = $_SESSION['role'] ?? 'user';
        if ($role !== 'super_admin') {
            echo json_encode(['status' => 'error', 'message' => 'Akses ditolak. Hanya Super Admin yang dapat mengubah branding.']);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $appName = trim($_POST['app_name'] ?? '');
        if (empty($appName)) {
            echo json_encode(['status' => 'error', 'message' => 'Nama aplikasi tidak boleh kosong.']);
            return;
        }

        $this->model('Setting_model')->updateSetting('app_name', $appName);
        $_SESSION['app_name'] = $appName;

        echo json_encode(['status' => 'success', 'message' => 'Pengaturan branding berhasil disimpan!']);
    }

    private function compressLogo($source, $destination, $ext)
    {
        if (!function_exists('imagecreatefromstring')) {
            return move_uploaded_file($source, $destination);
        }

        $fileData = file_get_contents($source);
        if ($fileData === false) return false;

        $img = @imagecreatefromstring($fileData);
        if (!$img) {
            return move_uploaded_file($source, $destination);
        }

        $width = imagesx($img);
        $height = imagesy($img);
        $maxSize = 600;

        if ($width > $maxSize || $height > $maxSize) {
            $ratio = min($maxSize / $width, $maxSize / $height);
            $newWidth = (int)($width * $ratio);
            $newHeight = (int)($height * $ratio);

            $newImg = imagecreatetruecolor($newWidth, $newHeight);
            if ($ext === 'png' || $ext === 'webp') {
                imagealphablending($newImg, false);
                imagesavealpha($newImg, true);
                $transparent = imagecolorallocatealpha($newImg, 255, 255, 255, 127);
                imagefilledrectangle($newImg, 0, 0, $newWidth, $newHeight, $transparent);
            }
            imagecopyresampled($newImg, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            $img = $newImg;
        }

        if ($ext === 'png') {
            imagealphablending($img, false);
            imagesavealpha($img, true);
            return imagepng($img, $destination, 8);
        } elseif ($ext === 'webp' && function_exists('imagewebp')) {
            return imagewebp($img, $destination, 85);
        } else {
            return imagejpeg($img, $destination, 85);
        }
    }
}

