<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold text-success" href="index.php">Sehatin</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item">
                    <a class="nav-link active" href="index.php">Beranda</a>
                </li>
                <li class="nav-item ms-lg-3">
                    <form action="search.php" method="GET" class="d-flex">
                        <input class="form-control form-control-sm me-2" type="search" name="keyword" placeholder="Cari artikel..." required>
                        <button class="btn btn-outline-success btn-sm" type="submit">Cari</button>
                    </form>
                </li>
                <li class="nav-item ms-lg-3">
                    <a class="btn btn-success btn-sm text-white" href="admin/login.php">Login Admin</a>
                </li>
            </ul>
        </div>
    </div>
</nav>