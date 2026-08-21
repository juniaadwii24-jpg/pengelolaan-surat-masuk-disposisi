<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Model untuk tabel "dispositions".
 * Bertanggung jawab atas semua query database terkait data disposisi:
 * ambil list (dengan filter & pagination server-side), ambil 1 data,
 * insert, update, update status saja, hapus, dan beberapa query pendukung
 * untuk business rule (misal cek apakah surat masih punya disposisi aktif).
 */
class Disposition_model extends CI_Model
{
    // Nama tabel utama yang dikelola model ini
    private $table = 'dispositions';

    // Whitelist kolom yang boleh dipakai untuk search/filter dari frontend.
    // PENTING: jangan pernah pakai nama kolom mentah dari input user langsung
    // ke query builder tanpa whitelist, supaya tidak rentan SQL injection.
    // Key = nilai yang dikirim dari dropdown filter di frontend,
    // Value = nama kolom (dengan alias tabel) yang sebenarnya dipakai di query.
    private $allowedFilterColumns = [
        'letter_number'   => 'il.letter_number',   // dari tabel incoming_letters (alias il)
        'recipient_name'  => 'r.name',              // dari tabel recipients (alias r)
        'department'      => 'r.department',        // dari tabel recipients (alias r)
        'instruction'     => 'd.instruction',        // dari tabel dispositions (alias d)
        'status'          => 'd.status',             // dari tabel dispositions (alias d)
    ];

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Query dasar dengan JOIN 3 tabel (dipakai ulang oleh beberapa method).
     * Menggabungkan data dispositions + incoming_letters (untuk nomor surat)
     * + recipients (untuk nama, departemen, jabatan penerima).
     * Method ini hanya MENYUSUN query builder CI, belum menjalankan ->get().
     */
    private function _baseQuery()
    {
        $this->db->select('d.id, d.letter_id, d.recipient_id, d.instruction, d.disposition_date, d.status, d.notes,
                            il.letter_number, r.name AS recipient_name, r.department, r.position');
        $this->db->from($this->table . ' d');
        $this->db->join('incoming_letters il', 'il.id = d.letter_id'); // ambil nomor surat
        $this->db->join('recipients r', 'r.id = d.recipient_id');      // ambil detail penerima
    }

    /**
     * Server-side datatable: list + search + filter + pagination.
     * Dipanggil oleh DispositionsController::getData() yang menerima
     * request AJAX dari DataTables (start, length, filtervalue, filtertext).*/
    public function getDataAll($data)
    {
        // --- Query untuk hitung total data (tanpa filter, untuk RecordsTotal) ---
        $totalAll = $this->db->count_all($this->table);

        // --- Query utama dengan filter ---
        $this->_baseQuery();

        // Hanya terapkan filter jika filtertext & filtervalue diisi,
        // DAN filtervalue ada di whitelist $allowedFilterColumns (mencegah SQL injection
        // lewat nama kolom yang tidak dikenal).
        if (!empty($data['filtertext']) && !empty($data['filtervalue'])
            && isset($this->allowedFilterColumns[$data['filtervalue']])
        ) {
            $column = $this->allowedFilterColumns[$data['filtervalue']];
            $this->db->like($column, $data['filtertext']); // LIKE '%filtertext%'
        }

        // Hitung total setelah difilter (untuk RecordsFiltered), sebelum limit diterapkan.
        // Parameter kedua "false" artinya query builder TIDAK di-reset setelah count,
        // sehingga filter yang sudah di-set (where/like/join) masih bisa dipakai
        // untuk query ->get() di bawah.
        $totalFiltered = $this->db->count_all_results('', false); // false = jangan reset query builder

        // Urutkan berdasarkan tanggal disposisi terbaru, lalu terapkan pagination (limit/offset)
        $this->db->order_by('d.disposition_date', 'DESC');
        $this->db->limit((int) $data['length'], (int) $data['start']);
        $result = $this->db->get()->result();

        return [
            'RecordsTotal'    => $totalAll,
            'RecordsFiltered' => $totalFiltered,
            'Data'            => $result,
        ];
    }

    /**
     * Ambil 1 disposisi berdasarkan id (untuk mode edit di form).*/
    public function getDataId($id)
    {
        $this->_baseQuery();
        $this->db->where('d.id', $id);
        return $this->db->get()->result();
    }

    /**
     * Riwayat disposisi untuk 1 surat tertentu (dipakai di halaman Detail Surat).
     * Berguna untuk menampilkan semua disposisi yang pernah dibuat atas satu surat masuk,
     * letter_id id surat masuk
     *daftar disposisi terkait surat tsb, terbaru dulu
     */
    public function getByLetterId($letter_id)
    {
        $this->_baseQuery();
        $this->db->where('d.letter_id', $letter_id);
        $this->db->order_by('d.disposition_date', 'DESC');
        return $this->db->get()->result();
    }

    /**
     * Simpan data disposisi baru ke database.
     * data data dari form (letter_id, recipient_id, instruction, dst)
     *  id (insert_id) dari data yang baru disimpan
     */
    public function insertData($data)
    {
        $insert = [
            'letter_id'        => $data['letter_id'],
            'recipient_id'     => $data['recipient_id'],
            'instruction'      => $data['instruction'],
            'disposition_date' => $data['disposition_date'],
            'status'           => $data['status'],
            // notes bersifat opsional; jika tidak dikirim, simpan NULL
            'notes'            => isset($data['notes']) ? $data['notes'] : null,
        ];
        $this->db->insert($this->table, $insert);
        return $this->db->insert_id();
    }

    /**
     * Update data disposisi yang sudah ada.
     * Catatan: letter_id sengaja TIDAK diupdate di sini (surat masuk yang
     * sudah didisposisikan tidak boleh diganti setelah data dibuat).
     *  data harus mengandung 'id' dan field-field yang ingin diupdate
     *  hasil operasi update
     */
    public function updateData($data)
    {
        $update = [
            'recipient_id'     => $data['recipient_id'],
            'instruction'      => $data['instruction'],
            'disposition_date' => $data['disposition_date'],
            'status'           => $data['status'],
            'notes'            => isset($data['notes']) ? $data['notes'] : null,
        ];
        $this->db->where('id', $data['id']);
        return $this->db->update($this->table, $update);
    }
    public function updateStatus($id, $status)
{
    $this->db->where('id', $id);
    return $this->db->update($this->table, ['status' => $status]);
}

    /**
     * Update HANYA kolom status (dipakai oleh fitur ganti status langsung
     * dari dropdown di tabel list, tanpa perlu buka form edit lengkap).
     * id id disposisi
     * status status baru
     *  hasil operasi update
     */
  // dipanggil setiap kali disposisi disimpan ATAU diupdate, bukan cuma saat create

    public function deleteData($id)
    {
        $this->db->where('id', $id);
        return $this->db->delete($this->table);
    }

    // Cek apakah masih ada disposisi yang BELUM selesai untuk 1 surat tertentu.
    // Dipakai untuk business logic: surat baru boleh "Completed" kalau SEMUA
    // disposisinya sudah "Completed".
    /**
     *  $letter_id id surat masuk yang ingin dicek
     * true jika masih ada disposisi berstatus Pending/In Progress
     *              untuk surat tsb (artinya surat BELUM boleh dianggap selesai)
     */
    public function hasActiveDisposition($letter_id)
    {
        $this->db->where('letter_id', $letter_id);
        $this->db->where_in('status', ['Pending', 'In Progress']);
        return $this->db->count_all_results($this->table) > 0;
    }

    /**
     * Untuk dashboard: total semua disposisi (tanpa filter apapun).
     *  jumlah total baris di tabel dispositions
     */
    public function countAll()
    {
        return $this->db->count_all($this->table);
    }
}