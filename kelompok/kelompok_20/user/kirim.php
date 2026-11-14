<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

wajibLogin();

$jenis       = $_POST['jenis'] ?? 'pengaduan';
$judul       = trim($_POST['judul'] ?? '');
$kategori_id = $_POST['kategori_id'] ?? '';
$lokasi      = trim($_POST['lokasi'] ?? '');
$isi         = trim($_POST['isi'] ?? '');
$is_public   = isset($_POST['is_public']) ? 1 : (empty($_POST) ? 1 : 0);
$errors      = [];

try {
    $stmtKat = $pdo->query("SELECT id, nama_kategori FROM kategori ORDER BY nama_kategori ASC");
    $daftarKategori = $stmtKat->fetchAll();
} catch (PDOException $e) {
    $daftarKategori = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifikasiCsrf();

    if (!in_array($jenis, ['pengaduan', 'aspirasi'], true)) {
        $errors[] = 'Jenis laporan harus dipilih: Pengaduan atau Aspirasi.';
    }

    if ($judul === '') {
        $errors[] = 'Judul laporan wajib diisi.';
    } elseif (mb_strlen($judul) < 5 || mb_strlen($judul) > 255) {
        $errors[] = 'Judul laporan minimal 5 karakter dan maksimal 255 karakter.';
    }

    if (empty($kategori_id)) {
        $errors[] = 'Silakan pilih kategori yang sesuai.';
    }

    if ($lokasi === '') {
        $errors[] = 'Lokasi kejadian atau objek aspirasi wajib diisi.';
    } elseif (mb_strlen($lokasi) > 255) {
        $errors[] = 'Lokasi maksimal 255 karakter.';
    }

    if ($isi === '') {
        $errors[] = 'Isi laporan wajib diisi.';
    } elseif (mb_strlen($isi) < 15) {
        $errors[] = 'Isi laporan terlalu singkat, jelaskan minimal 15 karakter.';
    }

    $namaFotoDisimpan = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
        $fileError = $_FILES['foto']['error'];
        $fileSize  = $_FILES['foto']['size'];
        $tmpName   = $_FILES['foto']['tmp_name'];
        $fileName  = $_FILES['foto']['name'];

        if ($fileError !== UPLOAD_ERR_OK) {
            $errors[] = 'Terjadi kesalahan saat mengunggah foto.';
        } else {
            if ($fileSize > 2 * 1024 * 1024) {
                $errors[] = 'Ukuran foto maksimal adalah 2 MB.';
            }

            $ekstensi = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            if (!in_array($ekstensi, ['jpg', 'jpeg', 'png'], true)) {
                $errors[] = 'Format foto harus berupa JPG, JPEG, atau PNG.';
            }

            if (empty($errors) && function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $tmpName);
                finfo_close($finfo);

                if (!in_array($mimeType, ['image/jpeg', 'image/png'], true)) {
                    $errors[] = 'Berkas foto bukan format gambar yang valid.';
                }
            }

            if (empty($errors)) {
                $namaFotoDisimpan = 'laporan_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ekstensi;
                $folderTujuan = __DIR__ . '/../assets/uploads/';

                if (!is_dir($folderTujuan)) {
                    mkdir($folderTujuan, 0755, true);
                }

                if (!move_uploaded_file($tmpName, $folderTujuan . $namaFotoDisimpan)) {
                    $errors[] = 'Gagal menyimpan berkas foto ke server.';
                    $namaFotoDisimpan = null;
                }
            }
        }
    }

    if (empty($errors)) {
        try {
            $sql = "INSERT INTO laporan (user_id, kategori_id, jenis, judul, isi, lokasi, foto, is_public, status)
                    VALUES (:user_id, :kategori_id, :jenis, :judul, :isi, :lokasi, :foto, :is_public, 'menunggu')";
            
            $stmtInsert = $pdo->prepare($sql);
            $stmtInsert->execute([
                ':user_id'     => $_SESSION['user_id'],
                ':kategori_id' => $kategori_id,
                ':jenis'       => $jenis,
                ':judul'       => $judul,
                ':isi'         => $isi,
                ':lokasi'      => $lokasi,
                ':foto'        => $namaFotoDisimpan,
                ':is_public'   => $is_public
            ]);

            setFlash('success', 'Laporan Anda berhasil dikirim dan sedang menunggu penanganan.');
            header('Location: /user/laporan_saya.php');
            exit;

        } catch (PDOException $e) {
            $errors[] = 'Terjadi kesalahan sistem saat menyimpan laporan.';
        }
    }
}

$pageTitle = 'Sampaikan Laporan';
require_once __DIR__ . '/../includes/header.php';
?>

