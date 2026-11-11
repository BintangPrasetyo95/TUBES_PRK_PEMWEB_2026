<?php

require_once '../config/database.php';
require_once 'auth_check.php';

// Mengambil kategori dari database
$query = $pdo->query("
    SELECT id_kategori, nama_kategori
    FROM kategori
    ORDER BY nama_kategori ASC
");

$kategoriList = $query->fetchAll();

$nama = $_SESSION['nama_lengkap'] ?? 'Mahasiswa';

function e($data) {
    return htmlspecialchars((string) $data, ENT_QUOTES, 'UTF-8');
}

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Kirim Aspirasi - KabarSi FISIP</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .gradient-bg {
            background: linear-gradient(135deg, #ea580c 0%, #ff5a1f 55%, #f97316 100%);
        }

        .input-field {
            transition: all 0.2s ease;
        }

        .input-field:focus {
            border-color: #ff5a1f;
            box-shadow: 0 0 0 4px rgba(255, 90, 31, 0.12);
            outline: none;
        }

        .submit-btn {
            transition: all 0.25s ease;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(234, 88, 12, 0.25);
        }
    </style>

</head>

<body class="bg-slate-50 min-h-screen">

    <!-- NAVBAR -->
    <nav class="bg-white border-b border-slate-100">

        <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">

            <div class="flex items-center gap-3">

                <div class="gradient-bg w-10 h-10 rounded-xl flex items-center justify-center text-white font-bold text-lg shadow-md">
                    KS
                </div>

                <div>
                    <h1 class="text-lg font-extrabold text-slate-800">
                        KabarSi <span class="text-[#FF5A1F]">FISIP</span>
                    </h1>

                    <p class="text-xs text-slate-400">
                        Suara Mahasiswa, Perubahan Bersama
                    </p>
                </div>

            </div>

            <div class="hidden sm:flex items-center gap-3">

                <div class="text-right">
                    <p class="text-sm font-semibold text-slate-700">
                        <?= e($nama); ?>
                    </p>

                    <p class="text-xs text-slate-400">
                        Mahasiswa
                    </p>
                </div>

                <div class="w-10 h-10 rounded-full bg-orange-100 text-[#FF5A1F] flex items-center justify-center font-bold">
                    <?= strtoupper(substr($nama, 0, 1)); ?>
                </div>

            </div>

        </div>

    </nav>


    <!-- MAIN CONTENT -->
    <main class="max-w-6xl mx-auto px-5 py-10">

        <!-- BREADCRUMB -->
        <div class="mb-7">

            <a href="dashboard.php"
               class="inline-flex items-center gap-2 text-sm font-semibold text-[#FF5A1F] hover:text-orange-700 transition">

                <span>←</span>
                Kembali ke Dashboard

            </a>

        </div>


        <!-- PAGE HEADER -->
        <div class="mb-8">

            <div class="inline-flex items-center gap-2 bg-orange-50 text-[#FF5A1F] px-4 py-2 rounded-full text-xs font-bold mb-4 border border-orange-100">

                <span>✦</span>
                RUANG ASPIRASI MAHASISWA

            </div>

            <h2 class="text-3xl md:text-4xl font-extrabold text-slate-800 mb-3">

                Suaramu Berarti.

                <span class="text-[#FF5A1F]">
                    Sampaikan Sekarang!
                </span>

            </h2>

            <p class="text-slate-500 text-sm md:text-base leading-relaxed max-w-2xl">

                Setiap pendapat memiliki arti. Sampaikan ide, kritik,
                maupun saran untuk menciptakan lingkungan FISIP yang
                lebih baik bersama KabarSi.

            </p>

        </div>


        <!-- FORM LAYOUT -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-7 items-start">


            <!-- LEFT INFORMATION -->
            <div class="space-y-5">

                <!-- ORANGE INFORMATION CARD -->
                <div class="gradient-bg rounded-2xl p-7 text-white shadow-lg relative overflow-hidden">

                    <div class="absolute -right-10 -top-10 w-40 h-40 bg-white opacity-10 rounded-full"></div>

                    <div class="absolute -right-5 bottom-0 w-28 h-28 bg-white opacity-10 rounded-full"></div>

                    <div class="relative z-10">

                        <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center text-2xl mb-5">
                            💬
                        </div>

                        <h3 class="text-xl font-bold mb-3">
                            Satu Suara, Banyak Perubahan
                        </h3>

                        <p class="text-orange-100 text-sm leading-relaxed">
                            Aspirasi yang kamu sampaikan menjadi bagian
                            dari langkah untuk membangun lingkungan
                            kampus yang lebih baik.
                        </p>

                    </div>

                </div>


                <!-- TIPS CARD -->
                <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm">

                    <h3 class="font-bold text-slate-800 mb-5">
                        Tips Menyampaikan Aspirasi
                    </h3>

                    <div class="space-y-5">

                        <div class="flex gap-3">

                            <div class="w-9 h-9 shrink-0 rounded-lg bg-orange-50 flex items-center justify-center">
                                📝
                            </div>

                            <div>
                                <p class="text-sm font-semibold text-slate-700">
                                    Gunakan judul yang jelas
                                </p>

                                <p class="text-xs text-slate-400 mt-1">
                                    Gambarkan inti aspirasi secara singkat.
                                </p>
                            </div>

                        </div>


                        <div class="flex gap-3">

                            <div class="w-9 h-9 shrink-0 rounded-lg bg-orange-50 flex items-center justify-center">
                                🎯
                            </div>

                            <div>
                                <p class="text-sm font-semibold text-slate-700">
                                    Sampaikan secara spesifik
                                </p>

                                <p class="text-xs text-slate-400 mt-1">
                                    Jelaskan permasalahan dan harapanmu.
                                </p>
                            </div>

                        </div>


                        <div class="flex gap-3">

                            <div class="w-9 h-9 shrink-0 rounded-lg bg-orange-50 flex items-center justify-center">
                                🤝
                            </div>

                            <div>
                                <p class="text-sm font-semibold text-slate-700">
                                    Gunakan bahasa yang sopan
                                </p>

                                <p class="text-xs text-slate-400 mt-1">
                                    Sampaikan pendapat dengan baik dan santun.
                                </p>
                            </div>

                        </div>

                    </div>

                </div>


                <!-- PRIVACY CARD -->
                <div class="bg-orange-50 border border-orange-100 rounded-2xl p-5">

                    <div class="flex gap-3">

                        <div class="text-xl">
                            🔒
                        </div>

                        <div>
                            <h4 class="text-sm font-bold text-orange-900">
                                Privasi Terjaga
                            </h4>

                            <p class="text-xs text-orange-700 leading-relaxed mt-2">
                                Kamu dapat memilih opsi anonim.
                                Identitas tetap tercatat di database
                                untuk keperluan pengelolaan admin.
                            </p>
                        </div>

                    </div>

                </div>

            </div>


            <!-- RIGHT FORM -->
            <div class="lg:col-span-2">

                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">

                    <!-- FORM HEADER -->
                    <div class="px-7 py-6 border-b border-slate-100">

                        <div class="flex items-center gap-3">

                            <div class="w-11 h-11 rounded-xl bg-orange-50 flex items-center justify-center text-xl">
                                📋
                            </div>

                            <div>
                                <h3 class="text-lg font-bold text-slate-800">
                                    Formulir Aspirasi
                                </h3>

                                <p class="text-xs text-slate-400 mt-1">
                                    Lengkapi informasi berikut dengan benar.
                                </p>
                            </div>

                        </div>

                    </div>


                    <!-- FORM BODY -->
                    <form action="aspirasi_simpan.php" method="POST" class="p-7">


                        <!-- JUDUL -->
                        <div class="mb-6">

                            <label class="block text-sm font-bold text-slate-700 mb-2">

                                Judul Aspirasi

                                <span class="text-red-500">*</span>

                            </label>

                            <input
                                type="text"
                                name="judul_aspirasi"
                                maxlength="255"
                                required
                                placeholder="Contoh: Perbaikan Fasilitas Ruang Kelas"
                                class="input-field w-full border border-slate-200 rounded-xl px-4 py-3.5 text-sm text-slate-700 placeholder:text-slate-300"
                            >

                            <p class="text-xs text-slate-400 mt-2">
                                Buat judul yang singkat dan mudah dipahami.
                            </p>

                        </div>


                        <!-- KATEGORI -->
                        <div class="mb-6">

                            <label class="block text-sm font-bold text-slate-700 mb-2">

                                Kategori Aspirasi

                                <span class="text-red-500">*</span>

                            </label>

                            <select
                                name="id_kategori"
                                required
                                class="input-field w-full border border-slate-200 rounded-xl px-4 py-3.5 text-sm text-slate-600 bg-white"
                            >

                                <option value="">
                                    Pilih kategori aspirasi
                                </option>

                                <?php foreach ($kategoriList as $kategori): ?>

                                    <option value="<?= (int) $kategori['id_kategori']; ?>">

                                        <?= e($kategori['nama_kategori']); ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <!-- ISI ASPIRASI -->
                        <div class="mb-6">

                            <label class="block text-sm font-bold text-slate-700 mb-2">

                                Isi Aspirasi

                                <span class="text-red-500">*</span>

                            </label>

                            <textarea
                                name="isi_aspirasi"
                                rows="7"
                                required
                                placeholder="Ceritakan aspirasi, kritik, saran, atau pendapatmu secara jelas dan terperinci..."
                                class="input-field w-full border border-slate-200 rounded-xl px-4 py-4 text-sm text-slate-700 placeholder:text-slate-300 resize-y"
                            ></textarea>

                            <p class="text-xs text-slate-400 mt-2">
                                Jelaskan permasalahan dan solusi yang kamu harapkan.
                            </p>

                        </div>


                        <!-- ANONIM -->
                        <div class="mb-7">

                            <label class="flex items-start gap-3 p-4 bg-slate-50 border border-slate-200 rounded-xl cursor-pointer hover:bg-orange-50 hover:border-orange-200 transition">

                                <input
                                    type="checkbox"
                                    name="is_anonim"
                                    value="1"
                                    class="w-5 h-5 mt-0.5 accent-[#FF5A1F] shrink-0"
                                >

                                <div>

                                    <p class="text-sm font-bold text-slate-700">
                                        Kirim sebagai Anonim
                                    </p>

                                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                                        Namamu tidak ditampilkan pada tampilan publik.
                                        Identitas tetap tersimpan di database admin.
                                    </p>

                                </div>

                            </label>

                        </div>


                        <!-- BUTTON -->
                        <div class="border-t border-slate-100 pt-6">

                            <button
                                type="submit"
                                class="submit-btn w-full gradient-bg text-white py-4 rounded-xl font-bold text-sm shadow-md"
                            >

                                Kirim Aspirasi
                                <span class="ml-2">➜</span>

                            </button>

                            <p class="text-center text-xs text-slate-400 mt-4">
                                Terima kasih telah berkontribusi untuk lingkungan FISIP.
                            </p>

                        </div>


                    </form>

                </div>

            </div>

        </div>


        <!-- FOOTER -->
        <footer class="text-center mt-12 pb-5">

            <p class="text-xs text-slate-400">
                © <?= date('Y'); ?> KabarSi FISIP.
                Suara Mahasiswa, Perubahan Bersama.
            </p>

        </footer>

    </main>

</body>
</html>