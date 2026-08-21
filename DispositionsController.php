<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Controller untuk fitur Master Disposisi.
 * Menjembatani antara view (Knockout + DataTables) dan model (Disposition_model).
 * Semua endpoint AJAX yang dipanggil dari view (dispositions_view.php) ada di sini.
 */
class DispositionsController extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        // Load model utama untuk data disposisi, diberi alias "model" supaya
        // pemanggilannya singkat: $this->model->...
        $this->load->model('pengelolaan/Disposition_model', 'model');
        // Load model surat masuk, dipakai untuk update status surat (business rule poin 7)
        $this->load->model('pengelolaan/Incoming_letter_model');
        $this->load->database(); // Pastikan database terload
        $this->load->library('template'); // pastikan library template ter-load
    }

    /**
     * Halaman utama Master Disposisi.
     * Hanya me-render view lewat library template, tidak ada logic khusus.
     */
    public function index()
    {
        $data['title'] = 'Master Disposisi';
        $this->template->load('main_template', 'pengelolaan/@dispositions', $data);
    }

    /**
     * Endpoint untuk mengisi tabel di tab "Daftar Disposisi".
     * Dipanggil DataTables server-side (POST) setiap kali user membuka tab list,
     * berpindah halaman, atau melakukan pencarian/filter.
     * Mengembalikan JSON dengan format yang sudah disesuaikan DataTables
     * (RecordsTotal / RecordsFiltered / Data) di model.
     */
    function getData()
    {
        $data = [
            'start'       => $this->input->post('start'),        // offset pagination
            'length'      => $this->input->post('length'),       // jumlah baris per halaman
            'filtervalue' => $this->input->post('filtervalue'),  // kolom yang difilter
            'filtertext'  => $this->input->post('filtertext'),   // kata kunci pencarian
        ];
        $res = $this->model->getDataAll($data);
        echo json_encode($res);
    }

    /**
     * Endpoint untuk ambil 1 data disposisi lengkap berdasarkan id.
     * Dipanggil saat user klik tombol edit di tabel (mode Update),
     * supaya form bisa diisi otomatis dengan data yang akan diedit.
     */
    function getDataSelect()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $res = $this->model->getDataId($data['id']);
        echo json_encode($res);
    }

    /**
     * Endpoint untuk menyimpan disposisi BARU (dipanggil dari tombol "Simpan"
     * saat form dalam mode tambah data, bukan mode Update).
     */
    function save()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        // Validasi ulang di server (validasi di frontend hanya untuk UX,
        // validasi sesungguhnya yang menentukan aman/tidaknya data ada di sini).
        $error = $this->_validate($data);
        if ($error) {
            echo json_encode(['result' => false, 'message' => $error]);
            return;
        }

        // Simpan data disposisi baru, dapatkan id hasil insert
        $insertId = $this->model->insertData($data);

        // Business rule (poin 7): recalculate status surat setelah ada
        // disposisi baru. Dipakai fungsi yang sama dengan update()/delete()
        // supaya perilakunya konsisten di semua titik perubahan data disposisi.
        if ($insertId) {
            $this->_recalculateLetterStatus($data['letter_id']);
        }

        $res = ['result' => (bool) $insertId];
        echo json_encode($res);
    }

    /**
     * Endpoint untuk mengupdate disposisi yang sudah ada (termasuk ubah status
     * dari form edit lengkap; untuk ubah status cepat dari dropdown tabel list,
     * lihat method updateStatusOnly() di bawah).
     */
    function update()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        // Validasi server-side; $isUpdate = true supaya letter_id tidak wajib diisi
        // (karena letter_id memang tidak diubah saat update).
        $error = $this->_validate($data, true);
        if ($error) {
            echo json_encode(['result' => false, 'message' => $error]);
            return;
        }

        $updated = $this->model->updateData($data);

        // PERBAIKAN BUG: sebelumnya recalculate status surat HANYA dijalankan
        // ketika status disposisi baru == 'Completed'. Akibatnya kalau status
        // disposisi diubah BALIK dari Completed ke Pending/In Progress, status
        // surat tidak pernah diturunkan lagi dan tetap "nyangkut" di Completed.
        // Sekarang recalculate SELALU dijalankan setelah update, apapun status
        // barunya, supaya bisa naik (jadi Completed) maupun turun
        // (balik jadi Processing) sesuai kondisi disposisi yang sebenarnya.
        if ($updated) {
            // letter_id tidak dikirim dari form saat update, jadi diambil ulang dari DB
            $current = $this->model->getDataId($data['id']);
            if (!empty($current)) {
                $this->_recalculateLetterStatus($current[0]->letter_id);
            }
        }

        $res = ['result' => (bool) $updated];
        echo json_encode($res);
    }

    /**
     * Endpoint untuk mengubah status SAJA (dipanggil dari dropdown status
     * di tabel list, tanpa perlu buka form edit dan tanpa reload halaman).
     * Sama seperti update(): recalculate status surat SELALU dijalankan,
     * bukan cuma saat status baru == 'Completed', supaya status surat bisa
     * naik maupun turun mengikuti kondisi disposisi yang sebenarnya.
     */
    function updateStatusOnly()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $id     = $data['id'];
        $status = $data['status'];

        // Ambil letter_id SEBELUM update, supaya tetap ada datanya untuk recalculate
        $current = $this->model->getDataId($id);

        $updated = $this->model->updateStatus($id, $status);

        if ($updated && !empty($current)) {
            $this->_recalculateLetterStatus($current[0]->letter_id);
        }

        echo json_encode(['result' => (bool) $updated]);
    }

    /**
     * Endpoint untuk menghapus 1 data disposisi berdasarkan id.
     * Setelah dihapus, status surat juga di-recalculate: bisa saja surat yang
     * tadinya Completed jadi tidak Completed lagi kalau disposisi Completed
     * yang dihapus ternyata satu-satunya, atau sebaliknya, surat jadi Completed
     * kalau yang dihapus adalah satu-satunya disposisi aktif yang tersisa.
     */
    function delete()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        // Ambil letter_id SEBELUM data dihapus, karena setelah dihapus datanya
        // sudah tidak bisa diambil lagi lewat getDataId().
        $current = $this->model->getDataId($data['id']);

        $success = $this->model->deleteData($data['id']);

        if ($success && !empty($current)) {
            $this->_recalculateLetterStatus($current[0]->letter_id);
        }

        echo json_encode([
            'result'  => (bool) $success,
            'message' => $success ? 'Data berhasil dihapus.' : 'Gagal menghapus data.',
        ]);
    }

    /**
     * Endpoint untuk mengisi dropdown "Surat Masuk" pada form (dipanggil sekali
     * saat halaman dimuat, lewat disposisi.loadSelectSurat() di view).
     * Mengambil daftar surat dari Incoming_letter_model, lalu diformat ulang
     * menjadi pasangan value/name yang dikenali binding Knockout (options/optionsValue/optionsText).
     */
    public function getSelectSurat()
    {
        $data = $this->Incoming_letter_model->getSelectOptions();
        $result = [];
        foreach ($data as $row) {
            $result[] = [
                'value' => $row->id,
                'name'  => $row->letter_number . ' - ' . $row->subject,
            ];
        }
        echo json_encode($result);
    }

    /**
     * Endpoint untuk mengisi dropdown "Penerima Disposisi" pada form (dipanggil
     * sekali saat halaman dimuat, lewat disposisi.loadSelectPenerima() di view).
     * Mengambil semua baris dari tabel recipients langsung (tanpa lewat model
     * khusus), lalu diformat menjadi pasangan value/name.
     */
    public function getSelectPenerima()
    {
        $data = $this->db->get('recipients')->result();
        $result = [];
        foreach ($data as $row) {
            $result[] = [
                'value' => $row->id,
                'name'  => $row->name . ' - ' . $row->department,
            ];
        }
        echo json_encode($result);
    }

    /**
 * Endpoint AJAX untuk ambil riwayat disposisi milik 1 surat tertentu.
 * Dipanggil dari halaman detail surat (suratDetail.loadHistory()).
 */
