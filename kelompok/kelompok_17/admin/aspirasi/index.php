<?php
require_once __DIR__ . '/../../includes/auth_admin.php';
require_once __DIR__ . '/../../includes/csrf.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title>Kelola Aspirasi - KabarSih</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-800">
<?php include __DIR__ . '/../../components/admin_nav.php'; ?>

<main class="max-w-6xl mx-auto px-4 py-6">
    <h1 class="text-2xl font-bold mb-4">Kelola Aspirasi</h1>
    <div id="alert-box" class="mb-4 rounded-lg p-3" role="status" style="display:none;"></div>

    <div class="bg-white rounded-lg shadow overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-100 text-left">
                <tr>
                    <th class="p-3">Judul</th>
                    <th class="p-3">Kategori</th>
                    <th class="p-3">Pengirim</th>
                    <th class="p-3">Tanggal</th>
                    <th class="p-3">Status</th>
                </tr>
            </thead>
            <tbody id="tabel-body-aspirasi">
                <tr><td colspan="5" class="p-6 text-center text-slate-500">Memuat data dari server...</td></tr>
            </tbody>
        </table>
    </div>
</main>
<script src="../../js/app.js"></script>
</body>
</html>
