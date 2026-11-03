// public/assets/js/panen.js
// Tugas: AJAX Fetch CRUD Panen ke /api/panen
// Diisi oleh: Putri.

const API_PANEN = "/api/panen";

// TODO: loadData() - GET semua panen lalu render ke #tabelPanen tbody
// TODO: loadSiklusOptions() - GET /api/siklus untuk isi #panenSiklusId
// TODO: simpanData(event) - POST/PUT berdasarkan #panenId
// TODO: editData(id) - ambil satu data, isi form modal
// TODO: hapusData(id) - konfirmasi lalu DELETE
// TODO: resetFormPanen() - kosongkan form modal

function resetFormPanen() {
    document.getElementById("formPanen").reset();
    document.getElementById("panenId").value = "";
    document.getElementById("modalPanenTitle").textContent = "Tambah Hasil Panen";
}

// TODO: Panggil loadData() dan loadSiklusOptions() saat halaman siap.
