
// public/assets/js/siklus.js
// Tugas: AJAX Fetch CRUD Siklus ke /api/siklus
// Diisi oleh: Hengky.

const API_SIKLUS = "/api/siklus";

// TODO: loadData() - GET semua siklus lalu render ke #tabelSiklus tbody
async function loadData() {
    const tbody = document.querySelector("#tabelSiklus tbody");

    tbody.innerHTML = `
        <tr>
            <td colspan="7" class="text-center">Memuat data...</td>
        </tr>
    `;

    try {
        const response = await fetch(API_SIKLUS);
        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(result.message || "Gagal mengambil data siklus.");
        }

        const data = result.data || [];

        tbody.innerHTML = "";

        if (data.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center">
                        Belum ada data siklus tanam.
                    </td>
                </tr>
            `;
            return;
        }

        data.forEach((item, index) => {
            const tr = document.createElement("tr");

            const statusClass = {
                "Aktif": "bg-success",
                "Panen": "bg-warning text-dark",
                "Selesai": "bg-primary",
                "Gagal": "bg-danger"
            };

            const statusBadge = document.createElement("span");
            statusBadge.className = `badge ${statusClass[item.status] || "bg-secondary"}`;
            statusBadge.textContent = item.status;

            const values = [
                index + 1,
                item.nama_tanaman,
                `${item.nama_lahan || "-"} (${item.lokasi_lahan || "-"})`,
                formatTanggal(item.tanggal_tanam),
                formatTanggal(item.tanggal_panen_estimasi)
            ];

            values.forEach(value => {
                const td = document.createElement("td");
                td.textContent = value ?? "-";
                tr.appendChild(td);
            });

            const statusTd = document.createElement("td");
            statusTd.appendChild(statusBadge);
            tr.appendChild(statusTd);

            const actionTd = document.createElement("td");

            const editButton = document.createElement("button");
            editButton.type = "button";
            editButton.className = "btn btn-warning btn-sm me-1";
            editButton.textContent = "Edit";
            editButton.addEventListener("click", () => editData(item.id));

            const deleteButton = document.createElement("button");
            deleteButton.type = "button";
            deleteButton.className = "btn btn-danger btn-sm";
            deleteButton.textContent = "Hapus";
            deleteButton.addEventListener("click", () => hapusData(item.id));

            actionTd.appendChild(editButton);
            actionTd.appendChild(deleteButton);

            tr.appendChild(actionTd);
            tbody.appendChild(tr);
        });

    } catch (error) {
        console.error("Error loadData:", error);

        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center text-danger">
                    Gagal memuat data siklus.
                </td>
            </tr>
        `;
    }
}


// TODO: loadLahanOptions() - GET /api/lahan untuk isi #siklusLahanId
async function loadLahanOptions() {
    const select = document.getElementById("siklusLahanId");

    try {
        const response = await fetch("/api/lahan");
        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(result.message || "Gagal mengambil data lahan.");
        }

        const data = result.data || [];

        select.innerHTML = '<option value="">Pilih Lahan...</option>';

        data.forEach(lahan => {
            const option = document.createElement("option");

            option.value = lahan.id;
            option.textContent = `${lahan.nama} - ${lahan.lokasi}`;

            select.appendChild(option);
        });

    } catch (error) {
        console.error("Error loadLahanOptions:", error);

        select.innerHTML = '<option value="">Gagal memuat data lahan</option>';
    }
}


