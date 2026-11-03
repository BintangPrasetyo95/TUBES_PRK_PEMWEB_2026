// public/assets/js/lahan.js
// Tugas: AJAX Fetch CRUD Lahan ke /api/lahan
// Diisi oleh: Faqih.

const API_LAHAN = "/api/lahan";

// TODO: loadData() - GET semua lahan lalu render ke #tabelLahan tbody
// TODO: simpanData(event) - POST/PUT berdasarkan #lahanId
// TODO: editData(id) - ambil satu data, isi form modal
// TODO: hapusData(id) - konfirmasi lalu DELETE
// TODO: resetFormLahan() - kosongkan form modal

function resetFormLahan() {
    document.getElementById("formLahan").reset();
    document.getElementById("lahanId").value = "";
    document.getElementById("modalLahanTitle").textContent = "Tambah Lahan";
}

// TODO: Panggil loadData() saat halaman siap.
