<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CiviLens - Platform Pemantauan Isu</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased">

    <nav class="bg-blue-900 text-white shadow-lg">
        <div class="max-w-6xl mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <a href="index.php" class="text-2xl font-bold tracking-wider">🏛️ CiviLens</a>
                
                <div class="hidden md:flex space-x-6">
                    <a href="index.php" class="hover:text-blue-300 transition">Berita</a>
                    <a href="petisi.php" class="hover:text-blue-300 transition">Petisi Warga</a>
                    <a href="aspirasi.php" class="hover:text-blue-300 transition">Ruang Aspirasi</a>
                    <a href="lapor_hoaks.php" class="hover:text-blue-300 transition">Cek Fakta</a>
                </div>

                <div>
                    <a href="admin/login.php" class="bg-blue-700 hover:bg-blue-600 px-4 py-2 rounded text-sm font-semibold transition">Login Admin</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-6xl mx-auto px-4 py-8 min-h-screen">