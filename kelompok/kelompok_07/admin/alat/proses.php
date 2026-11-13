<?php

require_once __DIR__ . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    try {
   
        if ($aksi === 'tambah' || $aksi === 'edit') {
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
            $kategoriId = filter_var($_POST['kategori_id'] ?? null, FILTER_VALIDATE_INT);
            $kode = trim($_POST['kode'] ?? '');
            $nama = trim($_POST['nama'] ?? '');
            $jenis = $_POST['jenis'] ?? '';
            $deskripsi = trim($_POST['deskripsi'] ?? '');
            $stok = filter_var($_POST['stok'] ?? null, FILTER_VALIDATE_INT);
            $lokasi = trim($_POST['lokasi'] ?? '');
            $kondisi = $_POST['kondisi'] ?? '';

            if (!$kategoriId || $kategoriId < 1) {
                throw new RuntimeException('Silakan pilih kategori.');
            }

            if ($kode === '' || $nama === '') {
                throw new RuntimeException('Kode dan nama wajib diisi.');
            }

            if (mb_strlen($kode) > 30 || mb_strlen($nama) > 150) {
                throw new RuntimeException('Kode atau nama melebihi batas karakter.');
            }

            if (!in_array($jenis, ['alat', 'ruang'], true)) {
                throw new RuntimeException('Jenis data tidak valid.');
            }

            if ($stok === false || $stok < 0) {
                throw new RuntimeException('Stok harus berupa angka 0 atau lebih.');
            }

            if (!in_array($kondisi, ['baik', 'rusak_ringan', 'rusak_berat'], true)) {
                throw new RuntimeException('Kondisi tidak valid.');
            }

            $cekKategori = db()->prepare(
                'SELECT COUNT(*) FROM kategori WHERE id = :id'
            );
            $cekKategori->execute(['id' => $kategoriId]);

            if ((int) $cekKategori->fetchColumn() === 0) {
                throw new RuntimeException('Kategori tidak ditemukan.');
            }

            $fotoBaru = null;

            if (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
                $fotoBaru = validasi_foto($_FILES['foto']);
            }

            if ($aksi === 'tambah') {
                $query = db()->prepare(
                    'INSERT INTO alat
                    (kategori_id, kode, nama, jenis, deskripsi, stok, lokasi, kondisi, foto)
                    VALUES
                    (:kategori_id, :kode, :nama, :jenis, :deskripsi, :stok, :lokasi, :kondisi, :foto)'
                );

                $query->execute([
                    'kategori_id' => $kategoriId,
                    'kode' => $kode,
                    'nama' => $nama,
                    'jenis' => $jenis,
                    'deskripsi' => $deskripsi !== '' ? $deskripsi : null,
                    'stok' => $stok,
                    'lokasi' => $lokasi !== '' ? $lokasi : null,
                    'kondisi' => $kondisi,
                    'foto' => $fotoBaru,
                ]);

            } else {
                if (!$id || $id < 1) {
                    throw new RuntimeException('ID alat tidak valid.');
                }

                $cekAlat = db()->prepare(
                    'SELECT foto FROM alat WHERE id = :id'
                );
                $cekAlat->execute(['id' => $id]);
                $alatLama = $cekAlat->fetch();

                if (!$alatLama) {
                    throw new RuntimeException('Data alat tidak ditemukan.');
                }

                $query = db()->prepare(
                    'UPDATE alat SET
                    kategori_id = :kategori_id,
                    kode = :kode,
                    nama = :nama,
                    jenis = :jenis,
                    deskripsi = :deskripsi,
                    stok = :stok,
                    lokasi = :lokasi,
                    kondisi = :kondisi,
                    foto = :foto
                    WHERE id = :id'
                );

                $query->execute([
                    'kategori_id' => $kategoriId,
                    'kode' => $kode,
                    'nama' => $nama,
                    'jenis' => $jenis,
                    'deskripsi' => $deskripsi !== '' ? $deskripsi : null,
                    'stok' => $stok,
                    'lokasi' => $lokasi !== '' ? $lokasi : null,
                    'kondisi' => $kondisi,
                    'foto' => $fotoBaru ?? $alatLama['foto'],
                    'id' => $id,
                ]);
            }

            if ($fotoBaru !== null) {
                $tujuanFoto = $folderUpload . $fotoBaru;

                if (!move_uploaded_file($_FILES['foto']['tmp_name'], $tujuanFoto)) {
                    throw new RuntimeException('Foto gagal disimpan ke folder uploads.');
                }

                if ($aksi === 'edit' && !empty($alatLama['foto'])) {
                    hapus_foto($alatLama['foto'], $folderUpload);
                }
            }

            set_flash(
                'success',
                $aksi === 'tambah'
                    ? 'Data alat berhasil ditambahkan.'
                    : 'Data alat berhasil diperbarui.'
            );
        }

        elseif ($aksi === 'hapus') {
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

            if (!$id || $id < 1) {
                throw new RuntimeException('ID alat tidak valid.');
            }

            $query = db()->prepare(
                'SELECT foto FROM alat WHERE id = :id'
            );
            $query->execute(['id' => $id]);
            $dataAlat = $query->fetch();

            if (!$dataAlat) {
                throw new RuntimeException('Data alat tidak ditemukan.');
            }

            try {
                $hapus = db()->prepare(
                    'DELETE FROM alat WHERE id = :id'
                );
                $hapus->execute(['id' => $id]);

            } catch (PDOException $error) {
                if ($error->getCode() === '23000') {
                    throw new RuntimeException(
                        'Data alat tidak dapat dihapus karena sudah tercatat dalam riwayat peminjaman.'
                    );
                }

                throw $error;
            }

            if (!empty($dataAlat['foto'])) {
                hapus_foto($dataAlat['foto'], $folderUpload);
            }

            set_flash('success', 'Data alat berhasil dihapus.');
        }

        else {
            throw new RuntimeException('Aksi tidak dikenal.');
        }

        header('Location: ' . url('admin/alat/index.php'));
        exit;

    } catch (PDOException $error) {
        if ($error->getCode() === '23000') {
            $pesanError = 'Kode alat sudah digunakan atau data memiliki relasi yang tidak dapat diubah.';
        } else {
            $pesanError = 'Terjadi kesalahan saat memproses data alat.';
        }
    } catch (RuntimeException $error) {
        $pesanError = $error->getMessage();
    } 
}
?>