<?php
// Penutup halaman. Dipakai di akhir setiap halaman:
//   require __DIR__ . '/../../includes/footer.php';
// Opsional sebelum require: $extra_js = ['assets/pemeriksaan/pemeriksaan.js'];
?>
</main>
<footer class="site-footer">
    <div class="container">
        <small>CEKHAK adalah alat bantu pemeriksaan kelengkapan administratif. Hasilnya bukan pendapat hukum final dan tidak menjamin dokumen sah atau diterima instansi tertentu.</small>
    </div>
</footer>
<div class="toast-container" id="toast-container" aria-live="polite"></div>
<script src="<?= e(base_url('assets/js/app.js')) ?>"></script>
<script src="<?= e(base_url('assets/js/api.js')) ?>"></script>
<script src="<?= e(base_url('assets/js/ui.js')) ?>"></script>
<script src="<?= e(base_url('assets/js/validate.js')) ?>"></script>
<?php foreach ((array) ($extra_js ?? []) as $js_tambahan): ?>
<script src="<?= e(base_url($js_tambahan)) ?>"></script>
<?php endforeach; ?>
</body>
</html>
