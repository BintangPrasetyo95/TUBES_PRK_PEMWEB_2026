
<?php

// 1. Memanggil koneksi database
require_once '../config/database.php';

// 2. Memeriksa autentikasi pengguna
require_once 'auth_check.php';

// 3. Mengambil identitas mahasiswa dari session
$id_user = $_SESSION['id_user'];
$nama = $_SESSION['nama_lengkap'];

// 4. Mengambil seluruh kategori dari database
$queryKategori = $pdo->query("
    SELECT id_kategori, nama_kategori
    FROM kategori
    ORDER BY nama_kategori ASC
");

$kategoriList = $queryKategori->fetchAll();

// 5. Mengambil filter kategori dari URL
$id_kategori = filter_input(
    INPUT_GET,
    'kategori',
    FILTER_VALIDATE_INT
);

// 6. Membuat query riwayat aspirasi
$sql = "
    SELECT
        aspirasi.id_aspirasi,
        aspirasi.judul_aspirasi,
        aspirasi.isi_aspirasi,
        aspirasi.tanggal_kirim,
        aspirasi.status,
        aspirasi.is_anonim,
        kategori.nama_kategori

    FROM aspirasi

    INNER JOIN kategori
    ON aspirasi.id_kategori = kategori.id_kategori

    WHERE aspirasi.id_user = :id_user
";

$params = [
    ':id_user' => $id_user
];

// 7. Menambahkan filter kategori jika dipilih
if ($id_kategori !== false && $id_kategori !== null && $id_kategori > 0) {

    $sql .= " AND aspirasi.id_kategori = :id_kategori";

    $params[':id_kategori'] = $id_kategori;
}

// 8. Mengurutkan aspirasi terbaru
$sql .= " ORDER BY aspirasi.tanggal_kirim DESC";

// 9. Menjalankan query
$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$aspirasiList = $stmt->fetchAll();

// 10. Menghitung jumlah aspirasi berdasarkan status
$queryJumlah = $pdo->prepare("
    SELECT
        COUNT(*) AS total,
        SUM(status = 'menunggu') AS menunggu,
        SUM(status = 'ditinjau') AS ditinjau,
        SUM(status = 'selesai') AS selesai

    FROM aspirasi

    WHERE id_user = :id_user
");

$queryJumlah->execute([
    ':id_user' => $id_user
]);

$jumlah = $queryJumlah->fetch();

// 11. Fungsi keamanan untuk menampilkan teks
function e($data) {
    return htmlspecialchars(
        (string) $data,
        ENT_QUOTES,
        'UTF-8'
    );
}

// 12. Mengatur warna status
$warnaStatus = [
    'menunggu' => 'bg-amber-50 text-amber-700 border-amber-100',
    'ditinjau' => 'bg-blue-50 text-blue-700 border-blue-100',
    'selesai' => 'bg-emerald-50 text-emerald-700 border-emerald-100'
];

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard Mahasiswa - KabarSih</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .gradient-bg {
            background: linear-gradient(135deg, #172554 0%, #1d4ed8 55%, #3b82f6 100%);
        }

        .card-hover {
            transition: all 0.25s ease;
        }

        .card-hover:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 25px rgba(15, 23, 42, 0.07);
        }

        .btn-hover {
            transition: all 0.25s ease;
        }

        .btn-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 18px rgba(37, 99, 235, 0.2);
        }

        .input-field:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.10);
        }

    </style>

</head>

