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

// 5. Fungsi verifikasi login (Sesi 3)
// Mengecek email dan password waktu user login
// Mengembalikan array hasil: ['berhasil' => true/false, 'pesan' => '...', 'pengguna' => data/null]
function verifikasiLogin($email, $password) {
    $hasil = [
        'berhasil' => false,
        'pesan'    => '',
        'pengguna' => null
    ];

    $email = trim($email);

    // 1. Cek apakah email atau password kosong
    if ($email === '' || $password === '') {
        $hasil['pesan'] = 'Email dan password wajib diisi.';
        return $hasil;
    }

    // 2. Cari user berdasarkan email
    $pengguna = cariPenggunaByEmail($email);

    // 3. Kalau email tidak ditemukan
    // Pesan dibuat sama dengan password salah (AUT-02, SEC-05)
    if (!$pengguna) {
        $hasil['pesan'] = 'Email atau password salah.';
        return $hasil;
    }

    // 4. Cek password dengan password_verify()
    if (!password_verify($password, $pengguna['password'])) {
        $hasil['pesan'] = 'Email atau password salah.';
        return $hasil;
    }

    // 5. Cek apakah akun dinonaktifkan admin (AUT-02, AUT-07)
    // Kalau ada kolom 'aktif' atau 'status' di tabel user
    if (isset($pengguna['aktif']) && (int)$pengguna['aktif'] === 0) {
        $hasil['pesan'] = 'Akun Anda telah dinonaktifkan oleh Admin.';
        return $hasil;
    }
    if (isset($pengguna['status']) && $pengguna['status'] === 'nonaktif') {
        $hasil['pesan'] = 'Akun Anda telah dinonaktifkan oleh Admin.';
        return $hasil;
    }

    // 6. Login berhasil
    $hasil['berhasil'] = true;
    $hasil['pengguna'] = $pengguna;
    return $hasil;
}

// 6. Perbarui nama lengkap pengguna (BS-01)
function ubahNamaPengguna($id, $namaBaru) {
    global $pdo;

    $namaBaru = trim($namaBaru);
    if (strlen($namaBaru) < 2 || strlen($namaBaru) > 100) {
        return [
            'berhasil' => false,
            'pesan'    => 'Nama lengkap harus antara 2 sampai 100 karakter.'
        ];
    }

    $sql = "UPDATE user SET nama = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$namaBaru, $id]);

    // Perbarui nama di sesi aktif
    if (isset($_SESSION['user']['id']) && (int)$_SESSION['user']['id'] === (int)$id) {
        $_SESSION['user']['nama'] = $namaBaru;
    }

    return [
        'berhasil' => true,
        'pesan'    => 'Nama profil berhasil diperbarui.'
    ];
}

// 7. Ganti kata sandi pengguna (BS-01, AUT-08)
// Memeriksa password lama terlebih dahulu, lalu mengupdate hash password baru
function ubahPasswordPengguna($id, $passwordLama, $passwordBaru, $konfirmasiBaru) {
    global $pdo;

    $user = ambilPenggunaById($id);
    if (!$user) {
        return [
            'berhasil' => false,
            'pesan'    => 'Pengguna tidak ditemukan.'
        ];
    }

    // Verifikasi password lama
    if (!password_verify($passwordLama, $user['password'])) {
        return [
            'berhasil' => false,
            'pesan'    => 'Password lama yang Anda masukkan salah.'
        ];
    }

    // Validasi panjang password baru
    if (strlen($passwordBaru) < 8) {
        return [
            'berhasil' => false,
            'pesan'    => 'Password baru minimal harus 8 karakter.'
        ];
    }
    if (strlen($passwordBaru) > 72) {
        return [
            'berhasil' => false,
            'pesan'    => 'Password baru maksimal 72 karakter.'
        ];
    }

    // Validasi konfirmasi password baru
    if ($passwordBaru !== $konfirmasiBaru) {
        return [
            'berhasil' => false,
            'pesan'    => 'Konfirmasi password baru tidak cocok.'
        ];
    }

    // Enkripsi password baru
    $hashBaru = password_hash($passwordBaru, PASSWORD_DEFAULT);

    // Update database (dan reset wajib_ganti_sandi jika ada kolomnya)
    $sql = "UPDATE user SET password = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$hashBaru, $id]);

    // Reset status wajib_ganti_sandi di session jika sebelumnya diwajibkan
    if (isset($_SESSION['user']['wajib_ganti_sandi'])) {
        $_SESSION['user']['wajib_ganti_sandi'] = 0;
    }

    return [
        'berhasil' => true,
        'pesan'    => 'Password berhasil diubah.'
    ];
}

