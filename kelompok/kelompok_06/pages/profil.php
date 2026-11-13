
<?php
// =============================================
// HALAMAN PROFIL PENGGUNA FLASHPos
// =============================================

// Memastikan pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    exit("Akses ditolak!");
}

$user_id = (int) $_SESSION['user_id'];
$pesan = '';
$jenis_pesan = '';

// Mengambil data pengguna dari database
$stmt = mysqli_prepare(
    $conn,
    "SELECT id, nama_lengkap, username, password, role, created_at,
            last_nama_update, last_username_update
     FROM users
     WHERE id = ?"
);

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$user) {
    exit("Data pengguna tidak ditemukan!");
}

// Membuat CSRF token
if (empty($_SESSION['csrf_profil'])) {
    $_SESSION['csrf_profil'] = bin2hex(random_bytes(32));
}

// =============================================
// MEMPROSES FORM
// =============================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['csrf_profil'], $token)
    ) {
        $pesan = "Permintaan tidak valid. Silakan muat ulang halaman.";
        $jenis_pesan = 'error';

    } else {

        $aksi = $_POST['aksi'] ?? '';

        // =====================================
        // EDIT PROFIL
        // =====================================

        if ($aksi === 'edit_profil') {

            $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
            $username = trim($_POST['username'] ?? '');

            if ($nama_lengkap === '' || $username === '') {
                $pesan = "Nama lengkap dan username wajib diisi!";
                $jenis_pesan = 'error';

            } elseif (mb_strlen($nama_lengkap) > 100) {
                $pesan = "Nama lengkap maksimal 100 karakter!";
                $jenis_pesan = 'error';

            } elseif (strlen($username) > 50) {
                $pesan = "Username maksimal 50 karakter!";
                $jenis_pesan = 'error';

            } else {

                // Memeriksa perubahan data
                $nama_berubah = (
                    $nama_lengkap !== $user['nama_lengkap']
                );

                $username_berubah = (
                    $username !== $user['username']
                );

                $waktu_sekarang = time();

                // Batas perubahan nama: 7 hari
                if (
                    $nama_berubah &&
                    !empty($user['last_nama_update'])
                ) {
                    $batas_nama = strtotime(
                        $user['last_nama_update'] . ' +7 days'
                    );

                    if ($waktu_sekarang < $batas_nama) {
                        $pesan = "Nama lengkap hanya dapat diubah 1 kali dalam 7 hari. Anda dapat mengubahnya kembali pada " .
                            date('d-m-Y H:i', $batas_nama) . ".";
                        $jenis_pesan = 'error';
                    }
                }

                // Batas perubahan username: 30 hari
                if (
                    $jenis_pesan !== 'error' &&
                    $username_berubah &&
                    !empty($user['last_username_update'])
                ) {
                    $batas_username = strtotime(
                        $user['last_username_update'] . ' +30 days'
                    );

                    if ($waktu_sekarang < $batas_username) {
                        $pesan = "Username hanya dapat diubah 1 kali dalam 30 hari. Anda dapat mengubahnya kembali pada " .
                            date('d-m-Y H:i', $batas_username) . ".";
                        $jenis_pesan = 'error';
                    }
                }

                // Memeriksa username duplikat
                if (
                    $jenis_pesan !== 'error' &&
                    $username_berubah
                ) {
                    $stmt = mysqli_prepare(
                        $conn,
                        "SELECT id FROM users
                         WHERE username = ? AND id != ?"
                    );

                    mysqli_stmt_bind_param(
                        $stmt,
                        "si",
                        $username,
                        $user_id
                    );

                    mysqli_stmt_execute($stmt);

                    $cek = mysqli_stmt_get_result($stmt);
                    $username_ada = mysqli_num_rows($cek) > 0;

                    mysqli_stmt_close($stmt);

                    if ($username_ada) {
                        $pesan = "Username sudah digunakan oleh pengguna lain!";
                        $jenis_pesan = 'error';
                    }
                }

                // Menyimpan perubahan profil
                if ($jenis_pesan !== 'error') {

                    if (!$nama_berubah && !$username_berubah) {
                        $pesan = "Tidak ada perubahan pada profil.";
                        $jenis_pesan = 'success';

                    } else {

                        $stmt = mysqli_prepare(
                            $conn,
                            "UPDATE users
                             SET nama_lengkap = ?,
                                 username = ?,
                                 last_nama_update = CASE
                                     WHEN ? = 1 THEN NOW()
                                     ELSE last_nama_update
                                 END,
                                 last_username_update = CASE
                                     WHEN ? = 1 THEN NOW()
                                     ELSE last_username_update
                                 END
                             WHERE id = ?"
                        );

                        $nama_flag = (int) $nama_berubah;
                        $username_flag = (int) $username_berubah;

                        mysqli_stmt_bind_param(
                            $stmt,
                            "ssiii",
                            $nama_lengkap,
                            $username,
                            $nama_flag,
                            $username_flag,
                            $user_id
                        );

                        if (mysqli_stmt_execute($stmt)) {

                            // Memperbarui session
                            $_SESSION['nama_lengkap'] = $nama_lengkap;
                            $_SESSION['username'] = $username;

                            // Memperbarui data tampilan
                            $user['nama_lengkap'] = $nama_lengkap;
                            $user['username'] = $username;

                            if ($nama_berubah) {
                                $user['last_nama_update'] = date('Y-m-d H:i:s');
                            }

                            if ($username_berubah) {
                                $user['last_username_update'] = date('Y-m-d H:i:s');
                            }

                            $pesan = "Profil berhasil diperbarui!";
                            $jenis_pesan = 'success';

                        } else {
                            $pesan = "Gagal memperbarui profil!";
                            $jenis_pesan = 'error';
                        }

                        mysqli_stmt_close($stmt);
                    }
                }
            }

        // =====================================
        // GANTI PASSWORD
        // =====================================

        } elseif ($aksi === 'ganti_password') {

            $password_lama = $_POST['password_lama'] ?? '';
            $password_baru = $_POST['password_baru'] ?? '';
            $konfirmasi_password = $_POST['konfirmasi_password'] ?? '';

            if (
                $password_lama === '' ||
                $password_baru === '' ||
                $konfirmasi_password === ''
            ) {
                $pesan = "Semua kolom password wajib diisi!";
                $jenis_pesan = 'error';

            } elseif (
                !password_verify($password_lama, $user['password'])
            ) {
                $pesan = "Password lama yang dimasukkan salah!";
                $jenis_pesan = 'error';

            } elseif (strlen($password_baru) < 8) {
                $pesan = "Password baru minimal 8 karakter!";
                $jenis_pesan = 'error';

            } elseif ($password_baru !== $konfirmasi_password) {
                $pesan = "Konfirmasi password baru tidak sesuai!";
                $jenis_pesan = 'error';

            } elseif (
                password_verify($password_baru, $user['password'])
            ) {
                $pesan = "Password baru tidak boleh sama dengan password lama!";
                $jenis_pesan = 'error';

            } else {

                $password_hash = password_hash(
                    $password_baru,
                    PASSWORD_DEFAULT
                );

                $stmt = mysqli_prepare(
                    $conn,
                    "UPDATE users SET password = ? WHERE id = ?"
                );

                mysqli_stmt_bind_param(
                    $stmt,
                    "si",
                    $password_hash,
                    $user_id
                );

                if (mysqli_stmt_execute($stmt)) {

                    $user['password'] = $password_hash;

                    $pesan = "Password berhasil diperbarui!";
                    $jenis_pesan = 'success';

                } else {
                    $pesan = "Gagal memperbarui password!";
                    $jenis_pesan = 'error';
                }

                mysqli_stmt_close($stmt);
            }

        } else {
            $pesan = "Aksi tidak dikenali!";
            $jenis_pesan = 'error';
        }
    }
}

