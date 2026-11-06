<?php
// app/Views/layouts/footer.php
// Tugas: Tutup HTML + Bootstrap JS + JS kustom per halaman.
// Diisi oleh: anggota yang mengerjakan UI layout.
?>
        </div>
    </div>

    <!-- TODO: Include JS file spesifik halaman di sini (misal: lahan.js, siklus.js, dll.) -->
    <script>
        function bukaModal(el) {
            if (!el) return;
            el.classList.add('show');
            el.style.display = 'block';
            el.removeAttribute('aria-hidden');
        }
        function tutupModal(el) {
            if (!el) return;
            el.classList.remove('show');
            el.style.display = 'none';
            el.setAttribute('aria-hidden', 'true');
        }
        document.addEventListener('click', function (e) {
            const toggle = e.target.closest('[data-bs-toggle="modal"]');
            if (toggle) {
                e.preventDefault();
                const target = document.getElementById(toggle.getAttribute('data-bs-target').replace('#', ''));
                if (target) bukaModal(target);
                return;
            }
            const dismiss = e.target.closest('[data-bs-dismiss="modal"]');
            if (dismiss) {
                e.preventDefault();
                let el = dismiss.closest('.modal');
                if (!el && dismiss.getAttribute('data-bs-target')) {
                    el = document.getElementById(dismiss.getAttribute('data-bs-target').replace('#', ''));
                }
                if (el) tutupModal(el);
            }
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal.show').forEach(tutupModal);
            }
        });
    </script>
</body>
</html>