<div style="max-width: 800px; margin: 0 auto; padding-bottom: 2rem;">
    
    <div class="card" style="padding: 2.25rem 2rem;">
        <div style="margin-bottom: 1.75rem; border-bottom: 1px solid var(--color-border); padding-bottom: 1rem;">
            <h1 class="card-title" style="font-size: 1.6rem; margin-bottom: 0.35rem;">Sampaikan Aspirasi atau Pengaduan</h1>
            <p style="color: var(--color-text-muted); font-size: 0.95rem; margin: 0;">
                Tuliskan keluhan atau saran Anda secara jelas untuk kemajuan kampus FISIP.
            </p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger" style="margin-bottom: 1.5rem;">
                <ul style="margin: 0; padding-left: 1.25rem;">
                    <?php foreach ($errors as $error): ?>
                        <li><?= sanitize($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="/user/kirim.php" method="POST" enctype="multipart/form-data">
            <?= csrfField() ?>

            <!-- Pilihan Jenis Laporan -->
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label class="form-label">Jenis Laporan <span style="color: var(--color-danger);">*</span></label>
                <div style="display: flex; gap: 1.5rem; flex-wrap: wrap; margin-top: 0.35rem;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.95rem;">
                        <input type="radio" name="jenis" value="pengaduan" <?= ($jenis === 'pengaduan') ? 'checked' : '' ?>>
                        <strong>Pengaduan</strong> (Keluhan fasilitas/layanan)
                    </label>
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.95rem;">
                        <input type="radio" name="jenis" value="aspirasi" <?= ($jenis === 'aspirasi') ? 'checked' : '' ?>>
                        <strong>Aspirasi</strong> (Saran/usulan perbaikan)
                    </label>
                </div>
            </div>

            <!-- Judul Laporan -->
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label for="judul" class="form-label">Judul Laporan <span style="color: var(--color-danger);">*</span></label>
                <input type="text" id="judul" name="judul" class="form-control" 
                       placeholder="Contoh: AC Ruang Kuliah D2.1 Rusak" 
                       value="<?= sanitize($judul) ?>" required maxlength="255">
            </div>

            <!-- Kategori & Lokasi -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
                <div>
                    <label for="kategori_id" class="form-label">Kategori <span style="color: var(--color-danger);">*</span></label>
                    <select id="kategori_id" name="kategori_id" class="form-control" required>
                        <option value="">-- Pilih Kategori --</option>
                        <?php foreach ($daftarKategori as $kat): ?>
                            <option value="<?= (int)$kat['id'] ?>" <?= ((string)$kategori_id === (string)$kat['id']) ? 'selected' : '' ?>>
                                <?= sanitize($kat['nama_kategori']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="lokasi" class="form-label">Lokasi Kejadian <span style="color: var(--color-danger);">*</span></label>
                    <input type="text" id="lokasi" name="lokasi" class="form-control" 
                           placeholder="Contoh: Gedung B Lantai 2 FISIP" 
                           value="<?= sanitize($lokasi) ?>" required maxlength="255">
                </div>
            </div>

            <!-- Isi Keterangan -->
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label for="isi" class="form-label">Rincian Laporan <span style="color: var(--color-danger);">*</span></label>
                <textarea id="isi" name="isi" class="form-control" rows="5" 
                          placeholder="Jelaskan secara jelas kronologi kejadian atau saran yang ingin Anda sampaikan..." 
                          required><?= sanitize($isi) ?></textarea>
            </div>

            <!-- Upload Foto -->
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label for="foto" class="form-label">Lampiran Foto Bukti <span style="color: var(--color-text-muted); font-weight: normal;">(Opsional, Maks. 2 MB)</span></label>
                <input type="file" id="foto" name="foto" class="form-control" accept=".jpg, .jpeg, .png">
                
                <div id="preview-box" style="display: none; margin-top: 1rem;">
                    <p style="font-size: 0.85rem; font-weight: 600; color: var(--color-primary); margin-bottom: 0.5rem;">Pratinjau Foto:</p>
                    <img id="preview-img" src="" alt="Pratinjau" style="max-height: 250px; border-radius: var(--radius); border: 1px solid var(--color-border); display: block;">
                </div>
            </div>

            <!-- Checkbox Publik/Privat -->
            <div style="margin-bottom: 2rem; padding: 1.25rem; background-color: var(--color-bg); border-radius: var(--radius); border: 1px solid var(--color-border);">
                <label style="display: flex; align-items: flex-start; gap: 0.75rem; cursor: pointer; margin-bottom: 0;">
                    <input type="checkbox" name="is_public" value="1" <?= $is_public ? 'checked' : '' ?> style="margin-top: 0.25rem; width: 18px; height: 18px;">
                    <div>
                        <strong style="color: var(--color-primary); font-size: 0.95rem;">Publikasikan Laporan Ini ke Laporan Publik</strong>
                        <p style="font-size: 0.88rem; color: var(--color-text-muted); margin: 0.25rem 0 0;">
                            Jika dicentang, laporan dapat dibaca oleh umum untuk transparansi kampus. Jika tidak dicentang, laporan hanya dapat dilihat oleh Anda dan Admin (Privat).
                        </p>
                    </div>
                </label>
            </div>

            <!-- Tombol Aksi -->
            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <a href="/index.php" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary" style="padding: 0.75rem 1.75rem;">
                    Kirim Laporan Sekarang
                </button>
            </div>
        </form>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fotoInput = document.getElementById('foto');
    const previewBox = document.getElementById('preview-box');
    const previewImg = document.getElementById('preview-img');

    if (fotoInput && previewBox && previewImg) {
        fotoInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file && file.type.match(/^image\/(jpeg|png)$/)) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    previewBox.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                previewBox.style.display = 'none';
                previewImg.src = '';
            }
        });
    }
});
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
