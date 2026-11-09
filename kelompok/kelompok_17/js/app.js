/**
 * KabarSI - Client-Side API Consumer (Fetch API & DOM Manipulation)
 * File: js/app.js
 */

// -------------------------------------------------------------
// 1. ENDPOINT BASE URL
// -------------------------------------------------------------
const API = {
    aspirasi: 'api/aspirasi.php',
    berita: 'api/berita.php',
    kategori: 'api/kategori.php',
    komentar: 'api/komentar.php',
    users: 'api/users.php'
};

// -------------------------------------------------------------
// 2. UTILS / HELPER FUNCTIONS
// -------------------------------------------------------------

/**
 * Menampilkan Alert / Toast Notifikasi Dinamis
 */
function showAlert(message, type = 'success') {
    const alertContainer = document.getElementById('alert-box');
    if (!alertContainer) return;

    alertContainer.textContent = message;
    alertContainer.className = `alert-box alert-${type}`;
    alertContainer.style.display = 'block';

    setTimeout(() => {
        alertContainer.style.display = 'none';
    }, 4000);
}

// -------------------------------------------------------------
// 3. MODUL ASPIRASI
// -------------------------------------------------------------

/**
 * Load Semua Data Aspirasi (Untuk Admin Dashboard / User Feed)
 */
async function loadAspirasi(keyword = '') {
    const container = document.getElementById('tabel-aspirasi') || document.getElementById('container-aspirasi');
    if (!container) return;

    try {
        const response = await fetch(API.aspirasi);
        const result = await response.json();

        if (response.ok && result.status === 'success') {
            renderAspirasi(result.data, keyword);
        } else {
            showAlert(result.message || 'Gagal mengambil data aspirasi', 'danger');
        }
    } catch (err) {
        showAlert(`Kesalahan koneksi aspirasi: ${err.message}`, 'danger');
    }
}

/**
 * Render Data Aspirasi ke DOM
 */
