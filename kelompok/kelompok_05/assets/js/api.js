// Klien API CEKHAK (Fetch API murni, tanpa library). Hanya PIC yang boleh mengubah file ini.
// Dipakai fitur lewat objek global CEKHAK:
//
//   try {
//     const hasil = await CEKHAK.api.get('modules/contoh/api/cases.php', { search: 'kos' });
//     console.log(hasil.data);                       // hasil.message juga tersedia
//   } catch (err) {
//     CEKHAK.ui.toast(err.message, 'error');         // err.status dan err.errors tersedia
//   }
//
// Metode: CEKHAK.api.get(path, params), .post(path, body), .put(path, body), .del(path)
// Path selalu relatif terhadap folder kelompok_05, contoh 'modules/contoh/api/cases.php?id=3'.
// Token CSRF dan Content-Type ditambahkan otomatis. Jangan menulis fetch() sendiri.
(function () {
  'use strict';

  var CEKHAK = (window.CEKHAK = window.CEKHAK || {});

  function meta(nama) {
    var el = document.querySelector('meta[name="' + nama + '"]');
    return el ? el.getAttribute('content') : '';
  }

  /** Alamat lengkap dari path relatif: CEKHAK.url('index.php') */
  CEKHAK.url = function (path) {
    return meta('base-url').replace(/\/$/, '') + '/' + String(path).replace(/^\//, '');
  };

  class ApiError extends Error {
    constructor(message, status, errors) {
      super(message);
      this.name = 'ApiError';
      this.status = status || 0;
      this.errors = errors || {};
    }
  }
  CEKHAK.ApiError = ApiError;

  /** Permintaan dasar. opsi: { method: 'GET', body: objek }. Mengembalikan { status, message, data }. */
  async function request(path, opsi) {
    opsi = opsi || {};
    var metode = String(opsi.method || 'GET').toUpperCase();
    var headers = { Accept: 'application/json', 'X-Requested-With': 'fetch' };
    var konfigurasi = { method: metode, headers: headers, credentials: 'same-origin' };

    if (metode !== 'GET') {
      headers['X-CSRF-Token'] = meta('csrf-token');
    }
    if (opsi.body !== undefined) {
      headers['Content-Type'] = 'application/json';
      konfigurasi.body = JSON.stringify(opsi.body);
    }

    var respons;
    try {
      respons = await fetch(CEKHAK.url(path), konfigurasi);
    } catch (e) {
      throw new ApiError('Tidak dapat terhubung ke server. Periksa koneksi atau pastikan Apache menyala.', 0);
    }

    var hasil;
    try {
      hasil = await respons.json();
    } catch (e) {
      throw new ApiError('Respons server bukan JSON yang valid (status ' + respons.status + ').', respons.status);
    }

    if (!respons.ok || !hasil || hasil.status !== 'success') {
      throw new ApiError(
        (hasil && hasil.message) || 'Terjadi kesalahan pada server.',
        respons.status,
        hasil && hasil.errors
      );
    }
    return hasil;
  }

  function buatQuery(params) {
    var q = new URLSearchParams();
    Object.keys(params || {}).forEach(function (k) {
      if (params[k] !== undefined && params[k] !== null && params[k] !== '') {
        q.append(k, params[k]);
      }
    });
    var s = q.toString();
    return s ? s : '';
  }

  CEKHAK.api = {
    request: request,
    get: function (path, params) {
      var q = buatQuery(params);
      var sambung = q ? (path.indexOf('?') === -1 ? '?' : '&') + q : '';
      return request(path + sambung, { method: 'GET' });
    },
    post: function (path, body) {
      return request(path, { method: 'POST', body: body || {} });
    },
    put: function (path, body) {
      return request(path, { method: 'PUT', body: body || {} });
    },
    del: function (path) {
      return request(path, { method: 'DELETE' });
    },
  };
})();
