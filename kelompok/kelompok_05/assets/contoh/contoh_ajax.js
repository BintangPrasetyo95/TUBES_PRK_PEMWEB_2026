// ============================================================================
// JS CONTOH untuk modules/contoh/contoh_ajax.php. Tiru pola ini untuk fitur kamu.
// Memakai CEKHAK.api / CEKHAK.ui / CEKHAK.validate dari assets/js/ (tanpa library lain).
// Pola: state (array) -> render() -> DOM. Setiap aksi mengubah state di server dulu (pessimistic update),
// lalu memuat ulang data dan merender ulang.
// ============================================================================
(function () {
  'use strict';

  var api = CEKHAK.api;
  var ui = CEKHAK.ui;
  var V = CEKHAK.validate;
  var ENDPOINT = 'modules/contoh/api/cases.php';

  var BADGE = {
    draft: 'muted',
    belum_lengkap: 'bad',
    siap_ditinjau: 'warn',
    sedang_ditinjau: 'info',
    selesai: 'ok',
  };
  var ATURAN = { judul: ['wajib', 'max:200'], document_type_id: ['wajib'] };

  var tbody = document.getElementById('tabel-body-cases');
  var wadahStatus = document.getElementById('status-cases');
  var totalEl = document.getElementById('total-cases');
  var inputCari = document.getElementById('input-search');
  var formTambah = document.getElementById('form-tambah-case');
  var formEdit = document.getElementById('form-edit-case');
  var modalEdit = document.getElementById('modal-edit');

  // STATE: satu-satunya sumber kebenaran untuk isi tabel.
  var dataCases = [];

  function labelStatus(s) {
    var teks = String(s).replace(/_/g, ' ');
    return teks.charAt(0).toUpperCase() + teks.slice(1);
  }

  // RENDER: membangun ulang isi tabel dari state. Semua teks lewat textContent (aman dari XSS).
  function render() {
    tbody.textContent = '';
    totalEl.textContent = String(dataCases.length);
    if (dataCases.length === 0) {
      ui.keadaan(wadahStatus, 'kosong', 'Tidak ada data yang cocok.');
      return;
    }
    wadahStatus.textContent = '';
    var fragmen = document.createDocumentFragment();
    dataCases.forEach(function (c) {
      fragmen.appendChild(
        ui.el('tr', {}, [
          ui.el('td', { text: c.judul }),
          ui.el('td', { text: c.jenis }),
          ui.el('td', {}, [ui.el('span', { class: 'badge badge-' + (BADGE[c.status] || 'muted'), text: labelStatus(c.status) })]),
          ui.el('td', { text: c.created_at }),
          ui.el('td', {}, [
            ui.el('button', { type: 'button', class: 'btn btn-small', text: 'Edit', 'data-aksi': 'edit', 'data-id': c.id }),
            ' ',
            ui.el('button', { type: 'button', class: 'btn btn-small btn-danger', text: 'Hapus', 'data-aksi': 'hapus', 'data-id': c.id }),
          ]),
        ])
      );
    });
    tbody.appendChild(fragmen);
  }

  // MUAT DATA: tiga keadaan antarmuka (loading, sukses, error).
  async function muatData() {
    tbody.textContent = '';
    ui.keadaan(wadahStatus, 'loading', 'Memuat data dari server...');
    try {
      var hasil = await api.get(ENDPOINT, { search: inputCari.value.trim() });
      dataCases = hasil.data;
      render();
    } catch (err) {
      ui.keadaan(wadahStatus, 'error', err.message);
      ui.toast(err.message, 'error');
    }
  }

  // TAMBAH (POST): validasi klien -> kirim -> toast -> muat ulang.
  async function tambah(e) {
    e.preventDefault();
    var galat = V.form(formTambah, ATURAN);
    V.tampilkan(formTambah, galat);
    if (Object.keys(galat).length > 0) return;

    var tombol = formTambah.querySelector('button[type="submit"]');
    ui.sibuk(tombol, true, 'Menyimpan...');
    try {
      var hasil = await api.post(ENDPOINT, {
        judul: formTambah.elements.judul.value.trim(),
        document_type_id: Number(formTambah.elements.document_type_id.value),
      });
      ui.toast(hasil.message, 'success');
      formTambah.reset();
      await muatData();
    } catch (err) {
      V.tampilkan(formTambah, err.errors); // galat dari validasi server ditampilkan di bawah kolom
      ui.toast(err.message, 'error');
    } finally {
      ui.sibuk(tombol, false);
    }
  }

  // EDIT (GET by id -> isi form modal -> buka modal).
  async function bukaEdit(id) {
    try {
      var hasil = await api.get(ENDPOINT, { id: id });
      formEdit.elements.id.value = hasil.data.id;
      formEdit.elements.judul.value = hasil.data.judul;
      formEdit.elements.document_type_id.value = hasil.data.document_type_id;
      V.tampilkan(formEdit, {});
      ui.bukaModal(modalEdit);
    } catch (err) {
      ui.toast(err.message, 'error');
    }
  }

  // SIMPAN EDIT (PUT).
  async function simpanEdit(e) {
    e.preventDefault();
    var galat = V.form(formEdit, ATURAN);
    V.tampilkan(formEdit, galat);
    if (Object.keys(galat).length > 0) return;

    var tombol = formEdit.querySelector('button[type="submit"]');
    ui.sibuk(tombol, true, 'Menyimpan...');
    try {
      var hasil = await api.put(ENDPOINT + '?id=' + encodeURIComponent(formEdit.elements.id.value), {
        judul: formEdit.elements.judul.value.trim(),
        document_type_id: Number(formEdit.elements.document_type_id.value),
      });
      ui.tutupModal(modalEdit);
      ui.toast(hasil.message, 'success');
      await muatData();
    } catch (err) {
      V.tampilkan(formEdit, err.errors);
      ui.toast(err.message, 'error');
    } finally {
      ui.sibuk(tombol, false);
    }
  }

  // HAPUS (DELETE) dengan konfirmasi.
  async function hapus(id) {
    if (!window.confirm('Hapus pemeriksaan ini?')) return;
    try {
      var hasil = await api.del(ENDPOINT + '?id=' + encodeURIComponent(id));
      ui.toast(hasil.message, 'success');
      await muatData();
    } catch (err) {
      ui.toast(err.message, 'error');
    }
  }

  // EVENT: delegasi klik pada tabel (satu listener untuk semua tombol baris), pencarian dengan debounce 300 ms.
  tbody.addEventListener('click', function (e) {
    var tombol = e.target.closest('button[data-aksi]');
    if (!tombol) return;
    var id = Number(tombol.getAttribute('data-id'));
    if (tombol.getAttribute('data-aksi') === 'edit') bukaEdit(id);
    if (tombol.getAttribute('data-aksi') === 'hapus') hapus(id);
  });
  formTambah.addEventListener('submit', tambah);
  formEdit.addEventListener('submit', simpanEdit);
  inputCari.addEventListener('input', ui.debounce(muatData, 300));

  document.addEventListener('DOMContentLoaded', muatData);
})();
