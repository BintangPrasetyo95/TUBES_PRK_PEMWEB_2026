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
        /* Font Sans-Serif Default Sistem (Tailwind UI Standard) */
        body, .nav-kb {
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }

        .nav-kb {
            background-color: #141414;
            border-bottom: 1px solid #141414;
        }
        
        .nav-btn {
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.03em;
            padding: 0.5rem 1rem;
            border: 1px solid transparent;
            border-radius: 6px;
            transition: all 0.2s ease;
        }
        
        .nav-btn-or {
            background-color: #FF5A1F;
            color: #ffffff !important;
            border-color: #FF5A1F;
        }
        .nav-btn-or:hover {
            background-color: #e04a13;
            border-color: #e04a13;
        }
        
        .nav-btn-dark {
            background-color: #FFFDF8;
            color: #141414 !important;
            border-color: #FFFDF8;
        }
        .nav-btn-dark:hover {
            background-color: #FFD9C7;
        }

        .nav-btn-logout {
            background-color: #ef4444;
            color: #ffffff !important;
        }
        .nav-btn-logout:hover {
            background-color: #dc2626;
        }

        .nav-link {
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #F6F2E9;
            transition: color 0.15s ease;
        }
        .nav-link:hover {
            color: #FF5A1F;
        }
    </style>
</head>
<body class="bg-[#F6F2E9] text-[#141414] min-h-screen flex flex-col justify-between font-sans">

    <!-- Navigation Bar Clean Modern Style -->
    <nav class="nav-kb sticky top-0 z-50 text-[#F6F2E9]">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                
                <!-- Logo KabarSi -->
                <div class="flex items-center space-x-3">
                    <a href="index.php" class="font-bold text-xl tracking-wide flex items-center space-x-2 group">
                        <span class="bg-[#FF5A1F] text-[#FFFDF8] px-2.5 py-0.5 font-extrabold text-sm rounded transition-opacity group-hover:opacity-90">
                            KS
                        </span>
                        <span class="font-extrabold text-lg text-[#F6F2E9] tracking-tight">
                            KabarSi <span class="text-[#FF5A1F]">FISIP</span>
                        </span>
                    </a>
                </div>

                <!-- Nav Links -->
                <div class="flex items-center space-x-4 sm:space-x-6">
                    <a href="index.php" class="nav-link">Beranda</a>
                    <a href="index.php#berita" class="nav-link hidden sm:inline-block">Berita</a>
                    <a href="index.php#aspirasi" class="nav-link hidden sm:inline-block">Aspirasi</a>
                    
                    <?php if (isset($_SESSION['id_user']) || isset($_SESSION['user_id'])): ?>
                        <?php if (isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin'): ?>
                            <a href="admin/index.php" class="nav-btn nav-btn-or">Panel Admin</a>
                        <?php else: ?>
                            <a href="user/dashboard.php" class="nav-btn nav-btn-or">Dashboard Saya</a>
                        <?php endif; ?>
                        
                        <a href="auth/logout.php" class="nav-btn nav-btn-logout">Logout</a>
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