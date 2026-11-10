</main>
<footer class="footer-rh mt-5">
  <div class="container py-4 d-flex flex-column flex-md-row justify-content-between gap-2">
    <div><strong>Ruang Hukum</strong><br><span class="small">Konsultasi hukum online yang mudah dan terpercaya.</span></div>
    <div class="small align-self-md-end">&copy; <?= date('Y') ?> Kelompok 25 &middot; Pemrograman Web</div>
  </div>
</footer>

<!-- Jendela konfirmasi bersama. Pakai di form: <form method="post" data-confirm="Yakin hapus?"> -->
<div class="modal fade" id="modalKonfirmasi" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content modal-rh text-center p-4">
      <div class="modal-ikon" id="konfirmasiIkon"><i class="bi bi-question-lg"></i></div>
      <h4 class="h5 mb-2" id="konfirmasiJudul">Konfirmasi</h4>
      <p class="text-secondary mb-4" id="konfirmasiPesan"></p>
      <div class="d-flex justify-content-center gap-2">
        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-gold px-4" id="konfirmasiYa">Ya, lanjutkan</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// 1) Notifikasi (flash): hilang sendiri setelah 5 detik, kecuali pesan error.
document.querySelectorAll('.toast-rh').forEach(function (el) {
  function tutup() { el.classList.add('keluar'); setTimeout(function () { el.remove(); }, 350); }
  el.querySelector('.btn-close').addEventListener('click', tutup);
  if (!el.classList.contains('toast-danger')) { setTimeout(tutup, 5000); }
});

// 2) Konfirmasi: form yang punya atribut data-confirm tampil di jendela tengah layar.
//    Atribut opsional: data-confirm-judul, data-confirm-ok, data-confirm-tipe="danger"
(function () {
  var modalEl = document.getElementById('modalKonfirmasi');
  var modal = new bootstrap.Modal(modalEl);
  var tujuan = null;

  document.addEventListener('submit', function (ev) {
    var f = ev.target;
    if (!f.hasAttribute || !f.hasAttribute('data-confirm')) { return; }
    ev.preventDefault();
    tujuan = f;
    var bahaya = f.getAttribute('data-confirm-tipe') === 'danger';
    document.getElementById('konfirmasiJudul').textContent = f.getAttribute('data-confirm-judul') || 'Konfirmasi';
    document.getElementById('konfirmasiPesan').textContent = f.getAttribute('data-confirm');
    var ya = document.getElementById('konfirmasiYa');
    ya.textContent = f.getAttribute('data-confirm-ok') || 'Ya, lanjutkan';
    ya.className = 'btn px-4 ' + (bahaya ? 'btn-danger' : 'btn-gold');
    document.getElementById('konfirmasiIkon').className = 'modal-ikon' + (bahaya ? ' bahaya' : '');
    modal.show();
  });

  document.getElementById('konfirmasiYa').addEventListener('click', function () {
    if (!tujuan) { return; }
    var f = tujuan; tujuan = null;
    modal.hide();
    f.submit(); // submit() langsung kirim form, tanpa memicu jendela konfirmasi lagi
  });
})();

// 3) Validasi formulir: pesan berbahasa Indonesia tepat di bawah kolom yang salah
//    (menggantikan balon bawaan browser). Berlaku otomatis untuk semua <form>.
//    Pesan wajib-diisi bisa diganti per kolom: <input required data-pesan-wajib="Tulis emailmu.">
(function () {
  function labelDari(el) {
    var l = el.id ? document.querySelector('label[for="' + el.id + '"]') : null;
    return l ? l.textContent.replace('*', '').trim() : '';
  }

  function galat(el) {
    if (!el.willValidate) { return ''; }
    var v = el.validity, tipe = (el.type || '').toLowerCase(), label = labelDari(el);
    var hanyaSpasi = el.required && /^(text|search|tel|url|email|textarea)$/.test(tipe) && el.value.trim() === '';
    if (v.valueMissing || hanyaSpasi) {
      if (el.getAttribute('data-pesan-wajib')) { return el.getAttribute('data-pesan-wajib'); }
      if (el.tagName === 'SELECT') { return 'Silakan pilih ' + (label ? label.toLowerCase() : 'salah satu opsi') + '.'; }
      if (tipe === 'checkbox') { return 'Centang kotak ini untuk melanjutkan.'; }
      if (tipe === 'radio') { return 'Pilih salah satu opsi.'; }
      if (tipe === 'file') { return 'Pilih file terlebih dahulu.'; }
      return (label || 'Kolom ini') + ' wajib diisi.';
    }
    if (v.typeMismatch) { return tipe === 'email' ? 'Format email tidak valid. Contoh: nama@email.com' : 'Format isian tidak valid.'; }
    if (v.tooShort) { return (label || 'Isian') + ' minimal ' + el.minLength + ' karakter.'; }
    if (v.tooLong) { return (label || 'Isian') + ' maksimal ' + el.maxLength + ' karakter.'; }
    if (v.patternMismatch) { return el.title || 'Format isian tidak sesuai.'; }
    if (v.rangeUnderflow) { return 'Nilai minimal ' + el.min + '.'; }
    if (v.rangeOverflow) { return 'Nilai maksimal ' + el.max + '.'; }
    if (v.badInput || v.stepMismatch) { return 'Nilai yang dimasukkan tidak valid.'; }
    if (v.customError) { return el.validationMessage; }
    return '';
  }

  function hapus(el) {
    el.classList.remove('is-invalid');
    el.removeAttribute('aria-invalid');
    if (el._pesan) { el._pesan.remove(); el._pesan = null; }
  }

  function tampil(el, pesan) {
    hapus(el);
    el.classList.add('is-invalid');
    el.setAttribute('aria-invalid', 'true');
    var d = document.createElement('div');
    d.className = 'pesan-validasi';
    d.setAttribute('role', 'alert');
    d.innerHTML = '<i class="bi bi-exclamation-circle-fill"></i><span></span>';
    d.lastChild.textContent = pesan;
    (el.closest('.input-group') || el).insertAdjacentElement('afterend', d);
    el._pesan = d;
    el.classList.remove('field-getar'); void el.offsetWidth; el.classList.add('field-getar');
  }

  // Matikan balon bawaan browser; pemeriksaannya kita lakukan sendiri di bawah.
  document.querySelectorAll('form').forEach(function (f) { f.noValidate = true; });

  // Fase capture: jalan lebih dulu daripada jendela konfirmasi, jadi form yang
  // belum lengkap tidak pernah memunculkan jendela konfirmasi.
  document.addEventListener('submit', function (ev) {
    var f = ev.target, pertama = null;
    if (!f.elements) { return; }
    Array.prototype.forEach.call(f.elements, function (el) {
      var m = galat(el);
      if (m) { tampil(el, m); if (!pertama) { pertama = el; } } else { hapus(el); }
    });
    if (pertama) {
      ev.preventDefault();
      ev.stopPropagation();
      pertama.focus({ preventScroll: true });
      pertama.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  }, true);

  // Pesan hilang sendiri begitu isiannya sudah benar.
  function ulang(ev) {
    var el = ev.target;
    if (!el._pesan) { return; }
    var m = galat(el);
    if (m) { el._pesan.lastChild.textContent = m; } else { hapus(el); }
  }
  document.addEventListener('input', ulang);
  document.addEventListener('change', ulang);
})();
</script>
</body>
</html>
