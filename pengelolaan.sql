DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS dispositions;
DROP TABLE IF EXISTS incoming_letters;
DROP TABLE IF EXISTS recipients;

CREATE TABLE recipients (
    id          SERIAL PRIMARY KEY,
    name        VARCHAR(150) NOT NULL,
    position    VARCHAR(100),
    department  VARCHAR(100),
    email       VARCHAR(150),
    created_at  TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE TABLE incoming_letters (
    id             SERIAL PRIMARY KEY,
    letter_number  VARCHAR(50) NOT NULL UNIQUE,
    letter_date    DATE NOT NULL,
    received_date  DATE NOT NULL,
    sender         VARCHAR(150) NOT NULL,
    subject        VARCHAR(255) NOT NULL,
    description    TEXT,
    status         VARCHAR(20) NOT NULL DEFAULT 'Received',
    created_at     TIMESTAMP NOT NULL DEFAULT NOW(),
    CONSTRAINT chk_received_after_letter_date
        CHECK (received_date >= letter_date),
    CONSTRAINT chk_letter_status
        CHECK (status IN ('Received', 'Processing', 'Completed', 'Archived'))
);

CREATE TABLE dispositions (
    id                SERIAL PRIMARY KEY,
    letter_id         INTEGER NOT NULL,
    recipient_id      INTEGER NOT NULL,
    instruction       TEXT NOT NULL,
    disposition_date  DATE NOT NULL,
    status            VARCHAR(20) NOT NULL DEFAULT 'Pending',
    notes             TEXT,
    created_at        TIMESTAMP NOT NULL DEFAULT NOW(),

    CONSTRAINT fk_disposition_letter
        FOREIGN KEY (letter_id) REFERENCES incoming_letters(id) ON DELETE CASCADE,
    CONSTRAINT fk_disposition_recipient
        FOREIGN KEY (recipient_id) REFERENCES recipients(id) ON DELETE RESTRICT,
    CONSTRAINT chk_disposition_status
        CHECK (status IN ('Pending', 'In Progress', 'Completed'))
);

-- index (setelah semua tabel ada)
CREATE INDEX idx_letters_status         ON incoming_letters(status);
CREATE INDEX idx_letters_number         ON incoming_letters(letter_number);
CREATE INDEX idx_dispositions_letter    ON dispositions(letter_id);
CREATE INDEX idx_dispositions_recipient ON dispositions(recipient_id);
CREATE INDEX idx_dispositions_status    ON dispositions(status);

--testing recipients (struktur organisasi toko retail sesuai bagan)--
-- Urutan insert = urutan id (SERIAL), mengikuti hierarki dari atas ke bawah, kiri ke kanan:
-- Pemilik -> Direktur -> Manajer Umum -> (Staf Administrasi -> Supervisor -> [Marketing Retail, Marketing Project])
--                                      -> (Sekretaris        -> Supervisor -> [Staf, Staf])
INSERT INTO recipients (name, position, department, email) VALUES
('Ahmad Zulkarnain', 'Pemilik', 'Manajemen', 'ahmad.zulkarnain@gmail.com'),          -- id 1
('Danendra Vero', 'Direktur', 'Manajemen', 'danendra.vero@gmail.com'),               -- id 2
('Rian Saputra', 'Manajer Umum', 'Manajemen', 'rian.saputra@gmail.com'),             -- id 3
('Bagas Pratama', 'Staf Administrasi', 'Administrasi', 'bagas.pratama@gmail.com'),   -- id 4
('Maya Srikandi', 'Sekretaris', 'Sekretariat', 'maya.srikandi@gmail.com'),           -- id 5
('Alexandra Drazeva Janela', 'Supervisor Marketing', 'Marketing', 'alexandra.drazeva@gmail.com'), -- id 6
('Siti Nurhaliza', 'Supervisor Operasional', 'Operasional', 'siti.nurhaliza@gmail.com'),          -- id 7
('Fajar Ramadhan', 'Staf Marketing Retail', 'Marketing', 'fajar.ramadhan@gmail.com'),  -- id 8
('Dewi Anggraini', 'Staf Marketing Project', 'Marketing', 'dewi.anggraini@gmail.com'), -- id 9
('Ahmad Fauzi', 'Staf Operasional', 'Operasional', 'ahmad.fauzi@gmail.com'),           -- id 10
('Putri Amelia', 'Staf Operasional', 'Operasional', 'putri.amelia@gmail.com');         -- id 11

--testing incoming_letters (harus sebelum dispositions) - konteks toko retail--
INSERT INTO incoming_letters (letter_number, letter_date, received_date, sender, subject, description, status) VALUES
('001/SM/VIII/2026', '2026-08-01', '2026-08-02', 'PT Sumber Pangan Nusantara', 'Penawaran Kerja Sama Pasokan Barang Retail', 'Penawaran kerja sama pengadaan stok barang kebutuhan sehari-hari untuk toko', 'Processing'),
('002/SM/VIII/2026', '2026-08-02', '2026-08-03', 'Dinas Perdagangan Kota', 'Perpanjangan Izin Usaha Perdagangan (SIUP)', 'Pemberitahuan batas waktu perpanjangan izin usaha toko retail', 'Processing'),
('003/SM/VIII/2026', '2026-08-04', '2026-08-04', 'Ratna Wijaya (Pelanggan)', 'Keluhan Produk Cacat dan Permintaan Retur', 'Surat keluhan pelanggan terkait produk elektronik yang diterima dalam kondisi rusak', 'Received'),
('004/SM/VIII/2026', '2026-08-05', '2026-08-06', 'Agensi Kreasi Promosi', 'Proposal Kerja Sama Event Promo Akhir Tahun', 'Proposal kegiatan promosi dan diskon akhir tahun di area toko', 'Received'),
('005/SM/VIII/2026', '2026-08-06', '2026-08-07', 'Dinas Tenaga Kerja', 'Undangan Pelatihan K3 dan Pelayanan Prima', 'Undangan pelatihan wajib bagi karyawan toko retail', 'Completed'),
('006/SM/VIII/2026', '2026-08-08', '2026-08-08', 'Tim Audit Internal', 'Laporan Hasil Stock Opname Bulanan', 'Laporan hasil audit stok barang bulan Agustus 2026', 'Completed');

--testing dispositions (terakhir, karena referensi ke 2 tabel di atas)--
-- Setiap surat didisposisikan ke lebih dari 1 penerima supaya semua 11 posisi
-- di struktur toko retail kebagian contoh data (dari Pemilik sampai staf paling bawah).
INSERT INTO dispositions (letter_id, recipient_id, instruction, disposition_date, status, notes) VALUES
-- Surat 1: Penawaran supplier -> Manajer Umum (evaluasi) & Staf Marketing Retail (cek stok di lantai toko)
(1, 3, 'Evaluasi penawaran supplier dan bandingkan dengan harga vendor saat ini', '2026-08-03', 'Pending', NULL),
(1, 8, 'Cek ketersediaan rak dan estimasi kebutuhan stok di lantai toko', '2026-08-03', 'In Progress', 'Sedang pendataan rak kosong'),

-- Surat 2: Perpanjangan SIUP -> Direktur (koordinasi) & Staf Administrasi (kumpulkan berkas)
(2, 2, 'Siapkan dokumen perpanjangan SIUP dan koordinasi dengan notaris', '2026-08-04', 'In Progress', NULL),
(2, 4, 'Kumpulkan berkas legalitas toko (NPWP, akta, dsb) untuk keperluan perpanjangan izin', '2026-08-04', 'Pending', NULL),

-- Surat 3: Keluhan pelanggan -> Supervisor Operasional (tindak lanjut) & Staf Operasional (eksekusi retur)
(3, 7, 'Tindak lanjuti keluhan pelanggan dan proses retur sesuai SOP', '2026-08-05', 'In Progress', 'Sudah dihubungi via telepon'),
(3, 10, 'Hubungi pelanggan dan atur jadwal pengambilan barang retur', '2026-08-05', 'Pending', NULL),

-- Surat 4: Proposal event promo -> Supervisor Marketing (kaji anggaran) & Staf Marketing Project (susun konsep)
(4, 6, 'Kaji proposal event promo dan hitung estimasi anggaran', '2026-08-07', 'Pending', NULL),
(4, 9, 'Susun draft konsep dekorasi dan materi promosi', '2026-08-07', 'Pending', NULL),

-- Surat 5: Undangan pelatihan K3 -> Sekretaris (koordinasi peserta) & Staf Operasional (mengikuti pelatihan)
(5, 5, 'Susun daftar karyawan yang akan mengikuti pelatihan dan konfirmasi kehadiran', '2026-08-07', 'Completed', 'Daftar peserta sudah dikirim ke penyelenggara'),
(5, 11, 'Ikuti pelatihan K3 dan pelayanan prima sesuai jadwal', '2026-08-07', 'Completed', 'Sertifikat pelatihan sudah diterima'),

-- Surat 6: Laporan stock opname -> Pemilik (tinjau & arahan tindak lanjut)
(6, 1, 'Tinjau laporan stock opname dan berikan arahan tindak lanjut', '2026-08-09', 'Completed', 'Sudah dibahas di rapat mingguan');

SELECT
    il.letter_number, il.subject, il.status AS letter_status,
    d.instruction, d.disposition_date, d.status AS disposition_status,
    r.name AS recipient_name, r.department
FROM incoming_letters il
JOIN dispositions d ON d.letter_id = il.id
JOIN recipients r ON r.id = d.recipient_id
ORDER BY d.disposition_date DESC;

SELECT*FROM incoming_letters;
SELECT*FROM recipients;
SELECT*FROM dispositions;