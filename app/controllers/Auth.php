<?php

class Auth extends Controller {
    public function index()
    {
        if(isset($_SESSION['user_id'])) {
            header('Location: ' . BASEURL . '/dashboard');
            exit;
        }

        $data['title'] = 'Login WAGTW';
        $this->view('auth/login', $data);
    }

    public function doLogin()
    {
        // Set header to JSON for AJAX
        header('Content-Type: application/json');

        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';

            if(empty($username) || empty($password)) {
                echo json_encode(['status' => 'error', 'message' => 'Username dan password tidak boleh kosong!']);
                return;
            }

            $user = $this->model('User_model')->checkUser($username, $password);

            if($user) {
                if($user['status'] !== 'active') {
                    echo json_encode(['status' => 'error', 'message' => 'Akun tidak aktif. Hubungi admin.']);
                    return;
                }

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['parent_id'] = $user['parent_id'];
                $_SESSION['profile_picture'] = $user['profile_picture'];

                echo json_encode(['status' => 'success', 'message' => 'Login berhasil!', 'redirect' => BASEURL . '/dashboard']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Username atau Password salah!']);
            }
        }
    }

    public function logout()
    {
        session_destroy();
        header('Location: ' . BASEURL . '/auth');
        exit;
    }
}
