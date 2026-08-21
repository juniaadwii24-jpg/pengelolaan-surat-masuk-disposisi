<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Recipient_model extends CI_Model
{
    private $table = 'recipients';

    // Whitelist kolom yang boleh dipakai untuk filter/search dari frontend.
    // Sama seperti Disposition_model::$allowedFilterColumns — mencegah nama
    // kolom mentah dari input user langsung dipakai di query.
    private $allowedFilterColumns = [
        'name'       => 'name',
        'position'   => 'position',
        'department' => 'department',
        'email'      => 'email',
    ];

    public function __construct()
    {
        parent::__construct();
    }

    
     //Daftar semua penerima (dipakai untuk isi tabel di halaman index,
     //versi non-datatable / render langsung lewat PHP seperti pada view
     //"Master Penerima Disposisi" yang sudah ada).
     //search kata kunci pencarian nama

    public function get_all($search = null)
    {
        if ($search) {
            $this->db->like('name', $search);
        }
        $this->db->order_by('name', 'ASC');
        return $this->db->get($this->table)->result_array();
    }

    /**
     * Server-side datatable: list + search per kolom (whitelist) + pagination.
     * Setara dengan DataPelanggan_model::getDataAll(), tapi aman dari SQL
     * Injection dan tanpa JOIN (recipients berdiri sendiri).
     *
     * data berisi start, length, filtervalue, filtertext
     *  RecordsTotal, RecordsFiltered, Data
     */
    public function getDataAll($data)
    {
        // Total semua data (tanpa filter)
        $totalAll = $this->db->count_all($this->table);

        $this->db->from($this->table);

        // Hanya terapkan filter jika filtertext & filtervalue diisi,
        // DAN filtervalue ada di whitelist (mencegah SQL injection lewat
        // nama kolom yang tidak dikenal).
        if (!empty($data['filtertext']) && !empty($data['filtervalue'])
            && isset($this->allowedFilterColumns[$data['filtervalue']])
        ) {
            $column = $this->allowedFilterColumns[$data['filtervalue']];
            $this->db->like($column, $data['filtertext']);
        }

        // Hitung total setelah difilter (untuk RecordsFiltered), sebelum limit.
        // Parameter kedua "false" = query builder TIDAK direset, supaya filter
        // yang sudah di-set masih terpakai untuk query ->get() di bawah.
        $totalFiltered = $this->db->count_all_results('', false);

        $this->db->order_by('id', 'ASC');
        $this->db->limit((int) $data['length'], (int) $data['start']);
        $result = $this->db->get()->result();

        return [
            'RecordsTotal'    => $totalAll,
            'RecordsFiltered' => $totalFiltered,
            'Data'            => $result,
        ];
    }

    /// Ambil 1 penerima berdasarkan id, dalam bentuk ARRAY asosiatif
      //(dipakai modal edit: JS membaca data.name, data.position, dst).
    
    public function getDataId($id)
    {
        return $this->db->get_where($this->table, ['id' => $id])->row_array();
    }

    public function insert($data)
    {
        $insert = [
            'name'       => $data['name'],
            'position'   => $data['position'],
            'department' => $data['department'],
            'email'      => $data['email'],
          'created_at' => date('Y-m-d H:i:s'), // jaga-jaga kalau kolom ini NOT NULL tanpa default
          
        ];
            $this->db->insert($this->table, $insert);   // <-- baris yang hilang, WAJIB ADA
            return $this->db->insert_id(); // return ID baru (0/false kalau gagal)

    }

    
     //Update data penerima//
  public function update($id, $data)
{
    $update = [
        'name'       => $data['name'],
        'position'   => $data['position'],
        'department' => $data['department'],
        'email'      => $data['email'],
    ];
    $this->db->where('id', $id);
    $this->db->update($this->table, $update);
    return $this->db->affected_rows() > 0;
}

    // Cek apakah penerima ini masih dipakai di tabel dispositions,
    //  Constraint fk_disposition_recipient di pengelolaan.sql memakai
   //ON DELETE RESTRICT, artinya kalau masih dipakai, delete() akan
    //  ditolak oleh database (fatal error) jika tidak dicek dulu di sini

    public function isUsedInDisposition($id)
    {
        $this->db->where('recipient_id', $id);
        return $this->db->count_all_results('dispositions') > 0;
    }

    //Hapus 1 penerima berdasarkan id.
     // Panggil isUsedInDisposition() dulu di controller sebelum memanggil ini.
     
    public function delete($id)
    {
        $this->db->where('id', $id);
        return $this->db->delete($this->table);
    }
}