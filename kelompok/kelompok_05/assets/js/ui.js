// Pembantu antarmuka CEKHAK (JavaScript murni). Hanya PIC yang boleh mengubah file ini.
// Prinsip: data dari server atau pengguna ditulis lewat textContent (aman dari XSS), bukan innerHTML.
(function () {
  'use strict';

  var CEKHAK = (window.CEKHAK = window.CEKHAK || {});
  var ui = (CEKHAK.ui = {});

  /**
   * Membuat elemen dengan aman.
   * ui.el('td', { class: 'x', text: 'Halo', 'data-id': 5 }, [anak1, 'teks'])
   * Kunci khusus: class (className), text (textContent). Lainnya menjadi atribut.
   */
  ui.el = function (tag, atribut, anak) {
    var node = document.createElement(tag);
    Object.keys(atribut || {}).forEach(function (k) {
      var v = atribut[k];
      if (v === undefined || v === null || v === false) return;
      if (k === 'class') node.className = v;
      else if (k === 'text') node.textContent = v;
      else node.setAttribute(k, v === true ? '' : v);
    });
    (anak || []).forEach(function (a) {
      node.appendChild(typeof a === 'string' ? document.createTextNode(a) : a);
    });
    return node;
  };

  /** Kotak notifikasi sementara. tipe: 'success' | 'error' | 'info' */
  ui.toast = function (pesan, tipe, durasi) {
    var wadah = document.getElementById('toast-container');
    if (!wadah) return;
    var t = ui.el('div', { class: 'alert toast alert-' + (tipe || 'success'), role: 'status', text: pesan });
    wadah.appendChild(t);
    window.setTimeout(function () {
      t.remove();
    }, durasi || 3500);
  };

  /** Menunda pemanggilan fungsi sampai pengguna berhenti memicu event selama `ms` milidetik. */
  ui.debounce = function (fn, ms) {
    var timer = null;
    return function () {
      var args = arguments;
      var konteks = this;
      window.clearTimeout(timer);
      timer = window.setTimeout(function () {
        fn.apply(konteks, args);
      }, ms === undefined ? 300 : ms);
    };
  };

  /** Mengunci tombol saat permintaan berjalan agar tidak terkirim dua kali. */
  ui.sibuk = function (tombol, sibuk, teksSibuk) {
    if (!tombol) return;
    if (sibuk) {
      tombol.setAttribute('data-teks-asli', tombol.textContent);
      tombol.disabled = true;
      if (teksSibuk) tombol.textContent = teksSibuk;
    } else {
      tombol.disabled = false;
      var asli = tombol.getAttribute('data-teks-asli');
      if (asli !== null) tombol.textContent = asli;
    }
  };

  /**
   * Keadaan tampilan: 'loading' | 'error' | 'kosong'. Mengganti isi wadah dengan satu pesan.
   * Untuk menampilkan data, kosongkan wadah lalu isi sendiri.
   */
  ui.keadaan = function (wadah, jenis, pesan) {
    wadah.textContent = '';
    var anak = [];
    if (jenis === 'loading') anak.push(ui.el('span', { class: 'spinner' }));
    anak.push(pesan || '');
    wadah.appendChild(ui.el('div', { class: 'state state-' + jenis }, anak));
  };

  ui.bukaModal = function (modal) {
    modal.classList.add('active');
    modal.setAttribute('aria-hidden', 'false');
  };

  ui.tutupModal = function (modal) {
    modal.classList.remove('active');
    modal.setAttribute('aria-hidden', 'true');
  };

  // Tutup modal: tombol [data-modal-close], klik latar gelap, atau tombol Escape.
  document.addEventListener('click', function (e) {
    var modal = e.target.closest ? e.target.closest('.modal') : null;
    if (!modal) return;
    if (e.target === modal || (e.target.closest && e.target.closest('[data-modal-close]'))) {
      ui.tutupModal(modal);
    }
  });
  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    document.querySelectorAll('.modal.active').forEach(ui.tutupModal);
  });
})();
