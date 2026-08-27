<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Model untuk tabel "users" (lihat database/users.sql).
 * Dipakai khusus oleh AuthController untuk proses login.
 *
 * Mengikuti pola model lain di modul pengelolaan (Recipient_model,
 * Disposition_model, dst): semua query pakai Query Builder CodeIgniter,
 * tidak ada string SQL manual, supaya aman dari SQL Injection.
 */
class Auth_model extends CI_Model
{
    private $table = 'users';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Ambil 1 user berdasarkan username, dalam bentuk OBJECT.
     * Dipakai AuthController::login() untuk mengecek kredensial.
     * Tidak error kalau username tidak ditemukan -> mengembalikan NULL,
     * biar AuthController yang menentukan pesan error ke user
     * (username tidak ditemukan & password salah sengaja diberi pesan
     * yang SAMA, supaya tidak membocorkan username mana saja yang valid).
     *
     * @param string $username
     * @return object|null
     */
    public function getUserByUsername($username)
    {
        return $this->db
            ->get_where($this->table, ['username' => $username, 'is_active' => true])
            ->row();
    }
}