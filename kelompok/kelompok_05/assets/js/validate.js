// Validasi sisi klien CEKHAK. Hanya PIC yang boleh mengubah file ini.
// Aturan format di sini WAJIB sama dengan includes/validasi.php (cek_format) di sisi server.
// Validasi klien hanya kenyamanan pengguna. Server tetap memvalidasi ulang.
(function () {
  'use strict';

  var CEKHAK = (window.CEKHAK = window.CEKHAK || {});
  var V = (CEKHAK.validate = {});

  V.pesan = {
    wajib: 'Wajib diisi.',
    tanggal: 'Tanggal harus berformat YYYY-MM-DD dan valid.',
    angka: 'Harus berupa angka.',
    nomor: 'Nomor tidak valid (8 sampai 15 digit).',
    email: 'Format email tidak valid.',
  };

  V.tanggal = function (nilai) {
    var m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(nilai);
    if (!m) return false;
    var y = Number(m[1]);
    var bln = Number(m[2]);
    var tgl = Number(m[3]);
    if (y < 1900 || y > 2100) return false;
    var d = new Date(Date.UTC(y, bln - 1, tgl));
    return d.getUTCFullYear() === y && d.getUTCMonth() === bln - 1 && d.getUTCDate() === tgl;
  };

  V.angka = function (nilai) {
    return /^-?[0-9]+(\.[0-9]+)?$/.test(nilai);
  };

  V.nomor = function (nilai) {
    if (!/^[0-9 ()+-]+$/.test(nilai)) return false;
    return /^[0-9]{8,15}$/.test(nilai.replace(/[ ()+-]/g, ''));
  };

  V.email = function (nilai) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(nilai);
  };

  /**
   * Memeriksa satu nilai terhadap daftar aturan, misal ['wajib', 'email', 'max:150'].
   * Mengembalikan pesan kesalahan pertama, atau string kosong bila lolos.
   * Nilai kosong tanpa aturan 'wajib' dianggap lolos.
   */
  V.cek = function (nilai, aturan) {
    nilai = String(nilai === undefined || nilai === null ? '' : nilai).trim();
    aturan = aturan || [];
    if (nilai === '') {
      return aturan.indexOf('wajib') !== -1 ? V.pesan.wajib : '';
    }
    for (var i = 0; i < aturan.length; i++) {
      var a = aturan[i];
      if (a === 'wajib') continue;
      if (a.indexOf('max:') === 0) {
        var batas = Number(a.slice(4));
        if (nilai.length > batas) return 'Maksimal ' + batas + ' karakter.';
      } else if (typeof V[a] === 'function' && !V[a](nilai)) {
        return V.pesan[a] || 'Format tidak valid.';
      }
    }
    return '';
  };

  /**
   * Memeriksa seluruh isian form. aturan: { namaInput: ['wajib', 'max:200'], ... }
   * Mengembalikan objek { namaInput: 'pesan' } (kosong bila semua lolos).
   */
  V.form = function (form, aturan) {
    var galat = {};
    Object.keys(aturan).forEach(function (nama) {
      var input = form.elements[nama];
      if (!input) return;
      var pesan = V.cek(input.value, aturan[nama]);
      if (pesan) galat[nama] = pesan;
    });
    return galat;
  };

  /** Menampilkan pesan galat di bawah masing-masing input (di dalam .form-group), menghapus yang lama. */
  V.tampilkan = function (form, galat) {
    form.querySelectorAll('[data-js-error]').forEach(function (n) {
      n.remove();
    });
    form.querySelectorAll('.has-error').forEach(function (n) {
      n.classList.remove('has-error');
    });
    Object.keys(galat || {}).forEach(function (nama) {
      var input = form.elements[nama];
      var grup = input && input.closest ? input.closest('.form-group') : null;
      if (!grup) return;
      grup.classList.add('has-error');
      var d = document.createElement('div');
      d.className = 'field-error';
      d.setAttribute('data-js-error', '');
      d.textContent = galat[nama];
      grup.appendChild(d);
    });
  };
})();