public function getByLetterId()
{
    $payload   = json_decode(file_get_contents('php://input'), true);
    $letter_id = isset($payload['letter_id']) ? $payload['letter_id'] : null;

    if (!$letter_id) {
        echo json_encode([]);
        return;
    }

    $res = $this->model->getByLetterId($letter_id);
    echo json_encode($res);
}
    /**
     * [BARU] Satu-satunya tempat yang menentukan status surat berdasarkan
     * kondisi disposisi-disposisi surat itu SAAT INI. Dipanggil dari save(),
     * update(), updateStatusOnly(), dan delete(), supaya logic-nya konsisten
     * di semua titik yang bisa mengubah data disposisi, dan bisa menaikkan
     * ATAUPUN menurunkan status surat (bukan cuma satu arah seperti
     * sebelumnya).
     *
     * Aturan (business rule poin 7):
     * - "Archived" adalah status manual/final -> tidak pernah disentuh
     *   otomatis oleh perhitungan ini.
     * - Kalau masih ada disposisi berstatus Pending/In Progress -> surat
     *   harus "Processing" (mundur dari Completed kalau perlu, sekaligus
     *   penjaga jika surat masih "Received" tapi sudah ada disposisi).
     * - Kalau SEMUA disposisi surat itu sudah Completed -> surat jadi
     *   "Completed".
     *
     * @param int $letter_id
     */
    private function _recalculateLetterStatus($letter_id)
    {
        $letter = $this->Incoming_letter_model->getById($letter_id);
        if (!$letter) {
            return;
        }

        // Status "Archived" bersifat final/manual, jangan pernah ditimpa otomatis
        if ($letter->status === 'Archived') {
            return;
        }

        $stillActive = $this->model->hasActiveDisposition($letter_id);

        if ($stillActive) {
            // Masih ada disposisi yang belum selesai -> surat harus Processing
            if ($letter->status !== 'Processing') {
                $this->Incoming_letter_model->update_status($letter_id, 'Processing');
            }
        } else {
            // Tidak ada lagi disposisi yang aktif -> semua sudah Completed
            // (atau surat memang belum punya disposisi sama sekali, dalam hal
            // itu status tidak perlu diubah karena tidak ada apa-apa untuk
            // dinilai "selesai").
            $hasAnyDisposition = !empty($this->model->getByLetterId($letter_id));
            if ($hasAnyDisposition && $letter->status !== 'Completed') {
                $this->Incoming_letter_model->update_status($letter_id, 'Completed');
            }
        }
    }

    /**
     * Validasi input sesuai business rule poin 7 dokumen tugas.
     * Dipakai bersama oleh save() dan update().
     *
     * @param array $data data yang dikirim dari form (hasil decode JSON body request)
     * @param bool $isUpdate jika true, letter_id TIDAK wajib divalidasi
     *                       (karena saat update, letter_id memang tidak dikirim/diubah)
     * @return string|null pesan error jika validasi gagal, atau null jika semua valid
     */
    private function _validate($data, $isUpdate = false)
    {
        // letter_id hanya wajib diisi saat tambah data baru (bukan saat update)
        if (!$isUpdate && empty($data['letter_id'])) {
            return 'Surat masuk wajib dipilih.';
        }
        if (empty($data['recipient_id'])) {
            return 'Penerima disposisi wajib dipilih.';
        }
        if (empty($data['instruction'])) {
            return 'Instruksi disposisi wajib diisi.';
        }
        if (empty($data['disposition_date'])) {
            return 'Tanggal disposisi wajib diisi.';
        }
        // Status harus salah satu dari 3 nilai yang valid, mencegah data status "liar" masuk ke DB
        if (empty($data['status']) || !in_array($data['status'], ['Pending', 'In Progress', 'Completed', 'Archived'])) {
            return 'Status disposisi tidak valid.';
        }
        return null; // lolos validasi
    }
}