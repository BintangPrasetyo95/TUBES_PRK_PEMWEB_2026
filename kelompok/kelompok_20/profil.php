<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

wajibLogin();

$userId = (int)$_SESSION['user_id'];
$errorsProfil = [];
$errorsPassword = [];

$stmt = $pdo->prepare('SELECT id, nama, email, password, role, created_at FROM users WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: /logout.php');
    exit;
}

$aktivitas = [
    'total' => 0,
    'menunggu' => 0,
    'diproses' => 0,
    'selesai' => 0
];

if ($user['role'] === 'user') {
    $stmtAktivitas = $pdo->prepare("
        SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN status = 'menunggu' THEN 1 ELSE 0 END) AS menunggu,
            SUM(CASE WHEN status = 'diproses' THEN 1 ELSE 0 END) AS diproses,
            SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) AS selesai
        FROM laporan
        WHERE user_id = :user_id
    ");

    $stmtAktivitas->execute([':user_id' => $userId]);
    $hasilAktivitas = $stmtAktivitas->fetch();

    if ($hasilAktivitas) {
        $aktivitas = [
            'total' => (int)($hasilAktivitas['total'] ?? 0),
            'menunggu' => (int)($hasilAktivitas['menunggu'] ?? 0),
            'diproses' => (int)($hasilAktivitas['diproses'] ?? 0),
            'selesai' => (int)($hasilAktivitas['selesai'] ?? 0)
        ];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifikasiCsrf();

    $action = $_POST['action'] ?? '';

    if ($action === 'update_profil') {
        $nama = trim($_POST['nama'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if (empty($nama)) {
            $errorsProfil[] = 'Nama lengkap wajib diisi.';
        } elseif (strlen($nama) > 100) {
            $errorsProfil[] = 'Nama lengkap maksimal 100 karakter.';
        }

        if (empty($email)) {
            $errorsProfil[] = 'Alamat email wajib diisi.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errorsProfil[] = 'Format alamat email tidak valid.';
        } elseif ($email !== $user['email']) {
            $stmtCek = $pdo->prepare(
                'SELECT id FROM users WHERE email = :email AND id != :id LIMIT 1'
            );

            $stmtCek->execute([
                ':email' => $email,
                ':id' => $userId
            ]);

            if ($stmtCek->fetch()) {
                $errorsProfil[] = 'Alamat email ini sudah digunakan oleh akun lain.';
            }
        }

        if (empty($errorsProfil)) {
            $stmtUpdate = $pdo->prepare(
                'UPDATE users SET nama = :nama, email = :email WHERE id = :id'
            );

            $sukses = $stmtUpdate->execute([
                ':nama' => $nama,
                ':email' => $email,
                ':id' => $userId
            ]);

            if ($sukses) {
                $_SESSION['nama'] = $nama;
                $_SESSION['email'] = $email;

                setFlash('success', 'Informasi profil berhasil diperbarui.');
                header('Location: /profil.php');
                exit;
            } else {
                $errorsProfil[] = 'Gagal menyimpan perubahan profil ke sistem.';
            }
        }
    } elseif ($action === 'update_password') {
        $passwordLama = $_POST['password_lama'] ?? '';
        $passwordBaru = $_POST['password_baru'] ?? '';
        $konfirmasiPassword = $_POST['konfirmasi_password'] ?? '';

        if (empty($passwordLama)) {
            $errorsPassword[] = 'Kata sandi saat ini wajib diisi.';
        } elseif (!password_verify($passwordLama, $user['password'])) {
            $errorsPassword[] = 'Kata sandi saat ini tidak cocok.';
        }

        if (empty($passwordBaru)) {
            $errorsPassword[] = 'Kata sandi baru wajib diisi.';
        } elseif (strlen($passwordBaru) < 6) {
            $errorsPassword[] = 'Kata sandi baru minimal 6 karakter.';
        }

        if ($passwordBaru !== $konfirmasiPassword) {
            $errorsPassword[] = 'Konfirmasi kata sandi baru tidak cocok.';
        }

        if (empty($errorsPassword)) {
            $hashBaru = password_hash($passwordBaru, PASSWORD_DEFAULT);

            $stmtPass = $pdo->prepare(
                'UPDATE users SET password = :password WHERE id = :id'
            );

            $sukses = $stmtPass->execute([
                ':password' => $hashBaru,
                ':id' => $userId
            ]);

            if ($sukses) {
                setFlash('success', 'Kata sandi berhasil diperbarui.');
                header('Location: /profil.php');
                exit;
            } else {
                $errorsPassword[] = 'Gagal memperbarui kata sandi sistem.';
            }
        }
    }
}

$pageTitle = 'Profil Pengguna';
require_once __DIR__ . '/includes/header.php';
?>

<div style="max-width: 900px; margin: 0 auto; padding-bottom: 2rem;">

    <!-- Kartu Informasi Profil -->
    <div class="card" style="padding: 2rem; margin-bottom: 2rem; display: flex; align-items: center; gap: 1.5rem; flex-wrap: wrap;">

        <div style="width: 70px; height: 70px; border-radius: 50%; background-color: var(--color-primary); color: var(--color-on-primary); display: flex; align-items: center; justify-content: center; font-size: 1.75rem; font-weight: 700; flex-shrink: 0;">
            <?= sanitize(strtoupper(substr($user['nama'], 0, 1))) ?>
        </div>

        <div style="flex: 1; min-width: 0;">
            <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                <h1 style="color: var(--color-primary); font-size: 1.5rem; margin: 0;">
                    <?= sanitize($user['nama']) ?>
                </h1>

                <span class="badge <?= $user['role'] === 'admin' ? 'badge-selesai' : 'badge-diproses' ?>" style="font-size: 0.85rem;">
                    <?= sanitize(strtoupper($user['role'])) ?>
                </span>
            </div>

            <p style="color: var(--color-text-muted); font-size: 0.95rem; margin: 0.25rem 0 0;">
                <?= sanitize($user['email']) ?> &bull;
                Bergabung sejak <?= date('d M Y', strtotime($user['created_at'])) ?>
            </p>
        </div>
    </div>

    <?php if ($user['role'] === 'user'): ?>
        <!-- Ringkasan Aktivitas Laporan -->
        <div class="card" style="padding: 1.75rem; margin-bottom: 2rem;">

            <div style="margin-bottom: 1.25rem;">
                <h3 class="card-title" style="font-size: 1.2rem; margin-bottom: 0.35rem;">
                    Aktivitas Laporan Saya
                </h3>

                <p style="color: var(--color-text-muted); font-size: 0.9rem; margin: 0;">
                    Ringkasan pengaduan dan aspirasi yang telah Anda kirimkan.
                </p>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 1rem;">

                <div style="background: var(--color-background, #F5F0E6); border: 1px solid var(--color-border); border-radius: var(--radius); padding: 1rem;">
                    <div style="font-size: 1.65rem; font-weight: 700; color: var(--color-primary); line-height: 1.2;">
                        <?= $aktivitas['total'] ?>
                    </div>
                    <div style="font-size: 0.88rem; color: var(--color-text-muted); margin-top: 0.35rem;">
                        Total Laporan
                    </div>
                </div>

                <div style="background: var(--color-background, #F5F0E6); border: 1px solid var(--color-border); border-radius: var(--radius); padding: 1rem;">
                    <div style="font-size: 1.65rem; font-weight: 700; color: var(--color-primary); line-height: 1.2;">
                        <?= $aktivitas['menunggu'] ?>
                    </div>
                    <div style="font-size: 0.88rem; color: var(--color-text-muted); margin-top: 0.35rem;">
                        Menunggu
                    </div>
                </div>

                <div style="background: var(--color-background, #F5F0E6); border: 1px solid var(--color-border); border-radius: var(--radius); padding: 1rem;">
                    <div style="font-size: 1.65rem; font-weight: 700; color: var(--color-primary); line-height: 1.2;">
                        <?= $aktivitas['diproses'] ?>
                    </div>
                    <div style="font-size: 0.88rem; color: var(--color-text-muted); margin-top: 0.35rem;">
                        Diproses
                    </div>
                </div>

                <div style="background: var(--color-background, #F5F0E6); border: 1px solid var(--color-border); border-radius: var(--radius); padding: 1rem;">
                    <div style="font-size: 1.65rem; font-weight: 700; color: var(--color-primary); line-height: 1.2;">
                        <?= $aktivitas['selesai'] ?>
                    </div>
                    <div style="font-size: 0.88rem; color: var(--color-text-muted); margin-top: 0.35rem;">
                        Selesai
                    </div>
                </div>

            </div>

            <div style="margin-top: 1.25rem;">
                <a href="/user/laporan_saya.php" style="color: var(--color-primary); font-weight: 600; text-decoration: none;">
                    Lihat semua laporan &rarr;
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Form Profil dan Kata Sandi -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 360px), 1fr)); gap: 2rem; align-items: start;">

        <!-- Perbarui Data Diri -->
        <div class="card" style="padding: 1.75rem;">

            <h3 class="card-title" style="font-size: 1.2rem; margin-bottom: 0.5rem;">
                Perbarui Data Diri
            </h3>

            <p style="color: var(--color-text-muted); font-size: 0.9rem; margin-bottom: 1.5rem;">
                Ubah nama lengkap atau alamat email akun Anda.
            </p>

            <?php if (!empty($errorsProfil)): ?>
                <div class="alert alert-danger">
                    <ul style="margin: 0; padding-left: 1.25rem;">
                        <?php foreach ($errorsProfil as $err): ?>
                            <li><?= sanitize($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="/profil.php" method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="update_profil">

                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <label for="nama" class="form-label">Nama Lengkap</label>

                    <input
                        type="text"
                        id="nama"
                        name="nama"
                        class="form-control"
                        value="<?= sanitize($_POST['nama'] ?? $user['nama']) ?>"
                        required
                        maxlength="100"
                    >
                </div>

                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label for="email" class="form-label">Alamat Email</label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control"
                        value="<?= sanitize($_POST['email'] ?? $user['email']) ?>"
                        required
                    >
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    Simpan Perubahan
                </button>
            </form>
        </div>

        <!-- Ganti Kata Sandi -->
        <div class="card" style="padding: 1.75rem;">

            <h3 class="card-title" style="font-size: 1.2rem; margin-bottom: 0.5rem;">
                Ganti Kata Sandi
            </h3>

            <p style="color: var(--color-text-muted); font-size: 0.9rem; margin-bottom: 1.5rem;">
                Pastikan kata sandi baru Anda minimal 6 karakter.
            </p>

            <?php if (!empty($errorsPassword)): ?>
                <div class="alert alert-danger">
                    <ul style="margin: 0; padding-left: 1.25rem;">
                        <?php foreach ($errorsPassword as $err): ?>
                            <li><?= sanitize($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="/profil.php" method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="update_password">

                <?php
                $fieldPassword = [
                    [
                        'id' => 'password_lama',
                        'name' => 'password_lama',
                        'label' => 'Kata Sandi Saat Ini',
                        'placeholder' => 'Masukkan kata sandi lama',
                        'minlength' => ''
                    ],
                    [
                        'id' => 'password_baru',
                        'name' => 'password_baru',
                        'label' => 'Kata Sandi Baru',
                        'placeholder' => 'Minimal 6 karakter',
                        'minlength' => '6'
                    ],
                    [
                        'id' => 'konfirmasi_password',
                        'name' => 'konfirmasi_password',
                        'label' => 'Konfirmasi Kata Sandi Baru',
                        'placeholder' => 'Ulangi kata sandi baru',
                        'minlength' => '6'
                    ]
                ];

                foreach ($fieldPassword as $field):
                ?>

                    <div class="form-group" style="margin-bottom: 1.25rem;">

                        <label for="<?= $field['id'] ?>" class="form-label">
                            <?= $field['label'] ?>
                        </label>

                        <div style="position: relative;">

                            <input
                                type="password"
                                id="<?= $field['id'] ?>"
                                name="<?= $field['name'] ?>"
                                class="form-control"
                                style="padding-right: 3rem;"
                                placeholder="<?= $field['placeholder'] ?>"
                                <?= $field['minlength'] !== '' ? 'minlength="' . $field['minlength'] . '"' : '' ?>
                                required
                            >

                            <!-- Hanya satu elemen ikon mata -->
                            <button
                                type="button"
                                data-password-toggle="<?= $field['id'] ?>"
                                aria-label="Tampilkan kata sandi"
                                aria-pressed="false"
                                style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); border: 0; background: transparent; color: var(--color-text-muted); padding: 0.25rem; cursor: pointer; display: flex; align-items: center; justify-content: center;"
                            >
                                <svg
                                    data-eye-icon
                                    viewBox="0 0 24 24"
                                    width="20"
                                    height="20"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    aria-hidden="true"
                                >
                                    <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                            </button>

                        </div>
                    </div>

                <?php endforeach; ?>

                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    Perbarui Kata Sandi
                </button>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    document.querySelectorAll('[data-password-toggle]').forEach(function (button) {

        button.addEventListener('click', function () {

            var input = document.getElementById(
                button.getAttribute('data-password-toggle')
            );

            if (!input) return;

            var tampil = input.type === 'password';
            input.type = tampil ? 'text' : 'password';

            button.setAttribute(
                'aria-pressed',
                tampil ? 'true' : 'false'
            );

            button.setAttribute(
                'aria-label',
                tampil ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'
            );

            var icon = button.querySelector('[data-eye-icon]');

            if (tampil) {
                icon.innerHTML =
                    '<path d="m3 3 18 18"/>' +
                    '<path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"/>' +
                    '<path d="M9.9 5.2A10.8 10.8 0 0 1 12 5c6.4 0 10 7 10 7a14.7 14.7 0 0 1-3.1 3.8M6.2 6.2C3.5 8 2 12 2 12s3.6 7 10 7c1.4 0 2.7-.3 3.8-.8"/>';
            } else {
                icon.innerHTML =
                    '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/>' +
                    '<circle cx="12" cy="12" r="3"/>';
            }

        });

    });

});
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
