<?php
// app/Views/dashboard.php
// Tugas: Tampilan utama + cuaca (API pihak ketiga).
// Diisi oleh: Faqih.

$title = "Dashboard";
require_once __DIR__ . "/layouts/header.php";
require_once __DIR__ . "/layouts/sidebar.php";
?>
            <div class="container-fluid p-4">
                <h1 class="mb-4">Dashboard KebunKu</h1>

                <!-- TODO: Ringkasan statistik (jumlah lahan, siklus aktif, total panen, dll.) -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-white bg-primary mb-3">
                            <div class="card-body">
                                <h5 class="card-title">Total Lahan</h5>
                                <p class="card-text display-6" id="stat-lahan">0</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-success mb-3">
                            <div class="card-body">
                                <h5 class="card-title">Siklus Aktif</h5>
                                <p class="card-text display-6" id="stat-siklus">0</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-info mb-3">
                            <div class="card-body">
                                <h5 class="card-title">Item Inventaris</h5>
                                <p class="card-text display-6" id="stat-inventaris">0</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-warning mb-3">
                            <div class="card-body">
                                <h5 class="card-title">Total Panen (kg)</h5>
                                <p class="card-text display-6" id="stat-panen">0</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TODO: Widget cuaca dari API pihak ketiga -->
                <div class="card">
                    <div class="card-header">Cuaca Sekarang</div>
                    <div class="card-body" id="weather-widget">
                        Memuat data cuaca...
                    </div>
                </div>
            </div>
<?php
require_once __DIR__ . "/layouts/footer.php";
?>