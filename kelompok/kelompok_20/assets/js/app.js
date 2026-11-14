let currentKeyword = '';
let currentKategori = '';
let currentJenis = '';
let currentPage = 1;
let debounceTimer = null;

function esc(str) {
    if (str === null || str === undefined) {
        return '';
    }
    return String(str)
        .replace(/&/g, '&' + 'amp;')
        .replace(/</g, '&' + 'lt;')
        .replace(/>/g, '&' + 'gt;')
        .replace(/"/g, '&' + 'quot;')
        .replace(/'/g, '&' + '#039;');
}

function badgeStatus(status) {
    const s = String(status || '').toLowerCase().trim();
    let badgeClass = 'badge-menunggu';
    let label = 'Menunggu';

    if (s === 'diproses') {
        badgeClass = 'badge-diproses';
        label = 'Diproses';
    } else if (s === 'selesai') {
        badgeClass = 'badge-selesai';
        label = 'Selesai';
    }

    return `<span class="badge ${badgeClass}">${esc(label)}</span>`;
}

function formatTanggal(dateString) {
    if (!dateString) return '-';
    const normalized = String(dateString).replace(' ', 'T');
    const date = new Date(normalized);
    if (isNaN(date.getTime())) return esc(dateString);

    const tgl = date.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
    const jam = date.toLocaleTimeString('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: false
    }).replace('.', ':');

    return `${tgl}, ${jam}`;
}

function resetSemuaFilter() {
    currentKeyword = '';
    currentKategori = '';
    currentJenis = '';

    const searchInput = document.getElementById('search-input');
    const filterKategori = document.getElementById('filter-kategori');
    const filterJenis = document.getElementById('filter-jenis');

    if (searchInput) searchInput.value = '';
    if (filterKategori) filterKategori.value = '';
    if (filterJenis) filterJenis.value = '';

    loadLaporan(1);
}

function loadLaporan(page = 1) {
    currentPage = page;
    const container = document.getElementById('laporan-container');
    if (!container) return;

    container.innerHTML = `
        <div style="text-align: center; padding: 2.5rem 1rem; color: var(--color-text-muted);">
            <em>Memuat data dari server...</em>
        </div>
    `;

    const params = new URLSearchParams();
    if (currentKeyword) params.append('search', currentKeyword);
    if (currentKategori) params.append('kategori', currentKategori);
    if (currentJenis) params.append('jenis', currentJenis);
    params.append('page', currentPage);

    const url = `/api/laporan.php?${params.toString()}`;

    fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error('Respons jaringan tidak berhasil');
            }
            return response.json();
        })
        .then(res => {
            if (res.status === 'success') {
                renderLaporan(res.data, res.pagination);
                renderPagination(res.pagination);
            } else {
                showError(res.message || 'Gagal memuat data laporan.');
            }
        })
        .catch(err => {
            console.error('Error saat fetch laporan:', err);
            showError('Gagal memuat data dari server. Pastikan koneksi server aktif.');
        });
}

