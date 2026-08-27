DROP TABLE IF EXISTS dispositions;
DROP TABLE IF EXISTS incoming_letters;
DROP TABLE IF EXISTS recipients;
DROP TABLE IF EXISTS users;


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

CREATE TABLE users (
    id          SERIAL PRIMARY KEY,
    username    VARCHAR(50)  NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    full_name   VARCHAR(150) NOT NULL,
    role        VARCHAR(50)  NOT NULL DEFAULT 'staff',
    is_active   BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at  TIMESTAMP    NOT NULL DEFAULT NOW()
);
 
CREATE INDEX idx_users_username ON users(username);
 
--  tabel pendukung untuk login (password: <namauser>123, misal admin123, kepsek123, dst)
INSERT INTO users (username, password, full_name, role) VALUES
('admin',         '$2y$10$GN2Te7RX50orDokj5fG6EOlx.sjyx4fJecoXdk8JPZQUcGIsuhpMK', 'Dita Vandisa Jelita', 'admin'),
('kepsek',        '$2y$10$wZdnmuf2MMUqihWj/3GkLeL0xextBCdU8/s0UmQlcMku9mq0v8ED6', 'Ahmad Zulkarnain',    'kepala sekolah'),
('wakahumas',     '$2y$10$6Fqxp6KkFJwoi61/op67XOJo9ZXM4RjswBq7qlzMvvgkW/MSsTVO6', 'Danendra Vero',       'waka hubinmas'),
('wakakurikulum', '$2y$10$8dmlPyXUtVoaoaiZ7iDQRuC0tVLFcDT7K0Jye2jNB3D1zKkB3rHpm', 'Rian Saputra',        'waka kurikulum'),
('keptu',         '$2y$10$udCzePJ2S9QOddu2q78iMOpsafM5Pu6.aI8UbhlsmiH3yod/wzdRi', 'Bagas Pratama',       'Tata Usaha'),
('sektu',         '$2y$10$YIcu7JI9aMB0e0uMYr2bxOt1Mf5GVYCxZdHLUCAEUGdr36ZzVeMii', 'Maya Srikandi',       'Tata Usaha');


--testing recipients (struktur organisasi SMK sesuai bagan)--
INSERT INTO recipients (name, position, department, email) VALUES
('Ahmad Zulkarnain', 'Kepala Sekolah', 'Manajemen Sekolah', 'ahmad.zulkarnain@smk.sch.id'),            -- id 1
('Danendra Vero', 'Waka Hubungan Industri (Humas)', 'Hubinmas', 'danendra.vero@smk.sch.id'),             -- id 2
('Rian Saputra', 'Waka Kurikulum', 'Kurikulum', 'rian.saputra@smk.sch.id'),                              -- id 3
('Bagas Pratama', 'Kepala Tata Usaha', 'Tata Usaha (TU)', 'bagas.pratama@smk.sch.id'),                  -- id 4
('Maya Srikandi', 'Sekretaris Tata Usaha', 'Tata Usaha (TU)', 'maya.srikandi@smk.sch.id');                -- id 5

-- Incoming Letters --
INSERT INTO incoming_letters (letter_number, letter_date, received_date, sender, subject, description, status) VALUES
('001/SMK/VIII/2026', '2026-08-01', '2026-08-02', 'Dinas Pendidikan Provinsi', 'Undangan Rapat Koordinasi Asesmen Nasional', 'Pemberitahuan rapat persiapan Asesmen Nasional (ANBK) tingkat SMK', 'Processing'),
('002/SMK/VIII/2026', '2026-08-02', '2026-08-03', 'PT Auto Perkasa Teknik', 'Penawaran Kerja Sama Tempat Praktik Kerja Lapangan (PKL)', 'Penawaran kuota siswa PKL untuk Jurusan Teknik Kendaraan Ringan (TKR)', 'Processing'),
('003/SMK/VIII/2026', '2026-08-04', '2026-08-04', 'PT Solusi Informatika Utama', 'Permohonan Rekrutmen Lulusan (Loker SMK)', 'Permintaan daftar calon lulusan jurusan Rekayasa Perangkat Lunak untuk seleksi kerja', 'Received'),
('004/SMK/VIII/2026', '2026-08-05', '2026-08-06', 'Universitas Negeri', 'Undangan Lomba Kompetensi Siswa (LKS) Tingkat Kota', 'Undangan pengiriman kontingen siswa untuk cabang lomba teknologi informasi dan otomotif', 'Received'),
('005/SMK/VIII/2026', '2026-08-06', '2026-08-07', 'Puskesmas Kecamatan', 'Pemberitahuan Program Pelaksanaan Imunisasi & Cek Kesehatan Siswa', 'Jadwal pemeriksaan kesehatan berkala bagi siswa kelas X tahun ajaran baru', 'Completed');

-- Dispositions --
INSERT INTO dispositions (letter_id, recipient_id, instruction, disposition_date, status, notes) VALUES
-- Surat 1: Dinas Pendidikan -> Waka Kurikulum
(1, 3, 'Koordinasikan kesiapan laboratorium komputer dan susun jadwal proctor ANBK', '2026-08-03', 'In Progress', 'Sedang pendataan PC laboratorium'),
(2, 2, 'Kaji draft MOU kerja sama PKL dan petakan siswa yang akan ditempatkan', '2026-08-04', 'In Progress', 'Draft MOU dalam peninjauan'),
(2, 4, 'Siapkan surat tugas dan berkas administrasi pengantar PKL siswa', '2026-08-04', 'Pending', NULL),
(5, 5, 'Edarkan surat pemberitahuan ke wali kelas X dan konfirmasi jadwal pelaksanaan ke Puskesmas', '2026-08-07', 'Completed', 'Surat sudah diteruskan ke para wali kelas'),
(1, 1, 'Tinjau persiapan rapat koordinasi dan hadir pada rapat pleno Dinas Pendidikan', '2026-08-08', 'In Progress', 'Agenda sudah dijadwalkan');

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
SELECT*fROM users;

SELECT username, password, is_active FROM users WHERE username = 'kepsek';
SELECT username,password FROM users;


