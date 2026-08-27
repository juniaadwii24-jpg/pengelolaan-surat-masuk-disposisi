<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Incoming_lettersController extends MY_Controller
{

    private $allowedFilterColumns = ['letter_number', 'sender', 'subject', 'status'];

    // Sesuai CHECK constraint chk_letter_status pada tabel incoming_letters.
    private $allowedStatus = ['Received', 'Processing', 'Completed', 'Archived'];

    public function __construct()
    {
        parent::__construct();

        // Load Model
        $this->load->model('pengelolaan/Incoming_letter_model');
        $this->load->model('pengelolaan/Recipient_model');
        $this->load->model('pengelolaan/Disposition_model', 'model');

        // Load Library
        $this->load->library('template');
        $this->load->library('form_validation');
    }

    /**
     * Halaman utama (tab "Tambah/Edit Surat" + "Daftar Surat").
     * [DIUBAH] Tidak lagi mengirim $letters/$search_number/$selected_status
     * ke view, karena tabel sekarang di-render lewat DataTables server-side
     * (getData()), bukan foreach PHP langsung.
     */
    public function index()
    {
        $data['title'] = 'Master Surat Masuk';
        $this->template->load('main_template', 'pengelolaan/@incoming_letters', $data);
    }

    /**
     * [BARU] Endpoint server-side untuk DataTables di tab "Daftar Surat".
     * Format response harus cocok dengan dataSrc() di view:
     * { RecordsTotal, RecordsFiltered, Data: [...] }
     */
    public function getData()
    {
        $start      = (int) $this->input->post('start');
        $length     = (int) $this->input->post('length');
        $filtervalue = $this->input->post('filtervalue');
        $filtertext  = $this->input->post('filtertext');

        // Validasi whitelist kolom filter; fallback ke letter_number kalau tidak dikenali.
        if (!in_array($filtervalue, $this->allowedFilterColumns, true)) {
            $filtervalue = 'letter_number';
        }

        $recordsTotal    = $this->Incoming_letter_model->count_all();
        $recordsFiltered = $this->Incoming_letter_model->count_filtered($filtervalue, $filtertext);
        $data            = $this->Incoming_letter_model->get_datatables($filtervalue, $filtertext, $start, $length);

        echo json_encode([
            'RecordsTotal'    => $recordsTotal,
            'RecordsFiltered' => $recordsFiltered,
            'Data'            => $data,
        ]);
    }

    /**
     * [BARU] Ambil 1 record surat untuk mode edit (dipanggil dari surat.selectdata()).
     * Response HARUS array (res[0] dipakai di view), meski cuma 1 baris.
     */
    public function getDataSelect()
    {
        $payload = json_decode(file_get_contents('php://input'), true);
        $id      = isset($payload['id']) ? $payload['id'] : null;

        if (!$id) {
            echo json_encode([]);
            return;
        }

        $row = $this->Incoming_letter_model->get_by_id($id);
        echo json_encode($row ? [$row] : []);
    }

    /**
     * [BARU] Simpan surat masuk baru. Menggantikan store() versi lama
     * (form_validation + $_POST) karena view sekarang kirim JSON body
     * lewat ko.mapping.toJS(), bukan form-encoded.
     */
    public function save()
    {
        $payload = json_decode(file_get_contents('php://input'), true);

        $data = [
            'letter_number' => isset($payload['letter_number']) ? trim($payload['letter_number']) : '',
            'letter_date'   => isset($payload['letter_date']) ? $payload['letter_date'] : '',
            'received_date' => isset($payload['received_date']) ? $payload['received_date'] : '',
            'sender'        => isset($payload['sender']) ? trim($payload['sender']) : '',
            'subject'       => isset($payload['subject']) ? trim($payload['subject']) : '',
            'description'   => isset($payload['description']) ? $payload['description'] : null,
            'status'        => isset($payload['status']) && $payload['status'] ? $payload['status'] : 'Received',
        ];

        $errors = $this->_validate($data);
        if ($errors) {
            echo json_encode(['result' => false, 'message' => implode(' ', $errors)]);
            return;
        }

        $ok = $this->Incoming_letter_model->insert($data);

        echo json_encode([
            'result'  => (bool) $ok,
            'message' => $ok ? 'Data surat masuk berhasil disimpan.' : 'Gagal menyimpan data surat masuk.',
        ]);
    }

    /**
     * [BARU] Update status saja, dipanggil dari dropdown status di tabel list
     * (surat.changeStatus()) tanpa reload halaman.
     */
    public function updateStatusOnly()
    {
        $payload = json_decode(file_get_contents('php://input'), true);
        $id      = isset($payload['id']) ? $payload['id'] : null;
        $status  = isset($payload['status']) ? $payload['status'] : null;

        if (!$id || !in_array($status, $this->allowedStatus, true)) {
            echo json_encode(['result' => false, 'message' => 'Data tidak valid.']);
            return;
        }

        $ok = $this->Incoming_letter_model->update_status($id, $status);
        echo json_encode(['result' => (bool) $ok]);
    }

    /**
     * Custom Callback Validasi Tanggal Diterima >= Tanggal Surat.
     * [DIPERTAHANKAN] Tidak dipakai lagi oleh save()/update() (validasi
     * sekarang lewat _validate()), tapi dibiarkan ada kalau ada bagian lain
     * yang masih memanggil form_validation dengan callback ini.
     */
    public function check_dates()
    {
        $letter_date   = $this->input->post('letter_date');
        $received_date = $this->input->post('received_date');

        if ($letter_date && $received_date) {
            $tgl_surat    = DateTime::createFromFormat('Y-m-d', $letter_date) ?: DateTime::createFromFormat('d-m-Y', $letter_date);
            $tgl_diterima = DateTime::createFromFormat('Y-m-d', $received_date) ?: DateTime::createFromFormat('d-m-Y', $received_date);

            if ($tgl_surat && $tgl_diterima) {
                if ($tgl_diterima < $tgl_surat) {
                    $this->form_validation->set_message('check_dates', 'Tanggal diterima tidak boleh lebih awal dari tanggal surat.');
                    return FALSE;
                }
            }
        }
        return TRUE;
    }

    /**
     * Halaman Detail Surat + Riwayat Disposisi + Form buat disposisi baru.
     * [DIPERTAHANKAN] Tetap sama; $dispositions masih dikirim untuk
     * fallback/SEO awal, tapi view detail yang baru sebenarnya me-refresh
     * riwayat lewat DispositionsController::getByLetterId (AJAX).
     */
    public function detail($id)
    {
        $data['surat']        = $this->Incoming_letter_model->get_by_id($id);
        $data['recipients']   = $this->Recipient_model->get_all();
        $data['dispositions'] = $this->model->getByLetterId($id);
        $data['title']        = 'Detail Surat & Disposisi';

        $this->template->load('main_template', 'pengelolaan/@incoming_lettersdetail', $data);
    }
public function update()
{
    $post = json_decode($this->input->raw_input_stream, true);
    $id = isset($post['id']) ? $post['id'] : null;

    if (!$id) {
        echo json_encode(['result' => false, 'message' => 'ID tidak valid.']);
        return;
    }

    // Ambil status surat yang ada di DB sekarang (bukan dari input user)
    $existing = $this->Incoming_letter_model->get_by_id($id);

    if (!$existing) {
        echo json_encode(['result' => false, 'message' => 'Data tidak ditemukan.']);
        return;
    }

    if ($existing['status'] === 'Archived') {
        echo json_encode(['result' => false, 'message' => 'Surat yang sudah diarsipkan tidak dapat diubah.']);
        return;
    }

    $data = [
        'letter_number' => isset($post['letter_number']) ? trim($post['letter_number']) : '',
        'letter_date'   => isset($post['letter_date']) ? $post['letter_date'] : '',
        'received_date' => isset($post['received_date']) ? $post['received_date'] : '',
        'sender'        => isset($post['sender']) ? trim($post['sender']) : '',
        'subject'       => isset($post['subject']) ? trim($post['subject']) : '',
        'description'   => isset($post['description']) ? $post['description'] : null,
        'status'        => isset($post['status']) && $post['status'] ? $post['status'] : $existing['status'],
    ];

    $errors = $this->_validate($data, $id); // $excludeId = $id, biar cek unik letter_number gak nabrak dirinya sendiri
    if ($errors) {
        echo json_encode(['result' => false, 'message' => implode(' ', $errors)]);
        return;
    }

    $ok = $this->Incoming_letter_model->update($id, $data);

    echo json_encode([
        'result'  => (bool) $ok,
        'message' => $ok ? 'Data surat masuk berhasil diubah.' : 'Gagal mengubah data surat masuk.',
    ]);
}
        public function delete()
    {
        $post = json_decode($this->input->raw_input_stream, true);
        $id = isset($post['id']) ? $post['id'] : null;

        if (!$id) {
            echo json_encode(['result' => false, 'message' => 'ID tidak valid.']);
            return;
        }

        $existing = $this->Incoming_letter_model->get_by_id($id);

        if (!$existing) {
            echo json_encode(['result' => false, 'message' => 'Data tidak ditemukan.']);
            return;
        }

        if ($existing['status'] === 'Archived') {
            echo json_encode(['result' => false, 'message' => 'Surat yang sudah diarsipkan tidak dapat dihapus.']);
            return;
        }

        // Hapus dulu semua disposisi terkait. Sebenarnya sudah otomatis
        // lewat ON DELETE CASCADE (fk_disposition_letter) di database,
        // tapi tetap dijaga eksplisit di sini untuk kompatibilitas kalau
        // suatu saat constraint CASCADE dilepas / driver DB berbeda.
        $relatedDispositions = $this->model->getByLetterId($id);
        foreach ($relatedDispositions as $disposition) {
            $this->model->deleteData(['id' => $disposition->id]);
        }

        $ok = $this->Incoming_letter_model->delete($id);

        echo json_encode([
            'result'  => (bool) $ok,
            'message' => $ok
                ? 'Surat beserta riwayat disposisinya berhasil dihapus.'
                : 'Gagal menghapus surat.',
        ]);
    }
    

    /**
     * [BARU] Validasi server-side untuk save()/update(), meniru pola
     * DispositionsController::_validate() yang disebut di komentar view lama.
     * Mengecek field wajib, keunikan letter_number, urutan CHECK constraint
     * tanggal, dan status yang valid — sama persis dengan constraint di DB.
     */
    private function _validate($data, $excludeId = null)
    {
        $errors = [];

        if ($data['letter_number'] === '') {
            $errors[] = 'Nomor surat wajib diisi.';
        } else {
            // Cek keunikan letter_number (kolom UNIQUE di DB).
            if ($this->Incoming_letter_model->is_letter_number_taken($data['letter_number'], $excludeId)) {
                $errors[] = 'Nomor surat sudah digunakan surat lain.';
            }
        }

        if ($data['sender'] === '') {
            $errors[] = 'Pengirim wajib diisi.';
        }
        if ($data['subject'] === '') {
            $errors[] = 'Perihal wajib diisi.';
        }
        if (empty($data['letter_date'])) {
            $errors[] = 'Tanggal surat wajib diisi.';
        }
        if (empty($data['received_date'])) {
            $errors[] = 'Tanggal diterima wajib diisi.';
        }

        // Samakan dengan CHECK constraint chk_received_after_letter_date.
        if (!empty($data['letter_date']) && !empty($data['received_date'])) {
            if (strtotime($data['received_date']) < strtotime($data['letter_date'])) {
                $errors[] = 'Tanggal diterima tidak boleh sebelum tanggal surat.';
            }
        }

        // Samakan dengan CHECK constraint chk_letter_status.
        if (!in_array($data['status'], $this->allowedStatus, true)) {
            $errors[] = 'Status surat tidak valid.';
        }

        return $errors;
    }
}