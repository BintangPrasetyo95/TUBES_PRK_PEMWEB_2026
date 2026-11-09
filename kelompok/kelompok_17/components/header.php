<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KabarSi FISIP - Portal Berita & Aspirasi Mahasiswa</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&family=Space+Mono:wght@400;700&display=swap');

        /* Navbar & Utility Neo-Brutalism Style */
        .nav-kb {
            font-family: 'Space Grotesk', system-ui, sans-serif;
            background-color: #141414;
            border-bottom: 3px solid #141414;
        }
        .nav-btn {
            font-family: 'Space Mono', monospace;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.04em;
            padding: 0.5rem 1rem;
            border: 2px solid #141414;
            transition: all 0.15s ease;
        }
        .nav-btn-or {
            background-color: #FF5A1F;
            color: #ffffff !important;
            box-shadow: 3px 3px 0 #F6F2E9;
        }
        .nav-btn-or:hover {
            transform: translate(-2px, -2px);
            box-shadow: 5px 5px 0 #FFE24A;
        }
        .nav-btn-dark {
            background-color: #FFFDF8;
            color: #141414 !important;
            box-shadow: 3px 3px 0 #FF5A1F;
        }
        .nav-btn-dark:hover {
            transform: translate(-2px, -2px);
            box-shadow: 5px 5px 0 #FFFDF8;
        }
        .nav-link {
            font-family: 'Space Mono', monospace;
            font-size: 0.8rem;
            text-transform: uppercase;
            color: #F6F2E9;
            transition: color 0.15s ease;
        }
        .nav-link:hover {
            color: #FFE24A;
        }
    </style>
</head>
<body class="bg-[#F6F2E9] text-[#141414] min-h-screen flex flex-col justify-between font-sans">

    <!-- Navigation Bar Neo-Brutalisme -->
    <nav class="nav-kb sticky top-0 z-50 text-[#F6F2E9] shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                
                <!-- Logo KabarSi -->
                <div class="flex items-center space-x-3">
                    <a href="index.php" class="font-bold text-xl tracking-wide flex items-center space-x-2 group">
                        <span class="bg-[#FF5A1F] text-[#FFFDF8] border-2 border-[#FFFDF8] px-2.5 py-0.5 font-extrabold text-sm shadow-[2px_2px_0px_#FFE24A] group-hover:translate-x-0.5 transition-transform">
                            KS
                        </span>
                        <span class="font-extrabold text-lg text-[#F6F2E9] group-hover:text-[#FFE24A] transition-colors">
                            KabarSi <span class="text-[#FF5A1F]">FISIP</span>
                        </span>
                    </a>
                </div>

                <!-- Nav Links -->
                <div class="flex items-center space-x-4 sm:space-x-6">
                    <a href="index.php" class="nav-link">Beranda</a>
                    <a href="index.php#berita" class="nav-link hidden sm:inline-block">Berita</a>
                    <a href="index.php#aspirasi" class="nav-link hidden sm:inline-block">Aspirasi</a>
                    
                    <?php if (isset($_SESSION['id_user'])): ?>
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                            <a href="admin/index.php" class="nav-btn nav-btn-or">Panel Admin</a>
                        <?php else: ?>
                            <a href="user/dashboard.php" class="nav-btn nav-btn-or">Dashboard Saya</a>
                        <?php endif; ?>
                        
                        <a href="auth/logout.php" class="nav-btn bg-[#FF9A94] text-[#141414] hover:bg-red-500 hover:text-white">Logout</a>
                    <?php else: ?>
                        <a href="auth/login.php" class="nav-link">Login</a>
                        <a href="auth/register.php" class="nav-btn nav-btn-or">Daftar</a>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <main class="mb-auto">