function renderLaporan(laporanList, pagination) {
    const container = document.getElementById('laporan-container');
    const statusInfo = document.getElementById('search-info');
    if (!container) return;

    if (statusInfo) {
        const total = pagination && pagination.total_data !== undefined ? pagination.total_data : (laporanList ? laporanList.length : 0);
        let infoHtml = `Menampilkan <strong>${total}</strong> laporan yang ditemukan`;
        if (currentKeyword) {
            infoHtml = `Menampilkan hasil pencarian "<strong>${esc(currentKeyword)}</strong>" (<strong>${total}</strong> laporan ditemukan)`;
        }
        if (pagination && pagination.total_pages > 1) {
            infoHtml += ` • Halaman ${pagination.current_page} dari ${pagination.total_pages}`;
        }
        statusInfo.innerHTML = infoHtml + '.';
    }

    if (!laporanList || laporanList.length === 0) {
        const isFiltered = Boolean(currentKeyword || currentKategori || currentJenis);
        container.innerHTML = `
            <div class="card" style="text-align: center; padding: 3rem 1rem; color: var(--color-text-muted);">
                <p style="font-size: 1.1rem; margin-bottom: ${isFiltered ? '0.75rem' : '0'};">
                    ${isFiltered ? 'Tidak ada laporan yang sesuai dengan pencarian atau filter.' : 'Belum ada laporan publik yang terdaftar saat ini.'}
                </p>
                ${isFiltered ? '<button type="button" onclick="resetSemuaFilter()" class="btn btn-secondary">Reset Filter</button>' : ''}
            </div>
        `;
        const pagContainer = document.getElementById('pagination-container');
        if (pagContainer) pagContainer.innerHTML = '';
        return;
    }

    let html = '<div style="display: flex; flex-direction: column; gap: 1.25rem;">';

    laporanList.forEach(item => {
        const jenisLabel = esc(item.jenis).toUpperCase();
        const kategori = esc(item.nama_kategori || 'Umum').toUpperCase();
        const judul = esc(item.judul);
        const isi = esc(item.isi).replace(/\n/g, '<br>');
        const lokasi = esc(item.lokasi || '-');
        const pelapor = esc(item.nama_pelapor || 'Anonim');
        const tanggal = formatTanggal(item.created_at);
        const badge = badgeStatus(item.status);
        const detailUrl = `/detail.php?id=${encodeURIComponent(item.id)}`;

        let tanggapanHtml = '';
        if (item.tanggapan_terakhir) {
            tanggapanHtml = `
                <div style="background-color: var(--color-bg); border-left: 3px solid var(--color-secondary); border-radius: 4px; padding: 1rem; margin-bottom: 1rem;">
                    <div style="font-size: 0.82rem; font-weight: 700; color: var(--color-primary); text-transform: uppercase; margin-bottom: 0.25rem;">
                        Tanggapan Resmi Kampus:
                    </div>
                    <div style="font-size: 0.92rem; color: var(--color-text); line-height: 1.5;">
                        "${esc(item.tanggapan_terakhir)}"
                    </div>
                </div>
            `;
        }

        let fotoChipHtml = '';
        if (item.foto) {
            fotoChipHtml = `
                <span style="display: inline-flex; align-items: center; padding: 4px 12px; background: #E8ECE2; border: 1px solid #C4CEB5; color: #34402A; border-radius: 9999px; font-size: 0.8rem; font-weight: 600;">
                    Ada Foto
                </span>
            `;
        }

        html += `
            <article class="card" style="padding: 1.5rem; margin-bottom: 0;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap; margin-bottom: 0.75rem;">
                    <div>
                        <span style="font-size: 0.8rem; font-weight: 700; color: var(--color-secondary); text-transform: uppercase;">
                            ${jenisLabel} • ${kategori}
                        </span>
                        <h2 style="font-size: 1.35rem; color: var(--color-primary); margin: 0.25rem 0;">
                            <a href="${detailUrl}" style="color: inherit; text-decoration: none;">
                                ${judul}
                            </a>
                        </h2>
                    </div>
                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                        ${badge}
                    </div>
                </div>

                <p style="color: var(--color-text); font-size: 0.95rem; line-height: 1.6; margin-bottom: 1rem;">
                    ${isi}
                </p>

                ${tanggapanHtml}

                <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 1rem; padding-top: 1rem; border-top: 1px solid var(--color-border);">
                    <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; flex: 1;">
                        <span style="display: inline-flex; align-items: center; padding: 4px 12px; background: #f1f5f9; border: 1px solid #e2e8f0; color: #475569; border-radius: 9999px; font-size: 0.8rem; font-weight: 500;">
                            ${pelapor}
                        </span>
                        <span style="display: inline-flex; align-items: center; padding: 4px 12px; background: #f1f5f9; border: 1px solid #e2e8f0; color: #475569; border-radius: 9999px; font-size: 0.8rem; font-weight: 500;">
                            ${lokasi}
                        </span>
                        <span style="display: inline-flex; align-items: center; padding: 4px 12px; background: #f1f5f9; border: 1px solid #e2e8f0; color: #475569; border-radius: 9999px; font-size: 0.8rem; font-weight: 500;">
                            ${tanggal}
                        </span>
                        ${fotoChipHtml}
                    </div>

                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; justify-content: flex-end;">
                        <a href="${detailUrl}" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; height: 34px;">
                            Detail Lengkap →
                        </a>
                    </div>
                </div>
            </article>
        `;
    });

    html += '</div>';
    container.innerHTML = html;
}

function renderPagination(pagination) {
    const container = document.getElementById('pagination-container');
    if (!container) return;

    if (!pagination || pagination.total_pages <= 1) {
        container.innerHTML = '';
        return;
    }

    const totalPages = pagination.total_pages;
    const current = pagination.current_page;
    let html = '';

    if (current > 1) {
        html += `<button type="button" class="btn btn-secondary btn-sm" onclick="goToPage(${current - 1})">« Sebelumnya</button>`;
    } else {
        html += `<button type="button" class="btn btn-secondary btn-sm" disabled style="opacity: 0.5; cursor: not-allowed;">« Sebelumnya</button>`;
    }

    for (let i = 1; i <= totalPages; i++) {
        if (i === current) {
            html += `<button type="button" class="btn btn-primary btn-sm" style="font-weight: 700;">${i}</button>`;
        } else {
            html += `<button type="button" class="btn btn-secondary btn-sm" onclick="goToPage(${i})">${i}</button>`;
        }
    }

    if (current < totalPages) {
        html += `<button type="button" class="btn btn-secondary btn-sm" onclick="goToPage(${current + 1})">Selanjutnya »</button>`;
    } else {
        html += `<button type="button" class="btn btn-secondary btn-sm" disabled style="opacity: 0.5; cursor: not-allowed;">Selanjutnya »</button>`;
    }

    container.innerHTML = html;
}

function goToPage(page) {
    loadLaporan(page);
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function showError(pesan) {
    const container = document.getElementById('laporan-container');
    if (!container) return;

    container.innerHTML = `
        <div class="alert alert-danger" style="margin-bottom: 0;">
            ${esc(pesan)}
        </div>
    `;
}

function initLaporanPublik() {
    const searchInput = document.getElementById('search-input');
    const filterKategori = document.getElementById('filter-kategori');
    const filterJenis = document.getElementById('filter-jenis');
    const btnFilter = document.getElementById('btn-filter');
    const btnReset = document.getElementById('btn-reset');

    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                currentKeyword = e.target.value.trim();
                loadLaporan(1);
            }, 300);
        });
    }

    if (filterKategori) {
        filterKategori.addEventListener('change', (e) => {
            currentKategori = e.target.value;
            loadLaporan(1);
        });
    }

    if (filterJenis) {
        filterJenis.addEventListener('change', (e) => {
            currentJenis = e.target.value;
            loadLaporan(1);
        });
    }

    if (btnFilter) {
        btnFilter.addEventListener('click', () => {
            if (searchInput) currentKeyword = searchInput.value.trim();
            if (filterKategori) currentKategori = filterKategori.value;
            if (filterJenis) currentJenis = filterJenis.value;
            loadLaporan(1);
        });
    }

    if (btnReset) {
        btnReset.addEventListener('click', resetSemuaFilter);
    }

    loadLaporan(1);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initLaporanPublik);
} else {
    initLaporanPublik();
}
