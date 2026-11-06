// public/assets/js/lahan.js
// Tugas: AJAX Fetch CRUD Lahan ke /api/lahan
// Diisi oleh: Faqih.

const API_LAHAN = "/api/lahan";

// TODO: loadData() - GET semua lahan lalu render ke #tabelLahan tbody
function loadData() {
    fetch(API_LAHAN)
        .then(res => res.json())
        .then(data => {
            const tbody = document.querySelector("#tabelLahan tbody");
            const placeholder = document.getElementById("placeholderLahan");
            if (placeholder) placeholder.remove();
            tbody.innerHTML = "";
            data.forEach((item, index) => {
                const tr = document.createElement("tr");
                tr.innerHTML = `
                    <td>${index + 1}</td>
                    <td>${item.nama}</td>
                    <td>${item.lokasi}</td>
                    <td>${item.luas}</td>
                    <td>${item.jenis_tanah}</td>
                    <td>
                        <button class="btn btn-sm btn-warning me-1" onclick="editData(${item.id})">Edit</button>
                        <button class="btn btn-sm btn-danger" onclick="hapusData(${item.id})">Hapus</button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        })
        .catch(err => console.error("Gagal memuat data lahan:", err));
}

// TODO: simpanData(event) - POST/PUT berdasarkan #lahanId
function simpanData(event) {
    event.preventDefault();
    const id = document.getElementById("lahanId").value;
    const data = {
        nama: document.getElementById("lahanNama").value,
        lokasi: document.getElementById("lahanLokasi").value,
        luas: document.getElementById("lahanLuas").value,
        jenis_tanah: document.getElementById("lahanJenisTanah").value
    };
    const url = id ? `${API_LAHAN}/${id}` : API_LAHAN;
    const method = id ? "PUT" : "POST";

    fetch(url, {
        method: method,
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(data)
    })
        .then(res => res.json())
        .then(() => {
            tutupModal(document.getElementById("modalLahan"));
            loadData();
            resetFormLahan();
        })
        .catch(err => console.error("Gagal menyimpan data lahan:", err));
}

// TODO: editData(id) - ambil satu data, isi form modal
function editData(id) {
    fetch(`${API_LAHAN}/${id}`)
        .then(res => res.json())
        .then(item => {
            document.getElementById("lahanId").value = item.id;
            document.getElementById("lahanNama").value = item.nama;
            document.getElementById("lahanLokasi").value = item.lokasi;
            document.getElementById("lahanLuas").value = item.luas;
            document.getElementById("lahanJenisTanah").value = item.jenis_tanah;
            document.getElementById("modalLahanTitle").textContent = "Edit Lahan";
            bukaModal(document.getElementById("modalLahan"));
        })
        .catch(err => console.error("Gagal mengambil data lahan:", err));
}

// TODO: hapusData(id) - konfirmasi lalu DELETE
function hapusData(id) {
    if (!confirm("Yakin ingin menghapus data lahan ini?")) return;
    fetch(`${API_LAHAN}/${id}`, { method: "DELETE" })
        .then(() => loadData())
        .catch(err => console.error("Gagal menghapus data lahan:", err));
}

// TODO: resetFormLahan() - kosongkan form modal
function resetFormLahan() {
    document.getElementById("formLahan").reset();
    document.getElementById("lahanId").value = "";
    document.getElementById("modalLahanTitle").textContent = "Tambah Lahan";
}

document.getElementById("formLahan").addEventListener("submit", simpanData);

// TODO: Panggil loadData() saat halaman siap.
document.addEventListener("DOMContentLoaded", loadData);
