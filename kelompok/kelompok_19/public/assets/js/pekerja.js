// public/assets/js/pekerja.js
// Tugas: AJAX Fetch CRUD Pekerja ke /api/pekerja
// Diisi oleh: Devi.

const API_PEKERJA = "/api/pekerja";

// TODO: loadData() - GET semua pekerja lalu render ke #tabelPekerja tbody
// TODO: simpanData(event) - POST/PUT berdasarkan #pekerjaId
// TODO: editData(id) - ambil satu data, isi form modal
// TODO: hapusData(id) - konfirmasi lalu DELETE
// TODO: resetFormPekerja() - kosongkan form modal

function resetFormPekerja() {
    document.getElementById("formPekerja").reset();
    document.getElementById("pekerjaId").value = "";
    document.getElementById("modalPekerjaTitle").textContent = "Tambah Pekerja";
}

// TODO: Panggil loadData() saat halaman siap.
