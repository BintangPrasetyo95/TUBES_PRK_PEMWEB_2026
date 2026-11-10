<?php
// Fungsi-fungsi untuk mengelola data pengguna (user) dan autentikasi

// 1. Cari 1 data pengguna berdasarkan alamat email
function cariPenggunaByEmail($email) {
    global $pdo;

    $sql = "SELECT * FROM user WHERE email = ? LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        return null;
    }

    return $user;
}

// 2. Ambil 1 data pengguna berdasarkan ID
function ambilPenggunaById($id) {
    global $pdo;

    $sql = "SELECT * FROM user WHERE id = ? LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        return null;
    }

    return $user;
}

// 3. Validasi input form pendaftaran (Register)
// Mengembalikan array berisi pesan error per kolom (kosong jika semua valid)
function validasiRegister($data) {
    $errors = [];

    $nama = trim($data['nama'] ?? '');
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';
    $konfirmasi = $data['konfirmasi_password'] ?? '';
    $peran = $data['peran'] ?? '';

    // Validasi nama lengkap (2 sampai 100 karakter)
    if ($nama === '') {
        $errors['nama'] = 'Nama lengkap wajib diisi.';
    } elseif (strlen($nama) < 2 || strlen($nama) > 100) {
        $errors['nama'] = 'Nama lengkap harus antara 2 sampai 100 karakter.';
    }

    // Validasi email (format valid, maksimal 150 karakter, dan belum terdaftar)
    if ($email === '') {
        $errors['email'] = 'Alamat email wajib diisi.';
    } elseif (strlen($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Format alamat email tidak valid.';
    } else {
        $sudahAda = cariPenggunaByEmail($email);
        if ($sudahAda !== null) {
            $errors['email'] = 'Email ini sudah terdaftar. Silakan gunakan email lain.';
        }
    }

    // Validasi password (minimal 8 karakter, maksimal 72 karakter)
    if ($password === '') {
        $errors['password'] = 'Password wajib diisi.';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password minimal harus 8 karakter.';
    } elseif (strlen($password) > 72) {
        $errors['password'] = 'Password maksimal 72 karakter.';
    }

    // Validasi konfirmasi password (harus sama dengan password)
    if ($konfirmasi === '') {
        $errors['konfirmasi_password'] = 'Konfirmasi password wajib diisi.';
    } elseif ($password !== $konfirmasi) {
        $errors['konfirmasi_password'] = 'Konfirmasi password tidak cocok.';
    }

    // Validasi peran (hanya boleh pelajar atau pengajar, tidak boleh admin)
    if ($peran !== 'pelajar' && $peran !== 'pengajar') {
        $errors['peran'] = 'Silakan pilih peran sebagai Pelajar atau Pengajar.';
    }

    return $errors;
}

// 4. Simpan data pengguna baru ke tabel user
function daftarPenggunaBaru($nama, $email, $password, $peran) {
    global $pdo;

    // Enkripsi password sebelum disimpan ke database (AUT-01, SEC-05)
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO user (nama, email, password, peran) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $berhasil = $stmt->execute([$nama, $email, $passwordHash, $peran]);

    return $berhasil;
}
