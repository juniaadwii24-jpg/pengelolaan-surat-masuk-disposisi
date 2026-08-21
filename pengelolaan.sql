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

--testing recipients--
INSERT INTO recipients (name, position, department, email) VALUES
('Danendra Vero', 'Direktur Utama', 'Manajemen', 'danendra.vero@gmail.com'),
('Ahmad Fauzi', 'Kepala Divisi', 'IT', 'ahmad.fauzi@gmail.com'),
('Alexandra Drazeva Janela ', 'Supervisor', 'Keuangan', 'alexandra.drazeva@gmail.com'),
('Bagas Pratama', 'Staff Senior', 'Legal', 'bagas.pratama@gmail.com'),
('Maya Srikandi', 'Manager', 'Pemasaran', 'maya.srikandi@gmail.com');

--testing incoming_letters (harus sebelum dispositions)--
INSERT INTO incoming_letters (letter_number, letter_date, received_date, sender, subject, description, status) VALUES
('001/SM/VIII/2026', '2026-08-01', '2026-08-02', 'PT Solusi Teknologi', 'Penawaran Aplikasi ERP', 'Surat penawaran lisensi dan pengadaan sistem ERP perusahaan', 'Received'),
('002/SM/VIII/2026', '2026-08-02', '2026-08-02', 'Kementerian Keuangan', 'Pemberitahuan Audit Pajak', 'Surat pemberitahuan pelaksanaan audit pajak tahunan', 'Processing'),
('003/SM/VIII/2026', '2026-08-03', '2026-08-05', 'PT Mitra Nusantara', 'Draft Kontrak Kerjasama', 'Review draft perjanjian kerja sama investasi baru', 'Processing'),
('004/SM/VIII/2026', '2026-08-04', '2026-08-04', 'Agensi Kreatif Indonesia', 'Proposal Kampanye Branding', 'Proposal strategi pemasaran digital dan iklan semester II', 'Received'),
('005/SM/VIII/2026', '2026-08-05', '2026-08-06', 'Dinas Pelayanan Terpadu', 'Laporan Kinerja Tahunan', 'Undangan penyampaian laporan evaluasi dan kinerja direksi', 'Completed');

--testing dispositions (terakhir, karena referensi ke 2 tabel di atas)--
INSERT INTO dispositions (letter_id, recipient_id, instruction, disposition_date, status, notes) VALUES
(1, 2, 'Pelajari spesifikasi teknis ERP dan buat analisis kebutuhan IT', '2026-08-03', 'Pending', NULL),
(2, 3, 'Siapkan dokumen laporan keuangan dan berkas perpajakan tahunan', '2026-08-03', 'In Progress', 'Pemeriksaan berkas internal sedang berjalan'),
(3, 4, 'Lakukan review klausul pasal pada draft kontrak kerjasama', '2026-08-06', 'In Progress', 'Draft sedang dikaji oleh tim legal'),
(4, 5, 'Evaluasi proposal kampanye branding dan hitung estimasi ROI', '2026-08-05', 'Pending', NULL),
(5, 1, 'Hadir tepat waktu dan siapkan paparan laporan evaluasi kinerja', '2026-08-07', 'Completed', 'Danendra telah mengonfirmasi kehadiran');

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
