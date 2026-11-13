// Skrip modul Pemeriksaan (Peran C). JavaScript murni, memakai CEKHAK.validate dan CEKHAK.ui bersama.
// Validasi klien hanya kenyamanan; server tetap memeriksa ulang (modules/pemeriksaan/_lib.php).
(function () {
  'use strict';
  var BATAS_BYTE = 5 * 1024 * 1024;
  var EKSTENSI = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'doc', 'docx'];

  // ---- Konfirmasi sebelum tindakan berbahaya (form dengan data-konfirmasi) ----
  document.addEventListener('submit', function (e) {
    var pesan = e.target.getAttribute && e.target.getAttribute('data-konfirmasi');
    if (pesan && !window.confirm(pesan)) e.preventDefault();
  });

  // ---- Halaman buat pemeriksaan: info jenis dokumen yang dipilih ----
  var pilih = document.querySelector('[data-jenis-select]');
  var info = document.querySelector('[data-jenis-info]');
  if (pilih && info) {
    var tampil = function () {
      var opt = pilih.options[pilih.selectedIndex];
      if (!opt || !opt.value) { info.hidden = true; return; }
      var teks = [];
      if (opt.getAttribute('data-deskripsi')) teks.push(opt.getAttribute('data-deskripsi'));
      teks.push(opt.getAttribute('data-total') + ' persyaratan, ' + opt.getAttribute('data-wajib') + ' di antaranya wajib.');
      info.textContent = teks.join(' ');
      info.hidden = false;
    };
    pilih.addEventListener('change', tampil);
    tampil();
  }

  // ---- Halaman checklist ----
  var form = document.querySelector('[data-case-form]');
  if (!form) return;
  var V = window.CEKHAK && window.CEKHAK.validate;
  var items = Array.prototype.slice.call(document.querySelectorAll('[data-persyaratan]'));

  function nilaiItem(item) {
    var tipe = item.getAttribute('data-tipe');
    if (tipe === 'file') return item.getAttribute('data-terisi') === '1';
    var input = item.querySelector('input:not([type=file]), textarea');
    if (!input) return false;
    if (tipe === 'checkbox') return input.checked;
    return input.value.trim() !== '';
  }

  function periksaItem(item) {
    var tipe = item.getAttribute('data-tipe');
    var input = item.querySelector('input:not([type=file]):not([type=checkbox]), textarea');
    var galat = '';
    if (input && V) {
      var aturan = item.getAttribute('data-aturan') || (tipe === 'number' ? 'angka' : tipe === 'date' ? 'tanggal' : '');
      galat = V.cek(input.value, aturan ? [aturan] : []);
    }
    item.querySelectorAll('[data-js-error]').forEach(function (n) { n.remove(); });
    item.classList.toggle('has-error', galat !== '');
    if (galat) {
      var d = document.createElement('div');
      d.className = 'field-error';
      d.setAttribute('data-js-error', '');
      d.textContent = galat;
      item.appendChild(d);
    }
    return galat;
  }

  function perbaruiRingkasan() {
    var wajib = 0, terpenuhi = 0, belum = [];
    items.forEach(function (item) {
      var isi = nilaiItem(item);
      var penanda = item.querySelector('[data-penanda]');
      var wajibItem = item.getAttribute('data-wajib') === '1';
      if (wajibItem) { wajib++; if (isi) terpenuhi++; else belum.push(item); }
      if (penanda) {
        penanda.className = 'penanda penanda-' + (isi ? 'terpenuhi' : wajibItem ? 'belum' : 'opsional');
        penanda.textContent = isi ? '\u2713' : '\u25CB';
      }
    });
    var persen = wajib ? Math.floor(terpenuhi * 100 / wajib) : 100;
    var teks = document.querySelector('[data-progress-teks]');
    var bar = document.querySelector('[data-progress-bar]');
    if (teks) teks.textContent = terpenuhi + ' dari ' + wajib + ' terpenuhi (' + persen + '%)';
    if (bar) { bar.style.width = persen + '%'; bar.parentNode.setAttribute('aria-valuenow', persen); }
    var wadah = document.querySelector('[data-belum-wadah]');
    var daftar = document.querySelector('[data-belum-daftar]');
    if (wadah && daftar) {
      daftar.textContent = '';
      belum.forEach(function (item) {
        var li = document.createElement('li');
        var a = document.createElement('a');
        a.href = '#' + item.id;
        a.textContent = item.getAttribute('data-label');
        li.appendChild(a);
        daftar.appendChild(li);
      });
      wadah.hidden = belum.length === 0;
    }
  }

  var sudahDiubah = false;
  form.addEventListener('input', function (e) { sudahDiubah = true; perbaruiRingkasan(); });
  form.addEventListener('change', function () { sudahDiubah = true; perbaruiRingkasan(); });
  form.addEventListener('focusout', function (e) {
    var item = e.target.closest ? e.target.closest('[data-persyaratan]') : null;
    if (item) periksaItem(item);
  });
  form.addEventListener('submit', function (e) {
    var pertama = null;
    items.forEach(function (item) { if (periksaItem(item) && !pertama) pertama = item; });
    if (pertama) {
      e.preventDefault();
      pertama.scrollIntoView({ behavior: 'smooth', block: 'center' });
      if (window.CEKHAK && window.CEKHAK.ui) window.CEKHAK.ui.toast('Periksa kembali isian yang bertanda merah.', 'error');
    } else {
      sudahDiubah = false;
    }
  });
  window.addEventListener('beforeunload', function (e) {
    if (sudahDiubah) { e.preventDefault(); e.returnValue = ''; }
  });

  // ---- Unggah berkas: cek ukuran/ekstensi di klien, lalu bawa isian form ikut tersimpan ----
  document.querySelectorAll('[data-berkas-input]').forEach(function (input) {
    input.addEventListener('change', function () {
      var f = input.files && input.files[0];
      if (!f) return;
      var ext = f.name.split('.').pop().toLowerCase();
      var pesan = '';
      if (f.size > BATAS_BYTE) pesan = 'Ukuran berkas melebihi 5 MB.';
      else if (EKSTENSI.indexOf(ext) === -1) pesan = 'Format harus PDF, JPG, PNG, WebP, atau Word.';
      if (pesan) {
        input.value = '';
        if (window.CEKHAK && window.CEKHAK.ui) window.CEKHAK.ui.toast(pesan, 'error');
      }
    });
  });
  document.querySelectorAll('[data-upload-form]').forEach(function (uf) {
    uf.addEventListener('submit', function () {
      // Salin isian form utama sebagai input tersembunyi agar tidak hilang saat halaman dimuat ulang.
      uf.querySelectorAll('input[data-salinan]').forEach(function (n) { n.remove(); });
      new FormData(form).forEach(function (v, k) {
        if (k.indexOf('field[') !== 0) return;
        var h = document.createElement('input');
        h.type = 'hidden'; h.name = k; h.value = v; h.setAttribute('data-salinan', '');
        uf.appendChild(h);
      });
      sudahDiubah = false;
    });
  });

  perbaruiRingkasan();
})();