<body class="bg-slate-50 min-h-screen">


    <!-- NAVBAR -->
    <nav class="bg-white border-b border-slate-100 sticky top-0 z-50">

        <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">

            <!-- LOGO -->
            <div class="flex items-center gap-3">

                <div class="gradient-bg w-10 h-10 rounded-xl flex items-center justify-center text-white font-extrabold text-lg shadow-md">
                    K
                </div>

                <div>

                    <h1 class="text-lg font-extrabold text-slate-800">
                        KabarSih
                    </h1>

                    <p class="text-xs text-slate-400">
                        Suara Mahasiswa, Perubahan Bersama
                    </p>

                </div>

            </div>


            <!-- PROFILE -->
            <div class="flex items-center gap-3">

                <div class="hidden sm:block text-right">

                    <p class="text-sm font-bold text-slate-700">
                        <?= e($nama); ?>
                    </p>

                    <p class="text-xs text-slate-400">
                        Mahasiswa
                    </p>

                </div>

                <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold">

                    <?= e(strtoupper(substr($nama, 0, 1))); ?>

                </div>

            </div>

        </div>

    </nav>


    <!-- MAIN CONTENT -->
    <main class="max-w-6xl mx-auto px-5 py-10">


        <!-- WELCOME SECTION -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-5 mb-9">

            <div>

                <div class="inline-flex items-center gap-2 bg-blue-50 text-blue-700 px-3 py-1.5 rounded-full text-xs font-bold mb-4">

                    <span>✦</span>
                    STUDENT DASHBOARD

                </div>

                <h2 class="text-3xl font-extrabold text-slate-800">

                    Halo, <?= e($nama); ?>!

                </h2>

                <p class="text-slate-500 text-sm mt-2">

                    Selamat datang kembali di KabarSih.
                    Yuk, pantau perkembangan aspirasi kamu!

                </p>

            </div>


            <a href="aspirasi_tambah.php"
               class="btn-hover gradient-bg text-white px-6 py-3.5 rounded-xl font-bold text-sm shadow-md inline-flex items-center gap-2">

                <span class="text-lg">+</span>
                Kirim Aspirasi

            </a>

        </div>


        <!-- SUCCESS NOTIFICATION -->
        <?php if (isset($_GET['pesan']) && $_GET['pesan'] === 'berhasil'): ?>

            <div class="mb-7 bg-emerald-50 border border-emerald-200 rounded-xl p-4 flex items-center gap-3">

                <div class="w-9 h-9 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600">
                    ✓
                </div>

                <div>

                    <p class="text-sm font-bold text-emerald-800">
                        Aspirasi berhasil dikirim!
                    </p>

                    <p class="text-xs text-emerald-600 mt-1">
                        Terima kasih telah menyampaikan aspirasi melalui KabarSih.
                    </p>

                </div>

            </div>

        <?php endif; ?>


        <!-- STATISTICS -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-9">


            <!-- TOTAL -->
            <div class="card-hover bg-white rounded-2xl p-6 border border-slate-100 shadow-sm">

                <div class="flex justify-between items-center">

                    <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center text-2xl">
                        📋
                    </div>

                    <span class="text-xs font-semibold text-blue-600 bg-blue-50 px-3 py-1 rounded-full">
                        Keseluruhan
                    </span>

                </div>

                <p class="text-sm text-slate-500 mt-5">
                    Total Aspirasi
                </p>

                <h3 class="text-3xl font-extrabold text-slate-800 mt-1">
                    <?= (int) $jumlah['total']; ?>
                </h3>

                <p class="text-xs text-slate-400 mt-2">
                    Aspirasi yang telah dikirim
                </p>

            </div>


            <!-- MENUNGGU -->
            <div class="card-hover bg-white rounded-2xl p-6 border border-slate-100 shadow-sm">

                <div class="flex justify-between items-center">

                    <div class="w-12 h-12 rounded-xl bg-amber-50 flex items-center justify-center text-2xl">
                        ⏳
                    </div>

                    <span class="text-xs font-semibold text-amber-600 bg-amber-50 px-3 py-1 rounded-full">
                        Pending
                    </span>

                </div>

                <p class="text-sm text-slate-500 mt-5">
                    Menunggu
                </p>

                <h3 class="text-3xl font-extrabold text-amber-600 mt-1">
                    <?= (int) $jumlah['menunggu']; ?>
                </h3>

                <p class="text-xs text-slate-400 mt-2">
                    Belum mendapatkan tinjauan
                </p>

            </div>


            <!-- DITINJAU -->
            <div class="card-hover bg-white rounded-2xl p-6 border border-slate-100 shadow-sm">

                <div class="flex justify-between items-center">

                    <div class="w-12 h-12 rounded-xl bg-sky-50 flex items-center justify-center text-2xl">
                        🔍
                    </div>

                    <span class="text-xs font-semibold text-sky-600 bg-sky-50 px-3 py-1 rounded-full">
                        Proses
                    </span>

                </div>

                <p class="text-sm text-slate-500 mt-5">
                    Ditinjau
                </p>

                <h3 class="text-3xl font-extrabold text-sky-600 mt-1">
                    <?= (int) $jumlah['ditinjau']; ?>
                </h3>

                <p class="text-xs text-slate-400 mt-2">
                    Sedang dalam proses peninjauan
                </p>

            </div>


            <!-- SELESAI -->
            <div class="card-hover bg-white rounded-2xl p-6 border border-slate-100 shadow-sm">

                <div class="flex justify-between items-center">

                    <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center text-2xl">
                        ✓
                    </div>

                    <span class="text-xs font-semibold text-emerald-600 bg-emerald-50 px-3 py-1 rounded-full">
                        Selesai
                    </span>

                </div>

                <p class="text-sm text-slate-500 mt-5">
                    Selesai
                </p>

                <h3 class="text-3xl font-extrabold text-emerald-600 mt-1">
                    <?= (int) $jumlah['selesai']; ?>
                </h3>

                <p class="text-xs text-slate-400 mt-2">
                    Aspirasi telah diselesaikan
                </p>

            </div>

        </div>


        <!-- FILTER CATEGORY -->
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 mb-9">

            <div class="flex items-center gap-3 mb-5">

                <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center text-xl">
                    🔎
                </div>

                <div>

                    <h3 class="font-bold text-slate-800">
                        Filter Aspirasi
                    </h3>

                    <p class="text-xs text-slate-400 mt-1">
                        Temukan aspirasi berdasarkan kategori.
                    </p>

                </div>

            </div>


            <form method="GET" action="dashboard.php">

                <div class="flex flex-col sm:flex-row gap-3">

                    <select
                        name="kategori"
                        class="input-field flex-1 border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-600 bg-white"
                    >

                        <option value="">
                            Semua Kategori
                        </option>

                        <?php foreach ($kategoriList as $kategori): ?>

                            <option
                                value="<?= (int) $kategori['id_kategori']; ?>"
                                <?= ($id_kategori == $kategori['id_kategori']) ? 'selected' : ''; ?>
                            >

                                <?= e($kategori['nama_kategori']); ?>

                            </option>

                        <?php endforeach; ?>

                    </select>


                    <button
                        type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-7 py-3 rounded-xl text-sm font-bold transition"
                    >

                        Terapkan Filter

                    </button>


                    <a href="dashboard.php"
                       class="bg-slate-100 hover:bg-slate-200 text-slate-600 px-7 py-3 rounded-xl text-sm font-semibold text-center transition">

                        Reset

                    </a>

                </div>

            </form>

        </div>


        <!-- HISTORY HEADER -->
        <div class="flex justify-between items-center mb-5">

            <div>

                <h3 class="text-xl font-extrabold text-slate-800">
                    Riwayat Aspirasi Saya
                </h3>

                <p class="text-sm text-slate-400 mt-1">
                    Pantau perkembangan aspirasi yang telah kamu kirim.
                </p>

            </div>

            <span class="hidden sm:inline-block text-xs font-semibold text-blue-600 bg-blue-50 px-3 py-2 rounded-lg">

                <?= count($aspirasiList); ?> Aspirasi

            </span>

        </div>


        <!-- ASPIRATION HISTORY -->
        <?php if (count($aspirasiList) > 0): ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                <?php foreach ($aspirasiList as $aspirasi): ?>

                    <?php

                    $status = $aspirasi['status'];

                    $warna = $warnaStatus[$status]
                        ?? 'bg-slate-100 text-slate-600 border-slate-200';

                    ?>

                    <div class="card-hover bg-white rounded-2xl border border-slate-100 shadow-sm p-6">


                        <!-- CARD TOP -->
                        <div class="flex justify-between items-center gap-3 mb-5">

                            <span class="bg-blue-50 text-blue-700 px-3 py-1.5 rounded-lg text-xs font-bold">

                                <?= e($aspirasi['nama_kategori']); ?>

                            </span>

                            <span class="text-xs text-slate-400">

                                <?= e(date(
                                    'd M Y',
                                    strtotime($aspirasi['tanggal_kirim'])
                                )); ?>

                            </span>

                        </div>


                        <!-- TITLE -->
                        <h4 class="text-lg font-bold text-slate-800 mb-3">

                            <?= e($aspirasi['judul_aspirasi']); ?>

                        </h4>


                        <!-- CONTENT -->
                        <p class="text-sm text-slate-500 leading-relaxed mb-5 whitespace-pre-line">

                            <?= e($aspirasi['isi_aspirasi']); ?>

                        </p>


                        <!-- CARD BOTTOM -->
                        <div class="border-t border-slate-100 pt-4 flex justify-between items-center gap-3">


                            <!-- ANONYMOUS STATUS -->
                            <div class="flex items-center gap-2">

                                <?php if ($aspirasi['is_anonim']): ?>

                                    <span class="text-sm">🔒</span>

                                    <span class="text-xs text-slate-500">
                                        Dikirim sebagai Anonim
                                    </span>

                                <?php else: ?>

                                    <span class="text-sm">👤</span>

                                    <span class="text-xs text-slate-500">
                                        Dikirim atas nama saya
                                    </span>

                                <?php endif; ?>

                            </div>


                            <!-- STATUS BADGE -->
                            <span class="<?= e($warna); ?> border px-3 py-1.5 rounded-full text-xs font-bold whitespace-nowrap">

                                <?= e(ucfirst($status)); ?>

                            </span>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>


        <?php else: ?>


            <!-- EMPTY STATE -->
            <div class="bg-white border border-slate-100 rounded-2xl shadow-sm p-12 text-center">

                <div class="w-20 h-20 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-5 text-3xl">
                    📝
                </div>

                <h4 class="text-xl font-bold text-slate-800 mb-2">

                    Belum Ada Aspirasi

                </h4>

                <p class="text-sm text-slate-400 max-w-md mx-auto leading-relaxed mb-7">

                    Kamu belum memiliki riwayat aspirasi pada kategori ini.
                    Yuk, mulai sampaikan ide, kritik, atau saranmu melalui KabarSih!

                </p>

                <a href="aspirasi_tambah.php"
                   class="btn-hover inline-flex items-center gap-2 gradient-bg text-white px-6 py-3 rounded-xl font-bold text-sm shadow-md">

                    <span>+</span>
                    Kirim Aspirasi Pertama

                </a>

            </div>


        <?php endif; ?>


        <!-- FOOTER -->
        <footer class="text-center mt-14 pb-6">

            <div class="border-t border-slate-200 pt-6">

                <p class="text-sm font-bold text-slate-600">
                    KabarSih
                </p>

                <p class="text-xs text-slate-400 mt-2">
                    Suara Mahasiswa, Perubahan Bersama.
                </p>

                <p class="text-xs text-slate-400 mt-2">
                    © <?= date('Y'); ?> KabarSih. All rights reserved.
                </p>

            </div>

        </footer>


    </main>

</body>
</html>
