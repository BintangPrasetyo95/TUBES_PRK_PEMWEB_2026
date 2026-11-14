<?php

// Wizard instalasi DB otomatis (buat DB + tabel + simpan config lokal).
mysqli_report(MYSQLI_REPORT_OFF);

require_once __DIR__ . '/config/setup_helpers.php';

$localConfigFile = __DIR__ . '/config/database.local.php';
$sqlFile = __DIR__ . '/database/febmart.sql';

$defaults = [
    'host'     => 'localhost',
    'username' => 'root',
    'password' => '',
    'dbname'   => 'febmart',
];

// Isi awal formulir dari konfigurasi lokal yang sudah ada (jika ada).
$form = $defaults;

if (is_file($localConfigFile)) {
    $loaded = require $localConfigFile;

    if (is_array($loaded)) {
        $form = array_merge($form, $loaded);
    }
}

$errors = [];
$success = false;
$justReinstalled = false;

function febmart_status(array $config): array
{
    $status = [
        'connected' => false,
        'db_exists' => false,
        'missing'   => [],
        'error'     => '',
    ];

    $conn = @new mysqli($config['host'], $config['username'], $config['password']);

    if ($conn->connect_errno) {
        $status['error'] = $conn->connect_error;
        return $status;
    }

    $status['connected'] = true;

    $dbName = febmart_sanitize_dbname((string) $config['dbname']);

    $result = $conn->query(
        "SHOW DATABASES LIKE '" . $conn->real_escape_string($dbName) . "'"
    );

    if ($result && $result->num_rows > 0) {
        $status['db_exists'] = true;

        $conn->select_db($dbName);

        $analisis = febmart_table_analysis($conn);
        $status['missing'] = $analisis['hilang'];
    }

    $conn->close();

    return $status;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['host'] = trim((string) ($_POST['host'] ?? $defaults['host']));
    $form['username'] = trim((string) ($_POST['username'] ?? $defaults['username']));
    $form['password'] = (string) ($_POST['password'] ?? '');
    $form['dbname'] = trim((string) ($_POST['dbname'] ?? $defaults['dbname']));
    $recreate = isset($_POST['recreate']);

    if ($form['host'] === '' || $form['username'] === '' || $form['dbname'] === '') {
        $errors[] = 'Host, username, dan nama database wajib diisi.';
    }

    $dbName = febmart_sanitize_dbname($form['dbname']);

    if (!$errors) {
        $conn = @new mysqli($form['host'], $form['username'], $form['password']);

        if ($conn->connect_errno) {
            $errors[] = 'Gagal terhubung ke MySQL: ' . $conn->connect_error
                . ' (periksa kembali host, username, dan password Anda).';
        } else {
            $conn->set_charset('utf8mb4');

            if ($recreate) { // hapus lalu buat ulang
                $conn->query('DROP DATABASE IF EXISTS `' . $dbName . '`');
                $justReinstalled = true;
            }

            $created = $conn->query(
                'CREATE DATABASE IF NOT EXISTS `' . $dbName . '` '
                . 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
            );

            if (!$created) {
                $errors[] = 'Gagal membuat database "' . $dbName . '": ' . $conn->error;
            } elseif (!$conn->select_db($dbName)) {
                $errors[] = 'Gagal memilih database "' . $dbName . '": ' . $conn->error;
            } else {
                $analisis = febmart_table_analysis($conn);

                febmart_apply_migrations($conn, $analisis['ada']);

                if ($recreate || $analisis['hilang']) { // jalankan skema + data contoh
                    $hasilSql = febmart_run_sql_file($conn, $sqlFile, $dbName);

                    if (!$hasilSql['ok']) {
                        $errors[] = 'Terjadi kesalahan saat membuat tabel: ' . $hasilSql['error'];
                    }
                }

                if (!$errors) {
                    $content = "<?php\n\n"
                        . "// File ini dibuat otomatis oleh install.php.\n"
                        . "// Berisi kredensial MySQL khusus komputer ini.\n"
                        . "return [\n"
                        . "    'host'     => " . var_export($form['host'], true) . ",\n"
                        . "    'username' => " . var_export($form['username'], true) . ",\n"
                        . "    'password' => " . var_export($form['password'], true) . ",\n"
                        . "    'dbname'   => " . var_export($dbName, true) . ",\n"
                        . "];\n";

                    $written = @file_put_contents($localConfigFile, $content);

                    if ($written === false) {
                        $errors[] = 'Database berhasil dibuat, tetapi konfigurasi tidak dapat '
                            . 'disimpan ke config/database.local.php (periksa izin folder config). '
                            . 'Anda tetap dapat memakai sistem pada sesi ini.';
                    }

                    $success = true;
                }
            }

            $conn->close();
        }
    }
}

