// public/assets/js/panen.js
// Tugas: AJAX Fetch CRUD Panen ke /api/panen
// Diisi oleh: Putri.

const API_PANEN = "/api/panen";
const fmt = new Intl.NumberFormat("id-ID", { maximumFractionDigits: 2 });

// ---------- Helper ----------
function el(id) {
    return document.getElementById(id);
}

function escapeHtml(value) {
    const div = document.createElement("div");
    div.textContent = value ?? "";
    return div.innerHTML;
}

function hariIni() {
    return new Date().toLocaleDateString("en-CA"); // format YYYY-MM-DD (waktu lokal)
}

function formatTanggal(tanggal) {
    const d = new Date(tanggal + "T00:00:00");
    return d.toLocaleDateString("id-ID", { day: "2-digit", month: "short", year: "numeric" });
}

async function request(url, options = {}) {
    const res = await fetch(url, {
        headers: { "Content-Type": "application/json" },
        ...options,
    });

    let json;
    try {
        json = await res.json();
    } catch (e) {
        throw new Error("Respons server tidak valid.");
    }

    if (!res.ok || json.status !== "success") {
        throw new Error(json.message || json.error || "Terjadi kesalahan.");
    }
    return json;
}

function urlDenganFilter(path = "") {
    const params = new URLSearchParams();
    const dari = el("filterDari").value;
    const sampai = el("filterSampai").value;
    if (dari) params.set("dari", dari);
    if (sampai) params.set("sampai", sampai);
    const qs = params.toString();
    return API_PANEN + path + (qs ? "?" + qs : "");
}

function tampilError(pesan) {
    const box = el("panenError");
    box.textContent = pesan || "";
    box.hidden = !pesan;
}

// ---------- Render ----------
function renderTabel(rows) {
    const tbody = el("tabelPanenBody");
    if (!rows.length) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center">Belum ada data panen.</td></tr>';
        return;
    }

    tbody.innerHTML = rows
        .map(
            (r, i) => `
        <tr>
            <td>${i + 1}</td>
            <td>${escapeHtml(r.nama_tanaman)} - ${escapeHtml(r.nama_lahan)}</td>
            <td>${escapeHtml(formatTanggal(r.tanggal_panen))}</td>
            <td>${fmt.format(Number(r.jumlah_kg))}</td>
            <td>${escapeHtml(r.kualitas)}</td>
            <td>${escapeHtml(r.catatan || "-")}</td>
            <td class="no-print">
                <button type="button" class="btn btn-sm btn-warning" data-aksi="edit" data-id="${Number(r.id)}">Edit</button>
                <button type="button" class="btn btn-sm btn-danger" data-aksi="hapus" data-id="${Number(r.id)}">Hapus</button>
            </td>
        </tr>`
        )
        .join("");
}

function renderRingkasan(r) {
    const totalKg = Number(r.total_kg);
    el("totalPanenKg").textContent = fmt.format(totalKg) + " kg";
    el("totalPanenTon").textContent = fmt.format(totalKg / 1000) + " ton";
    el("jumlahCatatan").textContent = r.jumlah_catatan;

    const tbody = el("tabelRekapBody");
    if (!r.per_tanaman.length) {
        tbody.innerHTML = '<tr><td colspan="3" class="text-center">Belum ada data.</td></tr>';
        return;
    }

    tbody.innerHTML = r.per_tanaman
        .map(
            (t) => `
        <tr>
            <td>${escapeHtml(t.nama_tanaman)}</td>
            <td>${Number(t.jumlah_catatan)}</td>
            <td>${fmt.format(Number(t.total_kg))}</td>
        </tr>`
        )
        .join("");
}

// ---------- Load data ----------
async function loadData() {
    try {
        const json = await request(urlDenganFilter());
        renderTabel(json.data);
        renderRingkasan(json.ringkasan);
    } catch (err) {
        el("tabelPanenBody").innerHTML =
            '<tr><td colspan="7" class="text-center text-danger">' + escapeHtml(err.message) + "</td></tr>";
    }
}

async function loadSiklusOptions() {
    try {
        const json = await request(API_PANEN + "?mode=siklus");
        const select = el("panenSiklusId");
        select.innerHTML =
            '<option value="">Pilih Siklus...</option>' +
            json.data
                .map(
                    (s) =>
                        `<option value="${Number(s.id)}">${escapeHtml(s.nama_tanaman)} - ${escapeHtml(s.nama_lahan)} (${escapeHtml(s.status)})</option>`
                )
                .join("");
    } catch (err) {
        tampilError("Gagal memuat daftar siklus: " + err.message);
    }
}

// ---------- Form / Modal (dialog native) ----------
function resetFormPanen() {
    el("formPanen").reset();
    el("panenId").value = "";
    el("panenTanggalPanen").value = hariIni();
    el("modalPanenTitle").textContent = "Tambah Hasil Panen";
    tampilError("");
}

async function editData(id) {
    try {
        const json = await request(`${API_PANEN}/${id}`);
        const d = json.data;

        el("formPanen").reset();
        el("panenId").value = d.id;
        el("panenSiklusId").value = d.siklus_id;
        el("panenTanggalPanen").value = d.tanggal_panen;
        el("panenJumlahKg").value = d.jumlah_kg;
        el("panenKualitas").value = d.kualitas;
        el("panenCatatan").value = d.catatan || "";
        el("modalPanenTitle").textContent = "Edit Hasil Panen";
        tampilError("");
        el("modalPanen").showModal();
    } catch (err) {
        alert(err.message);
    }
}

async function simpanData(event) {
    event.preventDefault();

    const id = el("panenId").value;
    const payload = {
        siklus_id: el("panenSiklusId").value,
        tanggal_panen: el("panenTanggalPanen").value,
        jumlah_kg: el("panenJumlahKg").value,
        kualitas: el("panenKualitas").value,
        catatan: el("panenCatatan").value,
    };

    el("btnSimpan").disabled = true;
    try {
        await request(id ? `${API_PANEN}/${id}` : API_PANEN, {
            method: id ? "PUT" : "POST",
            body: JSON.stringify(payload),
        });
        el("modalPanen").close();
        await loadData();
    } catch (err) {
        tampilError(err.message);
    } finally {
        el("btnSimpan").disabled = false;
    }
}

async function hapusData(id) {
    if (!confirm("Yakin ingin menghapus data panen ini?")) return;
    try {
        await request(`${API_PANEN}/${id}`, { method: "DELETE" });
        await loadData();
    } catch (err) {
        alert(err.message);
    }
}

// ---------- Event ----------
document.addEventListener("DOMContentLoaded", () => {
    loadSiklusOptions();
    loadData();

    el("formPanen").addEventListener("submit", simpanData);
    el("btnTerapkan").addEventListener("click", loadData);
    el("btnReset").addEventListener("click", () => {
        el("filterDari").value = "";
        el("filterSampai").value = "";
        loadData();
    });
    el("btnCetak").addEventListener("click", () => window.print());

    // Tombol Tambah & Batal untuk dialog native
    el("btnTambah").addEventListener("click", () => {
        resetFormPanen();
        el("modalPanen").showModal();
    });
    el("btnBatal").addEventListener("click", () => el("modalPanen").close());

    // Event delegation untuk tombol Edit/Hapus di tabel
    el("tabelPanenBody").addEventListener("click", (e) => {
        const btn = e.target.closest("button[data-aksi]");
        if (!btn) return;
        const id = Number(btn.dataset.id);
        if (btn.dataset.aksi === "edit") editData(id);
        if (btn.dataset.aksi === "hapus") hapusData(id);
    });
});