# Dokumentasi Skema Database KlikObat (Kelompok 22)

## 1. Entity Relationship Diagram (ERD)

```mermaid
erDiagram
    KATEGORI ||--o{ OBAT : "memiliki (1:N)"
    
    KATEGORI {
        int id_kategori PK "AUTO_INCREMENT"
        varchar_100 nama_kategori "NOT NULL, UNIQUE"
    }

    OBAT {
        int id_obat PK "AUTO_INCREMENT"
        varchar_150 nama_obat "NOT NULL"
        int id_kategori FK "ON DELETE RESTRICT"
        int stok "NOT NULL, DEFAULT 0"
        text indikasi "NOT NULL"
        text aturan_pakai "NOT NULL"
        text peringatan "NOT NULL"
    }

    ADMIN {
        int id_admin PK "AUTO_INCREMENT"
        varchar_50 username "NOT NULL, UNIQUE"
        varchar_255 password "NOT NULL (Bcrypt)"
    }

## 2. Spesifikasi Tabel (Kamus Data)

### a. Tabel `kategori`
| Kolom | Tipe Data | Constraint | Keterangan |
|---|---|---|---|
| `id_kategori` | `INT(11)` | `PRIMARY KEY`, `AUTO_INCREMENT` | ID unik kategori obat |
| `nama_kategori` | `VARCHAR(100)` | `NOT NULL`, `UNIQUE` | Nama kategori obat |

### b. Tabel `obat`
| Kolom | Tipe Data | Constraint | Keterangan |
|---|---|---|---|
| `id_obat` | `INT(11)` | `PRIMARY KEY`, `AUTO_INCREMENT` | ID unik obat |
| `nama_obat` | `VARCHAR(150)` | `NOT NULL` | Nama obat dan sediaan |
| `id_kategori` | `INT(11)` | `FOREIGN KEY` (`ON DELETE RESTRICT`) | Relasi ke `kategori.id_kategori` |
| `stok` | `INT(11)` | `NOT NULL`, `DEFAULT 0` | Stok (`0`: Habis, `1-10`: Menipis, `>10`: Tersedia) |
| `indikasi` | `TEXT` | `NOT NULL` | Deskripsi kegunaan obat |
| `aturan_pakai` | `TEXT` | `NOT NULL` | Dosis dan aturan konsumsi |
| `peringatan` | `TEXT` | `NOT NULL` | Peringatan dan kontraindikasi |

### c. Tabel `admin`
| Kolom | Tipe Data | Constraint | Keterangan |
|---|---|---|---|
| `id_admin` | `INT(11)` | `PRIMARY KEY`, `AUTO_INCREMENT` | ID unik administrator |
| `username` | `VARCHAR(50)` | `NOT NULL`, `UNIQUE` | Username login admin |
| `password` | `VARCHAR(255)` | `NOT NULL` | Bcrypt hash (`password_hash`) |