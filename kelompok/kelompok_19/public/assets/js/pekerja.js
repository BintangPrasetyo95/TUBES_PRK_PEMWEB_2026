// public/assets/js/pekerja.js
// Tugas: AJAX Fetch CRUD Pekerja ke /api/pekerja
// Diisi oleh: Devi Novita

const API_PEKERJA = "/api/pekerja";

// 1. Ambil data pekerja
async function loadData() {
    try {
        const response = await fetch(API_PEKERJA);
        const result = await response.json();

        if (result.status === "success" || result.success) {
            const dataPekerja = result.data.data || result.data || [];
            renderTabel(dataPekerja);

            // Hitung dan tampilkan total upah jika elemennya ada
            const totalUpahEl = document.getElementById("totalUpahPekerja");
            if (totalUpahEl) {
                const totalUpah = result.total_upah || (result.data && result.data.total_upah) || 0;
                totalUpahEl.innerText = new Intl.NumberFormat("id-ID", {
                    style: "currency",
                    currency: "IDR",
                    maximumFractionDigits: 0
                }).format(totalUpah);
            }
        }
    } catch (error) {
        console.error("Gagal memuat data pekerja:", error);
    }
}

// 2. Render ke Tabel HTML
function renderTabel(data) {
    const tbody = document.querySelector("#tabelPekerja tbody");
    if (!tbody) return;

    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center">Belum ada data pekerja.</td></tr>';
        return;
    }

    let html = "";
    data.forEach((item, index) => {
        const gajiValue = item.gaji_pokok || item.upah_harian || 0;
        const gajiFormatted = new Intl.NumberFormat("id-ID", {
            style: "currency",
            currency: "IDR",
            maximumFractionDigits: 0
        }).format(gajiValue);

        html += `
            <tr>
                <td>${index + 1}</td>
                <td>${item.nama}</td>
                <td>${item.no_telepon || "-"}</td>
                <td>${item.jabatan || item.peran || "-"}</td>
                <td>${gajiFormatted}</td>
                <td>${formatTanggal(item.tanggal_bergabung)}</td>
                <td>
                    <button class="btn btn-sm btn-warning me-1" onclick="editData(${item.id})">Edit</button>
                    <button class="btn btn-sm btn-danger" onclick="hapusData(${item.id})">Hapus</button>
                </td>
            </tr>
        `;
    });
    tbody.innerHTML = html;
}

// 3. Simpan Data (POST/PUT)
async function simpanData(event) {
    event.preventDefault();

    const id = document.getElementById("pekerjaId").value;
    const formData = {
        nama: document.getElementById("pekerjaNama").value,
        no_telepon: document.getElementById("pekerjaNoTelepon").value,
        jabatan: document.getElementById("pekerjaJabatan").value,
        gaji_pokok: document.getElementById("pekerjaGajiPokok").value,
        upah_harian: document.getElementById("pekerjaGajiPokok").value,
        tanggal_bergabung: document.getElementById("pekerjaTanggalBergabung").value
    };

    const method = id ? "PUT" : "POST";
    const url = id ? `${API_PEKERJA}/${id}` : API_PEKERJA;

    try {
        const response = await fetch(url, {
            method: method,
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(formData)
        });

        const result = await response.json();
        if (result.status === "success" || result.success) {
            const modalElement = document.getElementById("modalPekerja");
            tutupModal(modalElement);

            resetFormPekerja();
            loadData();
        } else {
            alert(result.message || "Gagal menyimpan data pekerja");
        }
    } catch (error) {
        console.error("Error saat menyimpan data:", error);
    }
}

// 4. Edit Data per ID
async function editData(id) {
    try {
        const response = await fetch(`${API_PEKERJA}/${id}`);
        const result = await response.json();

        if ((result.status === "success" || result.success) && result.data) {
            const data = result.data;
            document.getElementById("pekerjaId").value = data.id;
            document.getElementById("pekerjaNama").value = data.nama || "";
            document.getElementById("pekerjaNoTelepon").value = data.no_telepon || "";
            document.getElementById("pekerjaJabatan").value = data.jabatan || data.peran || "";
            document.getElementById("pekerjaGajiPokok").value = data.gaji_pokok || data.upah_harian || 0;
            document.getElementById("pekerjaTanggalBergabung").value = data.tanggal_bergabung || "";

            document.getElementById("modalPekerjaTitle").textContent = "Edit Data Pekerja";

            const modalElement = document.getElementById("modalPekerja");
            bukaModal(modalElement);
        }
    } catch (error) {
        console.error("Gagal mengambil data pekerja untuk diedit:", error);
    }
}

// 5. Hapus Data
async function hapusData(id) {
    if (confirm("Apakah Anda yakin ingin menghapus data pekerja ini?")) {
        try {
            const response = await fetch(`${API_PEKERJA}/${id}`, {
                method: "DELETE"
            });
            const result = await response.json();

            if (result.status === "success" || result.success) {
                loadData();
            } else {
                alert(result.message || "Gagal menghapus data pekerja");
            }
        } catch (error) {
            console.error("Error saat menghapus data:", error);
        }
    }
}

// 6. Reset Form Modal
function resetFormPekerja() {
    const form = document.getElementById("formPekerja");
    if (form) form.reset();
    document.getElementById("pekerjaId").value = "";
    document.getElementById("modalPekerjaTitle").textContent = "Tambah Pekerja";
}

// 7. Format Tanggal
function formatTanggal(tanggal) {
    if (!tanggal) return "-";
    const parts = tanggal.split("-");
    if (parts.length !== 3) return tanggal;
    return `${parts[2]}-${parts[1]}-${parts[0]}`;
}

// 8. Inisialisasi Aplikasi Saat Halaman Siap
function initPekerja() {
    const form = document.getElementById("formPekerja");
    if (form) {
        form.addEventListener("submit", simpanData);
    }
    loadData();
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initPekerja);
} else {
    initPekerja();
}