$status = febmart_status($form);
$alreadyInstalled = $status['connected'] && $status['db_exists'] && empty($status['missing']);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalasi Database | FEBMart</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        * { box-sizing: border-box; }

        body {
            min-height: 100vh;
            margin: 0;
            background: #f3f4f6;
            font-family: Arial, sans-serif;
            padding: 30px 16px;
        }

        .install-wrap {
            max-width: 860px;
            margin: 0 auto;
        }

        .install-header {
            text-align: center;
            margin-bottom: 28px;
        }

        .install-header .brand {
            font-size: 26px;
            font-weight: bold;
            color: #29245c;
        }

        .install-header .brand i {
            color: #ffad42;
            margin-right: 7px;
        }

        .install-header p {
            color: #6b7280;
            margin: 8px 0 0;
            font-size: 14px;
        }

        .card-install {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .card-install .card-body {
            padding: 34px 40px;
        }

        .step-title {
            font-size: 15px;
            font-weight: 700;
            color: #29245c;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 6px;
        }

        .step-title .step-num {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: #5142a5;
            color: #fff;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .step-desc {
            color: #6b7280;
            font-size: 13px;
            margin-bottom: 18px;
            padding-left: 34px;
        }

        .form-label {
            font-size: 13px;
            font-weight: 600;
            color: #333;
        }

        .form-control {
            font-size: 14px;
            padding: 10px 12px;
            border-color: #ddd;
        }

        .form-control:focus {
            border-color: #5142a5;
            box-shadow: 0 0 0 0.2rem rgba(81, 66, 165, 0.12);
        }

        .btn-install {
            background: #ffad42;
            border: none;
            color: #29245c;
            font-weight: bold;
            padding: 12px 22px;
            border-radius: 8px;
            transition: 0.2s;
        }

        .btn-install:hover {
            background: #f49a25;
            color: #29245c;
        }

        .btn-outline-install {
            border: 1px solid #5142a5;
            color: #5142a5;
            font-weight: 600;
            border-radius: 8px;
        }

        .btn-outline-install:hover {
            background: #5142a5;
            color: #fff;
        }

        .status-pill {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-pill.ok { background: #e6f7ee; color: #1a7f4b; }
        .status-pill.warn { background: #fff4e0; color: #b26a00; }
        .status-pill.bad { background: #fde8e8; color: #b02a2a; }

        .divider {
            border-top: 1px dashed #e5e7eb;
            margin: 26px 0;
        }

        .alert {
            font-size: 14px;
            border-radius: 10px;
        }

        .success-icon {
            font-size: 58px;
            color: #1a7f4b;
        }

        .copyright {
            text-align: center;
            color: #9ca3af;
            font-size: 12px;
            margin-top: 24px;
        }

        @media (max-width: 600px) {
            .card-install .card-body { padding: 24px 20px; }
        }
    </style>
</head>
<body>

<div class="install-wrap">

    <div class="install-header">
        <div class="brand">
            <i class="bi bi-cart3"></i>FEBMart
        </div>
        <p>Instalasi &amp; perbaikan database otomatis</p>
    </div>

    <div class="card card-install">
        <div class="card-body">

            <?php if ($success): ?>

                <div class="text-center py-4">
                    <div class="success-icon">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>

                    <h4 class="mt-3 fw-bold" style="color:#29245c;">
                        Database siap digunakan!
                    </h4>

                    <p class="text-muted mb-4">
                        Database <strong><?= htmlspecialchars($dbName) ?></strong>
                        beserta seluruh tabel dan data contoh berhasil disiapkan.
                        <?php if ($justReinstalled): ?>
                            Database lama telah dihapus dan dibuat ulang.
                        <?php endif; ?>
                    </p>

                    <div class="d-flex justify-content-center gap-2 flex-wrap">
                        <a href="auth/login.php" class="btn btn-install">
                            <i class="bi bi-box-arrow-in-right me-1"></i>Masuk ke FEBMart
                        </a>
                        <a href="dashboard.php" class="btn btn-outline-install">
                            <i class="bi bi-speedometer2 me-1"></i>Buka Dashboard
                        </a>
                    </div>

                    <p class="text-muted mt-4 mb-0" style="font-size:13px;">
                        Akun bawaan: <code>adminfebmart</code> (admin) dan
                        <code>kasir01</code> (kasir).
                    </p>
                </div>

            <?php else: ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong>Instalasi gagal:</strong>
                        <ul class="mb-0 mt-2 ps-3">
                            <?php foreach ($errors as $err): ?>
                                <li><?= htmlspecialchars($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php elseif (isset($_GET['error']) && $_GET['error'] === 'db'): ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        Database belum terhubung. Lengkapi formulir di bawah ini
                        untuk membuat dan mengonfigurasi database secara otomatis.
                    </div>
                <?php endif; ?>

                <?php if ($alreadyInstalled): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        Sistem sudah terhubung ke database <strong><?= htmlspecialchars($form['dbname']) ?></strong>
                        dan seluruh tabel tersedia. Anda dapat langsung
                        <a href="auth/login.php" class="alert-link">masuk</a>
                        atau <a href="dashboard.php" class="alert-link">buka dashboard</a>.
                    </div>
                <?php endif; ?>

                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-1">
                    <h5 class="fw-bold mb-0" style="color:#29245c;">
                        <i class="bi bi-gear me-2"></i>Konfigurasi Database
                    </h5>

                    <div>
                        <?php if ($status['connected']): ?>
                            <span class="status-pill ok">MySQL terhubung</span>
                        <?php else: ?>
                            <span class="status-pill bad">MySQL belum terhubung</span>
                        <?php endif; ?>

                        <?php if ($status['db_exists']): ?>
                            <span class="status-pill ok">Database ditemukan</span>
                        <?php else: ?>
                            <span class="status-pill warn">Database akan dibuat</span>
                        <?php endif; ?>
                    </div>
                </div>

                <p class="step-desc mb-4">
                    Isi kredensial MySQL di komputer ini. Sistem akan membuat database,
                    tabel, dan data contoh secara otomatis. Biasanya cukup menggunakan
                    default <code>localhost</code> / <code>root</code> tanpa password
                    (Laragon / XAMPP).
                </p>

                <form method="POST" action="install.php">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="host">Host MySQL</label>
                            <input type="text" class="form-control" id="host" name="host"
                                   value="<?= htmlspecialchars($form['host']) ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="dbname">Nama Database</label>
                            <input type="text" class="form-control" id="dbname" name="dbname"
                                   value="<?= htmlspecialchars($form['dbname']) ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="username">Username MySQL</label>
                            <input type="text" class="form-control" id="username" name="username"
                                   value="<?= htmlspecialchars($form['username']) ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="password">Password MySQL</label>
                            <input type="password" class="form-control" id="password" name="password"
                                   value="<?= htmlspecialchars($form['password']) ?>"
                                   placeholder="Kosongkan jika tanpa password"
                                   autocomplete="off">
                        </div>
                    </div>

                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" id="recreate" name="recreate">
                        <label class="form-check-label" for="recreate" style="font-size:13px;">
                            <strong class="text-danger">Buat ulang seluruh database &amp; data contoh</strong>
                            <span class="text-muted">(menghapus semua data yang sudah ada di database tersebut)</span>
                        </label>
                    </div>

                    <div class="d-flex gap-2 flex-wrap">
                        <button type="submit" class="btn btn-install">
                            <i class="bi bi-lightning-charge-fill me-1"></i>
                            Instal / Perbaiki Database
                        </button>

                        <a href="dashboard.php" class="btn btn-outline-install">
                            <i class="bi bi-arrow-right me-1"></i>
                            Coba buka sistem
                        </a>
                    </div>

                </form>

                <hr class="divider">

                <div>
                    <div class="step-title">
                        <span class="step-num">1</span> Uji koneksi MySQL
                    </div>
                    <div class="step-desc">
                        Sistem mencoba terhubung menggunakan host, username, dan password yang Anda isi.
                    </div>

                    <div class="step-title">
                        <span class="step-num">2</span> Buat database otomatis
                    </div>
                    <div class="step-desc">
                        Jika database belum ada, sistem membuatnya dengan charset utf8mb4.
                    </div>

                    <div class="step-title">
                        <span class="step-num">3</span> Buat tabel &amp; data contoh
                    </div>
                    <div class="step-desc">
                        Menjalankan <code>database/febmart.sql</code> (users, categories, products,
                        transactions, transaction_details + data contoh).
                    </div>

                    <div class="step-title">
                        <span class="step-num">4</span> Simpan konfigurasi lokal
                    </div>
                    <div class="step-desc">
                        Kredensial disimpan ke <code>config/database.local.php</code>
                        (khusus komputer ini, tidak ikut di-commit ke git).
                    </div>
                </div>

            <?php endif; ?>

        </div>
    </div>

    <div class="copyright">
        © 2026 FEBMart · Kelompok 23 · Praktikum Pemrograman Web 2026
    </div>

</div>

</body>
</html>