// =============================================
// INFORMASI PROFIL DAN BATAS PERUBAHAN
// =============================================

$inisial = strtoupper(
    mb_substr($user['nama_lengkap'], 0, 1)
);

$batas_nama = !empty($user['last_nama_update'])
    ? strtotime($user['last_nama_update'] . ' +7 days')
    : null;

$batas_username = !empty($user['last_username_update'])
    ? strtotime($user['last_username_update'] . ' +30 days')
    : null;

$boleh_ubah_nama = (
    $batas_nama === null || time() >= $batas_nama
);

$boleh_ubah_username = (
    $batas_username === null || time() >= $batas_username
);
?>

<div class="max-w-5xl mx-auto space-y-6">

    <!-- JUDUL HALAMAN -->
    <div>
        <h2 class="text-2xl font-bold text-slate-800">
            Profil Pengguna
        </h2>
        <p class="text-sm text-slate-500 mt-1">
            Kelola informasi akun dan keamanan password Anda.
        </p>
    </div>

    <!-- NOTIFIKASI -->
    <?php if ($pesan): ?>
        <div class="p-4 rounded-xl border
            <?= $jenis_pesan === 'success'
                ? 'bg-green-50 text-green-700 border-green-200'
                : 'bg-red-50 text-red-700 border-red-200' ?>">
            <?= htmlspecialchars($pesan) ?>
        </div>
    <?php endif; ?>

    <!-- INFORMASI AKUN -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">

        <div class="flex items-center gap-5">

            <div class="w-20 h-20 rounded-full bg-brand-500
                        text-white flex items-center justify-center
                        text-3xl font-bold shrink-0">
                <?= htmlspecialchars($inisial) ?>
            </div>

            <div>
                <h3 class="text-xl font-bold text-slate-800">
                    <?= htmlspecialchars($user['nama_lengkap']) ?>
                </h3>

                <p class="text-sm text-slate-500">
                    @<?= htmlspecialchars($user['username']) ?>
                </p>

                <span class="inline-block mt-2 px-3 py-1 rounded-full
                    text-xs font-semibold capitalize
                    <?= $user['role'] === 'admin'
                        ? 'bg-purple-100 text-purple-700'
                        : 'bg-blue-100 text-blue-700' ?>">
                    <?= htmlspecialchars($user['role']) ?>
                </span>
            </div>

        </div>

        <div class="border-t border-slate-100 mt-6 pt-5">
            <p class="text-xs text-slate-400">
                Tanggal Bergabung
            </p>

            <p class="text-sm font-semibold text-slate-700 mt-1">
                <?= date(
                    'd F Y',
                    strtotime($user['created_at'])
                ) ?>
            </p>
        </div>

    </div>

    <!-- EDIT PROFIL -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">

        <div class="mb-6">
            <h3 class="text-lg font-bold text-slate-800">
                Edit Informasi Profil
            </h3>

            <p class="text-sm text-slate-500 mt-1">
                Perbarui informasi akun sesuai ketentuan perubahan.
            </p>
        </div>

        <form method="POST" class="space-y-5">

            <input type="hidden"
                   name="csrf_token"
                   value="<?= htmlspecialchars($_SESSION['csrf_profil']) ?>">

            <input type="hidden"
                   name="aksi"
                   value="edit_profil">

            <!-- NAMA LENGKAP -->
            <div>
                <label class="block text-sm font-semibold mb-2">
                    Nama Lengkap
                </label>

                <input
                    type="text"
                    name="nama_lengkap"
                    required
                    maxlength="100"
                    value="<?= htmlspecialchars($user['nama_lengkap']) ?>"
                    <?= !$boleh_ubah_nama ? 'disabled' : '' ?>
                    class="w-full border border-slate-200 rounded-xl p-3
                           focus:outline-none focus:ring-2 focus:ring-brand-500
                           <?= !$boleh_ubah_nama
                               ? 'bg-slate-100 text-slate-500 cursor-not-allowed'
                               : '' ?>"
                >

                <p class="text-xs mt-2
                    <?= $boleh_ubah_nama
                        ? 'text-green-600'
                        : 'text-amber-600' ?>">

                    <?php if ($boleh_ubah_nama): ?>
                        <i data-lucide="check-circle" class="w-3 h-3 inline"></i>
                        Nama lengkap dapat diubah.
                    <?php else: ?>
                        Nama lengkap hanya dapat diubah 1 kali setiap 7 hari.
                        Bisa diubah kembali pada
                        <strong>
                            <?= date('d-m-Y H:i', $batas_nama) ?>
                        </strong>.
                    <?php endif; ?>

                </p>
            </div>

            <!-- USERNAME -->
            <div>
                <label class="block text-sm font-semibold mb-2">
                    Username
                </label>

                <input
                    type="text"
                    name="username"
                    required
                    maxlength="50"
                    value="<?= htmlspecialchars($user['username']) ?>"
                    <?= !$boleh_ubah_username ? 'disabled' : '' ?>
                    class="w-full border border-slate-200 rounded-xl p-3
                           focus:outline-none focus:ring-2 focus:ring-brand-500
                           <?= !$boleh_ubah_username
                               ? 'bg-slate-100 text-slate-500 cursor-not-allowed'
                               : '' ?>"
                >

                <p class="text-xs mt-2
                    <?= $boleh_ubah_username
                        ? 'text-green-600'
                        : 'text-amber-600' ?>">

                    <?php if ($boleh_ubah_username): ?>
                        <i data-lucide="check-circle" class="w-3 h-3 inline"></i>
                        Username dapat diubah.
                    <?php else: ?>
                        Username hanya dapat diubah 1 kali setiap 30 hari.
                        Bisa diubah kembali pada
                        <strong>
                            <?= date('d-m-Y H:i', $batas_username) ?>
                        </strong>.
                    <?php endif; ?>

                </p>
            </div>

            <!-- ROLE -->
            <div>
                <label class="block text-sm font-semibold mb-2">
                    Role Pengguna
                </label>

                <input
                    type="text"
                    value="<?= htmlspecialchars(ucfirst($user['role'])) ?>"
                    disabled
                    class="w-full border border-slate-200 rounded-xl p-3
                           bg-slate-100 text-slate-500 cursor-not-allowed"
                >

                <p class="text-xs text-slate-400 mt-2">
                    Role pengguna tidak dapat diubah melalui halaman profil.
                </p>
            </div>

            <button
                type="submit"
                class="bg-brand-500 hover:bg-brand-600 text-white
                       px-5 py-3 rounded-xl font-semibold transition">
                Simpan Perubahan
            </button>

        </form>

    </div>

    <!-- GANTI PASSWORD -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">

        <div class="mb-6">
            <h3 class="text-lg font-bold text-slate-800">
                Ganti Password
            </h3>

            <p class="text-sm text-slate-500 mt-1">
                Gunakan password yang kuat untuk menjaga keamanan akun.
            </p>
        </div>

        <form method="POST" class="space-y-5">

            <input type="hidden"
                   name="csrf_token"
                   value="<?= htmlspecialchars($_SESSION['csrf_profil']) ?>">

            <input type="hidden"
                   name="aksi"
                   value="ganti_password">

            <!-- PASSWORD LAMA -->
            <div>
                <label class="block text-sm font-semibold mb-2">
                    Password Lama
                </label>

                <input
                    type="password"
                    name="password_lama"
                    required
                    autocomplete="current-password"
                    class="w-full border border-slate-200 rounded-xl p-3
                           focus:outline-none focus:ring-2 focus:ring-brand-500"
                >
            </div>

            <!-- PASSWORD BARU -->
            <div>
                <label class="block text-sm font-semibold mb-2">
                    Password Baru
                </label>

                <input
                    type="password"
                    name="password_baru"
                    required
                    minlength="8"
                    autocomplete="new-password"
                    class="w-full border border-slate-200 rounded-xl p-3
                           focus:outline-none focus:ring-2 focus:ring-brand-500"
                >

                <p class="text-xs text-slate-400 mt-2">
                    Password minimal 8 karakter.
                </p>
            </div>

            <!-- KONFIRMASI PASSWORD -->
            <div>
                <label class="block text-sm font-semibold mb-2">
                    Konfirmasi Password Baru
                </label>

                <input
                    type="password"
                    name="konfirmasi_password"
                    required
                    minlength="8"
                    autocomplete="new-password"
                    class="w-full border border-slate-200 rounded-xl p-3
                           focus:outline-none focus:ring-2 focus:ring-brand-500"
                >
            </div>

            <button
                type="submit"
                class="bg-slate-900 hover:bg-slate-800 text-white
                       px-5 py-3 rounded-xl font-semibold transition">
                Perbarui Password
            </button>

        </form>

    </div>

</div>
