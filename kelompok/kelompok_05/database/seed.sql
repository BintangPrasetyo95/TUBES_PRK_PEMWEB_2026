-- Impor SETELAH schema.sql.
-- Semua akun memakai kata sandi: password   (hanya untuk pengembangan)
-- Persyaratan di bawah adalah DATA CONTOH tanpa sumber hukum, berstatus belum_diverifikasi.
-- Isi persyaratan sungguhan harus diambil dari sumber resmi lalu diverifikasi reviewer.

USE cekhak;
SET NAMES utf8mb4;

INSERT INTO users (id, nama, email, password_hash, role) VALUES
(1, 'Admin CEKHAK',     'admin@cekhak.test',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
(2, 'Reviewer Contoh',  'reviewer@cekhak.test',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'reviewer'),
(3, 'Pengguna Contoh',  'pengguna@cekhak.test',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'pengguna'),
(4, 'Pengguna Kedua',   'pengguna2@cekhak.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'pengguna');

INSERT INTO document_types (id, nama, deskripsi, aktif) VALUES
(1, 'Surat Kuasa',       'Pemeriksaan kelengkapan administratif surat kuasa.', 1),
(2, 'Surat Pernyataan',  'Pemeriksaan kelengkapan administratif surat pernyataan.', 1),
(3, 'Surat Pengaduan',   'Pemeriksaan kelengkapan administratif surat pengaduan.', 1);

INSERT INTO requirements
(id, document_type_id, kode, label, tipe_input, wajib, aturan_format, penjelasan, sumber, tanggal_pembaruan, status_verifikasi, urutan) VALUES
-- Surat Kuasa
(1, 1, 'pemberi_nama',        'Nama pemberi kuasa',                'text',     1, NULL,      'Nama lengkap pihak yang memberikan kuasa.',          'Data contoh pengembangan (belum bersumber)', '2026-11-01', 'belum_diverifikasi', 1),
(2, 1, 'pemberi_alamat',      'Alamat pemberi kuasa',              'textarea', 1, NULL,      'Alamat lengkap pemberi kuasa.',                       'Data contoh pengembangan (belum bersumber)', '2026-11-01', 'belum_diverifikasi', 2),
(3, 1, 'penerima_nama',       'Nama penerima kuasa',               'text',     1, NULL,      'Nama lengkap pihak yang menerima kuasa.',             'Data contoh pengembangan (belum bersumber)', '2026-11-01', 'belum_diverifikasi', 3),
(4, 1, 'penerima_alamat',     'Alamat penerima kuasa',             'textarea', 1, NULL,      'Alamat lengkap penerima kuasa.',                      'Data contoh pengembangan (belum bersumber)', '2026-11-01', 'belum_diverifikasi', 4),
(5, 1, 'tujuan_kuasa',        'Tujuan atau uraian kuasa',          'textarea', 1, NULL,      'Hal yang dikuasakan, diuraikan dengan jelas.',        'Data contoh pengembangan (belum bersumber)', '2026-11-01', 'belum_diverifikasi', 5),
(6, 1, 'tanggal_surat',       'Tanggal surat',                     'date',     1, 'tanggal', 'Tanggal surat dibuat.',                               'Data contoh pengembangan (belum bersumber)', '2026-11-01', 'belum_diverifikasi', 6),
(7, 1, 'tempat_surat',        'Tempat surat dibuat',               'text',     1, NULL,      'Kota atau tempat surat dibuat.',                      'Data contoh pengembangan (belum bersumber)', '2026-11-01', 'belum_diverifikasi', 7),
(8, 1, 'lampiran_identitas',  'Lampiran identitas para pihak',     'file',     1, NULL,      'Salinan identitas yang menyertai surat.',             'Data contoh pengembangan (belum bersumber)', '2026-11-01', 'belum_diverifikasi', 8),
(9, 1, 'tanda_tangan',        'Surat sudah ditandatangani',        'checkbox', 1, NULL,      'Centang bila surat sudah ditandatangani.',            'Data contoh pengembangan (belum bersumber)', '2026-11-01', 'belum_diverifikasi', 9),
-- Surat Pernyataan
(10, 2, 'pernyataan_nama',    'Nama pihak yang menyatakan',        'text',     1, NULL,      'Nama lengkap pembuat pernyataan.',                    'Data contoh pengembangan (belum bersumber)', '2026-11-01', 'belum_diverifikasi', 1),
(11, 2, 'pernyataan_alamat',  'Alamat pihak yang menyatakan',      'textarea', 1, NULL,      'Alamat lengkap pembuat pernyataan.',                  'Data contoh pengembangan (belum bersumber)', '2026-11-01', 'belum_diverifikasi', 2),
(12, 2, 'isi_pernyataan',     'Isi pernyataan',                    'textarea', 1, NULL,      'Isi pernyataan yang dibuat.',                         'Data contoh pengembangan (belum bersumber)', '2026-11-01', 'belum_diverifikasi', 3),
(13, 2, 'tanggal_surat',      'Tanggal surat',                     'date',     1, 'tanggal', 'Tanggal surat dibuat.',                               'Data contoh pengembangan (belum bersumber)', '2026-11-01', 'belum_diverifikasi', 4),
(14, 2, 'tempat_surat',       'Tempat surat dibuat',               'text',     1, NULL,      'Kota atau tempat surat dibuat.',                      'Data contoh pengembangan (belum bersumber)', '2026-11-01', 'belum_diverifikasi', 5),
(15, 2, 'tanda_tangan',       'Surat sudah ditandatangani',        'checkbox', 1, NULL,      'Centang bila surat sudah ditandatangani.',            'Data contoh pengembangan (belum bersumber)', '2026-11-01', 'belum_diverifikasi', 6),
(16, 2, 'dokumen_pendukung',  'Dokumen pendukung (jika ada)',      'file',     0, NULL,      'Lampiran pendukung, tidak wajib.',                    'Data contoh pengembangan (belum bersumber)', '2026-11-01', 'belum_diverifikasi', 7),
-- Surat Pengaduan
(17, 3, 'pelapor_nama',       'Nama pelapor',                      'text',     1, NULL,      'Nama lengkap pihak yang mengadu.',                    'Data contoh pengembangan (belum bersumber)', '2026-11-01', 'belum_diverifikasi', 1),
(18, 3, 'pelapor_kontak',     'Nomor kontak pelapor',              'text',     1, 'nomor',   'Nomor telepon yang dapat dihubungi.',                 'Data contoh pengembangan (belum bersumber)', '2026-11-01', 'belum_diverifikasi', 2),
(19, 3, 'terlapor_nama',      'Nama atau pihak terlapor',          'text',     1, NULL,      'Pihak yang diadukan.',                                'Data contoh pengembangan (belum bersumber)', '2026-11-01', 'belum_diverifikasi', 3),
(20, 3, 'kronologi',          'Kronologi kejadian',                'textarea', 1, NULL,      'Uraian kejadian secara runtut.',                      'Data contoh pengembangan (belum bersumber)', '2026-11-01', 'belum_diverifikasi', 4),
(21, 3, 'waktu_kejadian',     'Waktu kejadian',                    'date',     1, 'tanggal', 'Tanggal kejadian.',                                   'Data contoh pengembangan (belum bersumber)', '2026-11-01', 'belum_diverifikasi', 5),
(22, 3, 'tempat_kejadian',    'Tempat kejadian',                   'text',     1, NULL,      'Lokasi kejadian.',                                    'Data contoh pengembangan (belum bersumber)', '2026-11-01', 'belum_diverifikasi', 6),
(23, 3, 'bukti_pendukung',    'Bukti pendukung',                   'file',     1, NULL,      'Berkas bukti yang menyertai pengaduan.',              'Data contoh pengembangan (belum bersumber)', '2026-11-01', 'belum_diverifikasi', 7);

-- Pemeriksaan contoh milik Pengguna Contoh (id 3)
INSERT INTO cases (id, user_id, document_type_id, judul, status, reviewer_id) VALUES
(1, 3, 1, 'Surat kuasa pengurusan dokumen (contoh)', 'draft',           NULL),
(2, 3, 3, 'Pengaduan layanan kos (contoh)',          'belum_lengkap',   NULL),
(3, 3, 2, 'Pernyataan domisili (contoh)',            'sedang_ditinjau', 2);

INSERT INTO case_data (case_id, requirement_id, nilai) VALUES
(1, 1, 'Contoh Pemberi'),
(1, 3, 'Contoh Penerima'),
(2, 17, 'Contoh Pelapor'),
(2, 18, 'abc'),
(2, 19, 'Pengelola Kos Contoh'),
(2, 20, 'Kronologi singkat contoh untuk keperluan pengembangan.'),
(2, 22, 'Bandar Lampung'),
(3, 10, 'Contoh Pernyataan'),
(3, 11, 'Jalan Contoh No. 1, Bandar Lampung'),
(3, 12, 'Isi pernyataan contoh.'),
(3, 13, '2026-11-01'),
(3, 14, 'Bandar Lampung'),
(3, 15, '1');

-- Hasil analisis contoh (supaya halaman hasil, laporan, dan reviewer bisa dibangun sebelum mesin selesai)
INSERT INTO analysis_results (case_id, persentase, jumlah_terpenuhi, jumlah_berlaku, hasil_json) VALUES
(2, 57.14, 4, 7, '[{"requirement_id":17,"status":"lengkap","pesan":""},{"requirement_id":18,"status":"perlu_diperiksa","pesan":"Format nomor tidak valid."},{"requirement_id":19,"status":"lengkap","pesan":""},{"requirement_id":20,"status":"lengkap","pesan":""},{"requirement_id":21,"status":"belum_lengkap","pesan":"Wajib diisi."},{"requirement_id":22,"status":"lengkap","pesan":""},{"requirement_id":23,"status":"belum_lengkap","pesan":"Berkas belum diunggah."}]'),
(3, 100.00, 6, 6, '[{"requirement_id":10,"status":"lengkap","pesan":""},{"requirement_id":11,"status":"lengkap","pesan":""},{"requirement_id":12,"status":"lengkap","pesan":""},{"requirement_id":13,"status":"lengkap","pesan":""},{"requirement_id":14,"status":"lengkap","pesan":""},{"requirement_id":15,"status":"lengkap","pesan":""}]');

INSERT INTO review_logs (case_id, reviewer_id, catatan, status_lama, status_baru, dibaca_pengguna) VALUES
(3, 2, 'Contoh catatan reviewer: mohon periksa kembali tanggal surat.', 'siap_ditinjau', 'sedang_ditinjau', 0);

INSERT INTO audit_logs (user_id, aksi, entitas, entitas_id, detail, ip) VALUES
(1, 'seed', 'users', 1, 'Data contoh awal dimuat', '127.0.0.1'),
(3, 'buat_pemeriksaan', 'cases', 1, 'Contoh log aktivitas', '127.0.0.1');