function renderAspirasi(list = [], keyword = '') {
    const tbody = document.getElementById('tabel-body-aspirasi');
    const feed = document.getElementById('aspirasi-feed');

    // Jika menggunakan tabel (Admin View)
    if (tbody) {
        tbody.innerHTML = '';
        if (list.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center">Belum ada aspirasi.</td></tr>';
            return;
        }

        list.forEach((item, index) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${index + 1}</td>
                <td><strong>${item.judul_aspirasi}</strong></td>
                <td><span class="badge badge-category">${item.nama_kategori}</span></td>
                <td>${item.pengirim}</td>
                <td>${new Date(item.tanggal_kirim).toLocaleDateString('id-ID')}</td>
                <td><span class="status-${item.status}">${item.status}</span></td>
                <td>
                    <select onchange="updateStatusAspirasi(${item.id_aspirasi}, this.value)" class="form-select-sm">
                        <option value="menunggu" ${item.status === 'menunggu' ? 'selected' : ''}>Menunggu</option>
                        <option value="ditinjau" ${item.status === 'ditinjau' ? 'selected' : ''}>Ditinjau</option>
                        <option value="selesai" ${item.status === 'selesai' ? 'selected' : ''}>Selesai</option>
                    </select>
                    <button class="btn btn-danger btn-sm" onclick="deleteAspirasi(${item.id_aspirasi})">Hapus</button>
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    // Jika menggunakan Kartu / Feed (User View)
    if (feed) {
        feed.innerHTML = '';
        if (list.length === 0) {
            feed.innerHTML = '<p class="text-muted">Belum ada aspirasi dikirim.</p>';
            return;
        }

        list.forEach(item => {
            const card = document.createElement('div');
            card.className = 'card-aspirasi';
            card.innerHTML = `
                <h3>${item.judul_aspirasi}</h3>
                <small>Oleh: ${item.pengirim} | Kategori: ${item.nama_kategori} | Status: ${item.status}</small>
                <p>${item.isi_aspirasi}</p>
                <button onclick="loadKomentar('aspirasi', ${item.id_aspirasi})" class="btn-link">Lihat Komentar</button>
            `;
            feed.appendChild(card);
        });
    }
}

/**
 * Submit Form Kirim Aspirasi Baru (POST)
 */
const formAspirasi = document.getElementById('form-tambah-aspirasi');
if (formAspirasi) {
    formAspirasi.addEventListener('submit', async function (e) {
        e.preventDefault();

        const payload = {
            id_user: document.getElementById('id_user').value,
            id_kategori: document.getElementById('id_kategori').value,
            judul_aspirasi: document.getElementById('judul_aspirasi').value.trim(),
            isi_aspirasi: document.getElementById('isi_aspirasi').value.trim(),
            is_anonim: document.getElementById('is_anonim')?.checked ? 1 : 0
        };

        try {
            const response = await fetch(API.aspirasi, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const result = await response.json();

            if (response.ok && result.status === 'success') {
                showAlert(result.message, 'success');
                formAspirasi.reset();
                loadAspirasi();
            } else {
                showAlert(result.message || 'Gagal mengirim aspirasi.', 'danger');
            }
        } catch (err) {
            showAlert(`Terjadi kesalahan: ${err.message}`, 'danger');
        }
    });
}

/**
 * Update Status Aspirasi oleh Admin (PUT)
 */
async function updateStatusAspirasi(idAspirasi, newStatus) {
    try {
        const response = await fetch(`${API.aspirasi}?id=${idAspirasi}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ status: newStatus })
        });
        const result = await response.json();

        if (response.ok && result.status === 'success') {
            showAlert(result.message, 'success');
            loadAspirasi();
        } else {
            showAlert(result.message || 'Gagal memperbarui status', 'danger');
        }
    } catch (err) {
        showAlert(`Error: ${err.message}`, 'danger');
    }
}

/**
 * Hapus Data Aspirasi (DELETE)
 */
async function deleteAspirasi(idAspirasi) {
    if (!confirm('Apakah Anda yakin ingin menghapus aspirasi ini?')) return;

    try {
        const response = await fetch(`${API.aspirasi}?id=${idAspirasi}`, {
            method: 'DELETE'
        });
        const result = await response.json();

        if (response.ok && result.status === 'success') {
            showAlert(result.message, 'success');
            loadAspirasi();
        } else {
            showAlert(result.message || 'Gagal menghapus aspirasi', 'danger');
        }
    } catch (err) {
        showAlert(`Error: ${err.message}`, 'danger');
    }
}

// -------------------------------------------------------------
// 4. MODUL BERITA
// -------------------------------------------------------------

/**
 * Load Data Berita (Dengan Live Search)
 */
async function loadBerita(keyword = '') {
    const container = document.getElementById('container-berita');
    if (!container) return;

    const url = keyword ? `${API.berita}?search=${encodeURIComponent(keyword)}` : API.berita;

    try {
        const response = await fetch(url);
        const result = await response.json();

        if (response.ok && result.status === 'success') {
            renderBerita(result.data);
        } else {
            showAlert(result.message || 'Gagal mengambil berita', 'danger');
        }
    } catch (err) {
        showAlert(`Error koneksi berita: ${err.message}`, 'danger');
    }
}

/**
 * Render List Berita
 */
function renderBerita(list = []) {
    const container = document.getElementById('container-berita');
    if (!container) return;

    container.innerHTML = '';
    if (list.length === 0) {
        container.innerHTML = '<p class="text-muted">Tidak ada berita ditemukan.</p>';
        return;
    }

    list.forEach(item => {
        const itemBerita = document.createElement('div');
        itemBerita.className = 'card-berita';
        itemBerita.innerHTML = `
            <img src="${item.gambar_url || 'assets/img/default-news.jpg'}" alt="${item.judul_berita}" class="img-berita">
            <div class="berita-content">
                <span class="badge">${item.nama_kategori}</span>
                <h3>${item.judul_berita}</h3>
                <small>Penulis: ${item.penulis} | ${new Date(item.tanggal_publikasi).toLocaleDateString('id-ID')}</small>
                <p>${item.isi_berita.substring(0, 120)}...</p>
                <a href="berita_detail.php?id=${item.id_berita}" class="btn btn-primary btn-sm">Baca Selengkapnya</a>
            </div>
        `;
        container.appendChild(itemBerita);
    });
}

// Live Search Berita
const inputSearchBerita = document.getElementById('search-berita');
if (inputSearchBerita) {
    let timer = null;
    inputSearchBerita.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(() => {
            loadBerita(inputSearchBerita.value.trim());
        }, 300);
    });
}

// -------------------------------------------------------------
// 5. MODUL KOMENTAR
// -------------------------------------------------------------

/**
 * Load Komentar berdasarkan Berita / Aspirasi
 */
async function loadKomentar(tipe, idTarget) {
    const containerKomentar = document.getElementById('list-komentar');
    if (!containerKomentar) return;

    const param = tipe === 'berita' ? `id_berita=${idTarget}` : `id_aspirasi=${idTarget}`;

    try {
        const response = await fetch(`${API.komentar}?${param}`);
        const result = await response.json();

        if (response.ok && result.status === 'success') {
            containerKomentar.innerHTML = '';
            if (result.data.length === 0) {
                containerKomentar.innerHTML = '<p class="text-muted">Belum ada komentar.</p>';
                return;
            }

            result.data.forEach(k => {
                const div = document.createElement('div');
                div.className = 'box-komentar';
                div.innerHTML = `
                    <strong>${k.pengirim}</strong> <small>${new Date(k.tanggal_komentar).toLocaleString('id-ID')}</small>
                    <p>${k.isi_komentar}</p>
                `;
                containerKomentar.appendChild(div);
            });
        }
    } catch (err) {
        console.error('Gagal mengambil komentar:', err);
    }
}

/**
 * Submit Komentar Baru (POST)
 */
const formKomentar = document.getElementById('form-tambah-komentar');
if (formKomentar) {
    formKomentar.addEventListener('submit', async function (e) {
        e.preventDefault();

        const payload = {
            id_user: document.getElementById('komentar_id_user').value,
            id_berita: document.getElementById('komentar_id_berita')?.value || null,
            id_aspirasi: document.getElementById('komentar_id_aspirasi')?.value || null,
            isi_komentar: document.getElementById('isi_komentar').value.trim(),
            is_anonim: document.getElementById('komentar_is_anonim')?.checked ? 1 : 0
        };

        try {
            const response = await fetch(API.komentar, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const result = await response.json();

            if (response.ok && result.status === 'success') {
                showAlert(result.message, 'success');
                formKomentar.reset();
                const targetId = payload.id_berita || payload.id_aspirasi;
                const tipe = payload.id_berita ? 'berita' : 'aspirasi';
                loadKomentar(tipe, targetId);
            } else {
                showAlert(result.message || 'Gagal mengirim komentar.', 'danger');
            }
        } catch (err) {
            showAlert(`Error: ${err.message}`, 'danger');
        }
    });
}

// -------------------------------------------------------------
// 6. INISIALISASI SAAT HALAMAN SELESAI DIMUAT
// -------------------------------------------------------------
document.addEventListener('DOMContentLoaded', () => {
    loadAspirasi();
    loadBerita();
});