// TODO: simpanData(event) - POST/PUT berdasarkan #siklusId
async function simpanData(event) {
    event.preventDefault();

    const id = document.getElementById("siklusId").value;

    const data = {
        nama_tanaman: document.getElementById("siklusNamaTanaman").value.trim(),
        lahan_id: document.getElementById("siklusLahanId").value,
        tanggal_tanam: document.getElementById("siklusTanggalTanam").value,
        tanggal_panen_estimasi: document.getElementById("siklusTanggalPanenEstimasi").value || null,
        status: document.getElementById("siklusStatus").value
    };

    if (
        !data.nama_tanaman ||
        !data.lahan_id ||
        !data.tanggal_tanam
    ) {
        alert("Lengkapi data siklus tanam terlebih dahulu.");
        return;
    }

    if (
        data.tanggal_panen_estimasi &&
        data.tanggal_panen_estimasi < data.tanggal_tanam
    ) {
        alert("Tanggal estimasi panen tidak boleh sebelum tanggal tanam.");
        return;
    }

    const url = id ? `${API_SIKLUS}/${id}` : API_SIKLUS;
    const method = id ? "PUT" : "POST";

    const submitButton = document.querySelector(
        "#formSiklus button[type='submit']"
    );

    submitButton.disabled = true;
    submitButton.textContent = "Menyimpan...";

    try {
        const response = await fetch(url, {
            method: method,
            headers: {
                "Content-Type": "application/json",
                "Accept": "application/json"
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(result.message || "Gagal menyimpan data.");
        }

        alert(result.message);

        const modalElement = document.getElementById("modalSiklus");
        const modal = bootstrap.Modal.getInstance(modalElement);

        if (modal) {
            modal.hide();
        }

        resetFormSiklus();
        await loadData();

    } catch (error) {
        console.error("Error simpanData:", error);
        alert(error.message);

    } finally {
        submitButton.disabled = false;
        submitButton.textContent = "Simpan";
    }
}


// TODO: editData(id) - ambil satu data, isi form modal
async function editData(id) {
    try {
        const response = await fetch(`${API_SIKLUS}/${id}`);
        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(result.message || "Gagal mengambil data siklus.");
        }

        const data = result.data;

        document.getElementById("siklusId").value = data.id;
        document.getElementById("siklusNamaTanaman").value = data.nama_tanaman;
        document.getElementById("siklusLahanId").value = data.lahan_id;
        document.getElementById("siklusTanggalTanam").value = data.tanggal_tanam;
        document.getElementById("siklusTanggalPanenEstimasi").value =
            data.tanggal_panen_estimasi || "";
        document.getElementById("siklusStatus").value = data.status;

        document.getElementById("modalSiklusTitle").textContent = "Edit Siklus";

        const modalElement = document.getElementById("modalSiklus");
        const modal = bootstrap.Modal.getOrCreateInstance(modalElement);

        modal.show();

    } catch (error) {
        console.error("Error editData:", error);
        alert(error.message);
    }
}


// TODO: hapusData(id) - konfirmasi lalu DELETE
async function hapusData(id) {
    const konfirmasi = confirm(
        "Apakah kamu yakin ingin menghapus data siklus ini?"
    );

    if (!konfirmasi) {
        return;
    }

    try {
        const response = await fetch(`${API_SIKLUS}/${id}`, {
            method: "DELETE",
            headers: {
                "Accept": "application/json"
            }
        });

        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(result.message || "Gagal menghapus data siklus.");
        }

        alert(result.message);

        await loadData();

    } catch (error) {
        console.error("Error hapusData:", error);
        alert(error.message);
    }
}


// TODO: resetFormSiklus() - kosongkan form modal
function resetFormSiklus() {
    document.getElementById("formSiklus").reset();
    document.getElementById("siklusId").value = "";
    document.getElementById("modalSiklusTitle").textContent = "Tambah Siklus";
}


// Fungsi untuk mengubah format tanggal
function formatTanggal(tanggal) {
    if (!tanggal) {
        return "-";
    }

    const parts = tanggal.split("-");

    if (parts.length !== 3) {
        return tanggal;
    }

    return `${parts[2]}-${parts[1]}-${parts[0]}`;
}


// TODO: Panggil loadData() dan loadLahanOptions() saat halaman siap.
function initSiklus() {
    const form = document.getElementById("formSiklus");

    if (!form) {
        return;
    }

    form.addEventListener("submit", simpanData);

    loadData();
    loadLahanOptions();
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initSiklus);
} else {
    initSiklus();
}
