<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Matriks hak akses per role.
 * Format: 'NamaController' => ['nama_action' => ['role1','role2', ...]]
 * Kalau action tidak didaftar di sini -> dianggap boleh diakses semua role yang login.
 */
$config['role_permission'] = [

    'Incoming_lettersController' => [
        // Lihat daftar & detail surat: semua role boleh
        'save'            => ['admin', 'keptu', 'sektu'],
        'update'          => ['admin', 'keptu', 'sektu'],
        'delete'          => ['admin', 'keptu'],
        'updateStatusOnly'=> ['admin', 'keptu', 'sektu'],
    ],

    'DispositionsController' => [
        // Lihat riwayat/daftar: semua role boleh
        'save'            => ['admin', 'kepsek', 'keptu'],   // yang boleh BUAT disposisi baru
        'update'          => ['admin', 'kepsek', 'keptu'],
        'delete'          => ['admin', 'kepsek'],
        'updateStatusOnly'=> ['admin', 'kepsek', 'keptu', 'wakahumas', 'wakakurikulum', 'sektu'], // tindak lanjut
    ],

    'RecipientsController' => [
        // Master data penerima -> hanya admin & TU yang kelola
        'index'  => ['admin', 'keptu'],
        'getData'=> ['admin', 'keptu'],
        'save'   => ['admin', 'keptu'],
        'update' => ['admin', 'keptu'],
        'delete' => ['admin'],
    ],
];