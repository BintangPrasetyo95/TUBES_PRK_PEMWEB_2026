-- skema database 
-- Impor lewat phpMyAdmin (tab Import) atau: mysql -u root < schema.sql


CREATE DATABASE IF NOT EXISTS cekhak CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cekhak;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS audit_logs, review_logs, analysis_results, case_files, case_data, cases, requirements, document_types, users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('pengguna','reviewer','admin') NOT NULL DEFAULT 'pengguna',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE document_types (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nama VARCHAR(100) NOT NULL,
    deskripsi TEXT NULL,
    aktif TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE requirements (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    document_type_id INT UNSIGNED NOT NULL,
    kode VARCHAR(50) NOT NULL,
    label VARCHAR(200) NOT NULL,
    tipe_input ENUM('text','textarea','date','number','file','checkbox') NOT NULL DEFAULT 'text',
    wajib TINYINT(1) NOT NULL DEFAULT 1,
    aturan_format ENUM('tanggal','angka','nomor','email') NULL,
    penjelasan TEXT NULL,
    sumber VARCHAR(255) NULL,
    tanggal_pembaruan DATE NULL,
    status_verifikasi ENUM('belum_diverifikasi','terverifikasi') NOT NULL DEFAULT 'belum_diverifikasi',
    urutan INT NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY uq_req_kode (document_type_id, kode),
    CONSTRAINT fk_req_type FOREIGN KEY (document_type_id) REFERENCES document_types (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cases (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    document_type_id INT UNSIGNED NOT NULL,
    judul VARCHAR(200) NOT NULL,
    status ENUM('draft','belum_lengkap','siap_ditinjau','sedang_ditinjau','selesai') NOT NULL DEFAULT 'draft',
    reviewer_id INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_cases_user (user_id),
    KEY idx_cases_status (status),
    CONSTRAINT fk_cases_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_cases_type FOREIGN KEY (document_type_id) REFERENCES document_types (id),
    CONSTRAINT fk_cases_reviewer FOREIGN KEY (reviewer_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE case_data (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    case_id INT UNSIGNED NOT NULL,
    requirement_id INT UNSIGNED NOT NULL,
    nilai TEXT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_case_req (case_id, requirement_id),
    CONSTRAINT fk_cdata_case FOREIGN KEY (case_id) REFERENCES cases (id) ON DELETE CASCADE,
    CONSTRAINT fk_cdata_req FOREIGN KEY (requirement_id) REFERENCES requirements (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE case_files (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    case_id INT UNSIGNED NOT NULL,
    requirement_id INT UNSIGNED NOT NULL,
    nama_asli VARCHAR(255) NOT NULL,
    nama_tersimpan VARCHAR(255) NOT NULL,
    mime VARCHAR(100) NOT NULL,
    ukuran INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_cfiles_case (case_id),
    CONSTRAINT fk_cfiles_case FOREIGN KEY (case_id) REFERENCES cases (id) ON DELETE CASCADE,
    CONSTRAINT fk_cfiles_req FOREIGN KEY (requirement_id) REFERENCES requirements (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE analysis_results (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    case_id INT UNSIGNED NOT NULL,
    persentase DECIMAL(5,2) NOT NULL DEFAULT 0,
    jumlah_terpenuhi INT UNSIGNED NOT NULL DEFAULT 0,
    jumlah_berlaku INT UNSIGNED NOT NULL DEFAULT 0,
    hasil_json LONGTEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_ares_case (case_id),
    CONSTRAINT fk_ares_case FOREIGN KEY (case_id) REFERENCES cases (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE review_logs (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    case_id INT UNSIGNED NOT NULL,
    reviewer_id INT UNSIGNED NOT NULL,
    catatan TEXT NULL,
    status_lama VARCHAR(20) NULL,
    status_baru VARCHAR(20) NULL,
    dibaca_pengguna TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_rlogs_case (case_id),
    CONSTRAINT fk_rlogs_case FOREIGN KEY (case_id) REFERENCES cases (id) ON DELETE CASCADE,
    CONSTRAINT fk_rlogs_reviewer FOREIGN KEY (reviewer_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_logs (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NULL,
    aksi VARCHAR(100) NOT NULL,
    entitas VARCHAR(50) NOT NULL,
    entitas_id INT UNSIGNED NULL,
    detail TEXT NULL,
    ip VARCHAR(45) NOT NULL DEFAULT '',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_audit_user (user_id),
    KEY idx_audit_created (created_at),
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
