<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class DashboardController extends MY_Controller
{
    public function __construct()
    {
        parent::__construct(); // menjalankan pengecekan login di Auth_Controller

        $this->load->model('pengelolaan/Disposition_model', 'model');
        $this->load->model('pengelolaan/Incoming_letter_model');
        $this->load->model('pengelolaan/Recipient_model');
        $this->load->library('template');
        $this->load->database();
    }

    /**
     * Ringkasan angka + tabel singkat untuk halaman dashboard:
     * - Total surat masuk, total disposisi, total penerima
     * - Breakdown jumlah surat per status (buat kartu info & progress bar)
     * - Breakdown jumlah disposisi per status
     * - 5 surat masuk terbaru
     * - 5 disposisi yang statusnya masih aktif (Pending/In Progress), buat
     *   pengingat "yang masih perlu ditindaklanjuti"
     */
    public function index()
    {
        $data['title'] = 'Dashboard';
    // --- Info user yang sedang login, untuk ucapan "Selamat datang" ---
    $data['fullName'] = $this->session->userdata('full_name');
    $data['role']     = $this->session->userdata('role');

        // --- Angka ringkasan ---
        $data['totalSurat']     = $this->db->count_all('incoming_letters');
        $data['totalDisposisi'] = $this->model->countAll();
        $data['totalPenerima']  = $this->db->count_all('recipients');

        // --- Breakdown status surat, dipakai untuk kartu warna-warni ---
        $statusSuratRaw = $this->db
            ->select('status, COUNT(*) AS jumlah')
            ->from('incoming_letters')
            ->group_by('status')
            ->get()->result();
        // Susun ke bentuk asosiatif [status => jumlah] dengan default 0,
        // supaya view tidak perlu cek isset() satu-satu untuk tiap status.
        $data['statusSurat'] = [
            'Received'   => 0,
            'Processing' => 0,
            'Completed'  => 0,
            'Archived'   => 0,
        ];
        foreach ($statusSuratRaw as $row) {
            $data['statusSurat'][$row->status] = (int) $row->jumlah;
        }

        // --- Breakdown status disposisi ---
        $statusDisposisiRaw = $this->db
            ->select('status, COUNT(*) AS jumlah')
            ->from('dispositions')
            ->group_by('status')
            ->get()->result();
        $data['statusDisposisi'] = [
            'Pending'     => 0,
            'In Progress' => 0,
            'Completed'   => 0,
        ];
        foreach ($statusDisposisiRaw as $row) {
            $data['statusDisposisi'][$row->status] = (int) $row->jumlah;
        }

        // --- 5 surat masuk terbaru (untuk tabel ringkas) ---
        $data['recentLetters'] = $this->db
            ->order_by('received_date', 'DESC')
            ->limit(5)
            ->get('incoming_letters')
            ->result();

        // --- 5 disposisi yang masih aktif (belum Completed), join supaya
        //     dapat nomor surat & nama penerima langsung, tanpa query N+1 ---
        $data['activeDispositions'] = $this->db
            ->select('d.id, d.instruction, d.status, d.disposition_date, il.letter_number, r.name AS recipient_name')
            ->from('dispositions d')
            ->join('incoming_letters il', 'il.id = d.letter_id')
            ->join('recipients r', 'r.id = d.recipient_id')
            ->where_in('d.status', ['Pending', 'In Progress'])
            ->order_by('d.disposition_date', 'ASC')
            ->limit(5)
            ->get()->result();

        $this->template->load('main_template', 'pengelolaan/@dashboard', $data);
    }
}