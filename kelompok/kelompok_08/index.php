<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BUYPANEN - Hasil Panen Segar</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons (Opsional untuk ikon lokasi & WA) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">

    <!-- 1. NAVBAR / HEADER -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-success sticky-top shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">🌾 BUYPANEN</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link active" href="#hero">Beranda</a></li>
                    <li class="nav-item"><a class="nav-link" href="#katalog">Katalog Panen</a></li>
                    <li class="nav-item"><a class="nav-link btn btn-outline-light ms-lg-3 px-3 mt-2 mt-lg-0" href="../../auth/login.php">Login Petani</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- 2. HERO SECTION (LANDING PAGE) -->
    <section id="hero" class="bg-success text-white py-5 text-center position-relative">
        <div class="container py-4">
            <h1 class="display-4 fw-bold mb-3">Panen Segar Langsung dari Petani</h1>
            <p class="lead mb-4">Dapatkan komoditas sayur, buah, dan pangan berkualitas tanpa perantara.</p>
            <a href="#katalog" class="btn btn-warning btn-lg fw-semibold px-4 shadow">Mulai Belanja</a>
        </div>
    </section>

    <!-- 3. KATALOG UTAMA -->
    <section id="katalog" class="container py-5">
        <div class="text-center mb-5">
            <h2 class="fw-bold">Katalog Hasil Panen</h2>
            <p class="text-muted">Pilih produk segar dan hubungi petaninya secara langsung</p>
        </div>

        <!-- TEMPAT ZAHRA MENEMPATKAN FORM SEARCH & FILTER (PRODUK/FILTER-SEARCH.PHP) -->
        <div class="row justify-content-center mb-4">
            <div class="col-md-8">
                <!-- Zahra nanti tinggal memasukkan include atau form filter di sini -->
            </div>
        </div>

        <!-- GRID KATALOG PRODUK -->
        <div class="row g-4">
            
            <?php
            // NANTI JIKA DATABASE SUDAH SIAP, UNCOMMENT LOGIKA QUERY & LOOPING INI:
            /*
            include '../../config/database.php';
            $query = "SELECT p.*, u.nama AS nama_petani, pt.lokasi_kebun, pt.no_whatsapp, k.nama_kategori 
                      FROM produk p
                      JOIN users u ON p.user_id = u.id
                      JOIN petani pt ON u.id = pt.user_id
                      JOIN kategori k ON p.kategori_id = k.id
                      WHERE p.status = 'tayang'";
            $result = mysqli_query($conn, $query);

            while ($row = mysqli_fetch_assoc($result)) {
                $pesan = "Halo " . $row['nama_petani'] . ", saya tertarik membeli " . $row['nama_produk'] . " dari BUYPANEN.";
                $wa_link = "https://wa.me/" . $row['no_whatsapp'] . "?text=" . urlencode($pesan);
            */
            ?>

            <!-- DUMMY CARD PRODUK (Untuk Uji Tampilan Awal) -->
            <div class="col-lg-3 col-md-4 col-sm-6">
                <div class="card h-100 shadow-sm border-0">
                    <img src="https://via.placeholder.com/300x200?text=Cabai+Merah" class="card-img-top" alt="Cabai Merah">
                    <div class="card-body d-flex flex-column">
                        <div class="mb-2">
                            <span class="badge bg-danger">Sayur</span>
                        </div>
                        <h5 class="card-title fw-bold">Cabai Merah Keriting</h5>
                        <p class="text-success fw-bold fs-5 mb-1">Rp 35.000 <small class="text-muted fs-6">/ kg</small></p>
                        <p class="text-muted small mb-2">Stok: 50 kg</p>
                        
                        <hr class="my-2">
                        
                        <p class="small text-secondary mb-1"><i class="bi bi-person-fill"></i> Pak Sutrisno</p>
                        <p class="small text-secondary mb-3"><i class="bi bi-geo-alt-fill"></i> Lampung Selatan</p>
                        
                        <a href="https://wa.me/6281234567890?text=Halo%20Pak%20Sutrisno,%20saya%20tertarik%20membeli%20Cabai%20Merah%20Keriting" target="_blank" class="btn btn-outline-success w-100 mt-auto">
                            <i class="bi bi-whatsapp"></i> Hubungi Petani
                        </a>
                    </div>
                </div>
            </div>
            <!-- END DUMMY CARD -->

            <?php 
            // } // Tutup kurung while
            ?>

        </div>
    </section>

    <!-- 4. FOOTER -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container text-center">
            <p class="mb-0">&copy; 2026 BUYPANEN - Tugas Besar Pemrograman Web Kelompok 08</p>
        </div>
    </footer>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>