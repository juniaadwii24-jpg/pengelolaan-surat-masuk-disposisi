<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class AuthController extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Auth_model', 'model');
        $this->load->library('session');
        $this->load->database();
    }

    public function index()
    {
        if ($this->session->userdata('logged_in')) {
            redirect('pengelolaan/DashboardController');
            return;
        }
        $data['flash_message'] = $this->session->flashdata('flash_message');
        $this->load->view('login', $data);
    }

    public function login()
    {
        $input    = json_decode(file_get_contents('php://input'), true);
        $username = isset($input['username']) ? trim($input['username']) : '';
        $password = isset($input['password']) ? (string) $input['password'] : '';

        if ($username === '' || $password === '') {
            echo json_encode(['status' => 'error', 'message' => 'Username dan password wajib diisi.']);
            return;
        }

        $user = $this->model->getUserByUsername($username);

        if (!$user || !password_verify($password, $user->password)) {
            echo json_encode(['status' => 'error', 'message' => 'Username atau password salah.']);
            return;
        }

        $this->session->sess_regenerate(true);
        $this->session->set_userdata([
            'logged_in' => true,
            'user_id'   => $user->id,
            'username'  => $user->username,
            'full_name' => $user->full_name,
            'role'      => $user->role,
        ]);

        echo json_encode([
            'status'   => 'success',
            'message'  => 'Login berhasil, mengalihkan ke dashboard...',
            'redirect' => site_url('pengelolaan/DashboardController'),
        ]);
    }

    public function logout()
    {
        $this->session->sess_destroy();
        $this->session->set_flashdata('flash_message', 'Anda telah berhasil logout.');
        redirect('AuthController');
    }
}