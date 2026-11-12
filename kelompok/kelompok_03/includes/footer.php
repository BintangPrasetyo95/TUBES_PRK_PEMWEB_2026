</main>

<footer class="kaki">
    <small>
        &copy; <?= date('Y') ?> Sistem Antrian Klinik
        - Kelompok 03
    </small>
</footer>

<script>
document.querySelectorAll('[data-tutup]').forEach(function (tombol) {
    tombol.addEventListener('click', function () {
        var kotak = tombol.closest('.peringatan');
        if (kotak) {
            kotak.remove();
        }
    });
});
</script>

</body>
</html>
