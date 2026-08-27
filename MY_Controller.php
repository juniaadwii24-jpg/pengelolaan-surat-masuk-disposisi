<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Controller extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->config('role_permission');

        if (!$this->session->userdata('logged_in')) {
            redirect('AuthController');
            exit;
        }
    }

    protected function checkPermission($action)
    {
        $controllerName = get_class($this);
        $permissions    = $this->config->item('role_permission');
        $role           = $this->session->userdata('role');

        if (!isset($permissions[$controllerName][$action])) {
            return true;
        }

        $allowedRoles = $permissions[$controllerName][$action];

        if (!in_array($role, $allowedRoles, true)) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['result' => false, 'message' => 'Anda tidak memiliki hak akses untuk aksi ini.']);
            exit;
        }
        return true;
    }
}