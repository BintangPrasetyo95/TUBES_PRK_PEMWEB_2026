const CONFIG = {
    kategoriUrl: '../api/kategori.php',
    obatUrl: '../api/obat.php',
    debounceMs: 300
};

const KATEGORI_LOKAL = [
    { id_kategori: 1, nama_kategori: 'Pereda Nyeri & Demam' },
    { id_kategori: 2, nama_kategori: 'Obat Batuk, Pilek & Flu' },
    { id_kategori: 3, nama_kategori: 'Saluran Pencernaan' },
    { id_kategori: 4, nama_kategori: 'Vitamin & Suplemen' },
    { id_kategori: 5, nama_kategori: 'Antiseptik & Perawatan Luka' }
];

const OBAT_LOKAL = [
    { id_obat: 1, nama_obat: 'Paracetamol 500 mg (Strip 10 Tablet)', id_kategori: 1, stok: 45, indikasi: 'Meredakan demam serta nyeri ringan hingga sedang seperti sakit kepala, sakit gigi, dan nyeri otot.', aturan_pakai: 'Dewasa: 1-2 tablet, 3-4 kali sehari sesudah makan. Maksimal 8 tablet per hari.', peringatan: 'Hati-hati penggunaan pada penderita gangguan fungsi hati dan ginjal. Jangan melebihi dosis yang dianjurkan.' },
    { id_obat: 2, nama_obat: 'Ibuprofen 400 mg (Strip 10 Tablet)', id_kategori: 1, stok: 8, indikasi: 'Mengatasi nyeri inflamasi ringan hingga sedang, nyeri haid (dismenore), sakit gigi, nyeri sendi, dan menurunkan demam.', aturan_pakai: 'Dewasa: 1 tablet, 3 kali sehari segera sesudah makan.', peringatan: 'Tidak dianjurkan untuk penderita tukak lambung, ibu hamil trimester akhir, dan pasien dengan riwayat alergi OAINS.' },
    { id_obat: 3, nama_obat: 'Asam Mefenamat 500 mg (Strip 10 Kaplet)', id_kategori: 1, stok: 25, indikasi: 'Meredakan nyeri akut dan kronik ringan sampai sedang sehubungan dengan sakit kepala, sakit gigi, dan nyeri pasca operasi.', aturan_pakai: 'Dewasa: Dosis awal 1 kaplet, dilanjutkan 1/2 - 1 kaplet tiap 6 jam sesudah makan.', peringatan: 'Jangan digunakan lebih dari 7 hari. Kontraindikasi pada pasien radang usus dan gangguan ginjal berat.' },
    { id_obat: 4, nama_obat: 'Sanmol Sirup 60 ml', id_kategori: 1, stok: 0, indikasi: 'Menurunkan panas demam dan meredakan rasa sakit pada anak-anak seperti sakit kepala dan nyeri tumbuh gigi.', aturan_pakai: 'Anak 1-2 tahun: 5 ml (1 sendok takar), 3-4 kali sehari. Anak 2-6 tahun: 5-10 ml, 3-4 kali sehari.', peringatan: 'Kocok dahulu sebelum diminum. Hubungi dokter bila demam tidak turun setelah 2 hari penggunaan.' },
    { id_obat: 5, nama_obat: 'OBH Combi Batuk Berdahak Menthol 100 ml', id_kategori: 2, stok: 30, indikasi: 'Meredakan batuk berdahak yang disertai gejala flu seperti demam, sakit kepala, hidung tersumbat, dan bersin-bersin.', aturan_pakai: 'Dewasa & anak di atas 12 tahun: 3 kali sehari 3 sendok takar (15 ml) sesudah makan.', peringatan: 'Dapat menyebabkan kantuk. Jangan mengendarai kendaraan bermotor atau menjalankan mesin setelah meminum obat ini.' },
    { id_obat: 6, nama_obat: 'Siladex Mucolytic & Expectorant 60 ml', id_kategori: 2, stok: 5, indikasi: 'Mengencerkan dahak pada saluran pernapasan dan mempermudah pengeluaran dahak saat batuk.', aturan_pakai: 'Dewasa & anak di atas 10 tahun: 3 kali sehari 1 sendok takar (5 ml) sesudah makan.', peringatan: 'Bebas gula dan alkohol. Hati-hati penggunaan pada penderita tukak lambung dan ibu hamil trimester pertama.' },
    { id_obat: 7, nama_obat: 'Tremenza Sirup 60 ml', id_kategori: 2, stok: 18, indikasi: 'Meringankan gejala-gejala flu karena alergi pada saluran pernapasan bagian atas seperti hidung tersumbat dan bersin.', aturan_pakai: 'Dewasa: 3-4 kali sehari 2 sendok takar (10 ml). Anak 6-12 tahun: 3-4 kali sehari 1 sendok takar (5 ml).', peringatan: 'Tidak dianjurkan untuk penderita hipertensi berat, penyakit jantung koroner, dan pasien yang mengonsumsi antidepresan MAOI.' },
    { id_obat: 8, nama_obat: 'Degrol Hisap Dequalinium (Strip 10 Tablet)', id_kategori: 2, stok: 0, indikasi: 'Tablet hisap antiseptik untuk membantu meredakan radang tenggorokan, sariawan, dan infeksi ringan rongga mulut.', aturan_pakai: 'Hisap perlahan 1 tablet setiap 3-4 jam. Maksimal 8 tablet sehari.', peringatan: 'Hindari penggunaan jangka panjang secara terus-menerus. Tidak untuk anak di bawah usia 6 tahun.' },
    { id_obat: 9, nama_obat: 'Promag Tablet Kunyah (Strip 12 Tablet)', id_kategori: 3, stok: 50, indikasi: 'Meringankan gejala sakit maag dan kelebihan asam lambung seperti perih, mual, kembung, dan nyeri ulu hati.', aturan_pakai: 'Dewasa: 1-2 tablet dikunyah, 3-4 kali sehari. Diminum 1 jam sebelum makan atau 2 jam setelah makan dan menjelang tidur.', peringatan: 'Beri jeda minimal 2 jam jika sedang mengonsumsi antibiotik tetrasiklin atau suplemen zat besi.' },
    { id_obat: 10, nama_obat: 'Mylanta Cair 150 ml', id_kategori: 3, stok: 7, indikasi: 'Mengurangi gejala kelebihan asam lambung, gastritis, tukak lambung, dan tukak usus dua belas jari.', aturan_pakai: 'Dewasa: 1-2 sendok takar (5-10 ml), 3-4 kali sehari di antara waktu makan dan sebelum tidur.', peringatan: 'Tidak dianjurkan digunakan terus-menerus lebih dari 2 minggu kecuali atas petunjuk dokter.' },
    { id_obat: 11, nama_obat: 'New Diatabs Attapulgite (Strip 4 Tablet)', id_kategori: 3, stok: 35, indikasi: 'Pengobatan simtomatik pada diare non-spesifik dengan cara menyerap racun dan bakteri penyebab diare di saluran cerna.', aturan_pakai: 'Dewasa: 2 tablet setiap selesai buang air besar. Maksimal 12 tablet dalam 24 jam.', peringatan: 'Jangan diberikan pada pasien konstipasi (sembelit). Tetap cukupi kebutuhan cairan tubuh (oralit) selama diare.' },
    { id_obat: 12, nama_obat: 'Oralit 200 Garam Elektrolit (Sachet)', id_kategori: 3, stok: 60, indikasi: 'Mencegah dan mengobati dehidrasi akibat diare atau muntah-muntah dengan menggantikan cairan dan elektrolit tubuh.', aturan_pakai: 'Larutkan 1 sachet ke dalam 200 ml air matang hangat. Minum setiap kali selesai buang air besar.', peringatan: 'Larutan yang sudah dibuat tidak boleh digunakan lagi setelah lewat dari 24 jam.' },
    { id_obat: 13, nama_obat: 'Vitamin C IPI 50 mg (Botol 45 Tablet)', id_kategori: 4, stok: 22, indikasi: 'Membantu memenuhi kebutuhan Vitamin C harian, menjaga daya tahan tubuh, serta membantu pemulihan pasca sakit.', aturan_pakai: 'Dewasa: 1-2 tablet hisap sehari sesudah makan.', peringatan: 'Simpan di tempat kering di bawah suhu 30 derajat Celsius dan terhindar dari sinar matahari langsung.' },
    { id_obat: 14, nama_obat: 'Imboost Force (Strip 10 Kaplet)', id_kategori: 4, stok: 4, indikasi: 'Suplemen imunomodulator (Echinacea & Zinc) untuk membantu memelihara daya tahan tubuh dan mempercepat penyembuhan.', aturan_pakai: 'Dewasa: 1 kaplet, 3 kali sehari sesudah makan.', peringatan: 'Tidak boleh digunakan oleh penderita penyakit autoimun, sklerosis multipel, tuberkulosis, dan AIDS. Maksimal konsumsi 8 minggu.' },
    { id_obat: 15, nama_obat: 'Sangobion Kapsul Penambah Darah (Strip 10 Kapsul)', id_kategori: 4, stok: 28, indikasi: 'Suplemen zat besi, vitamin, dan mineral untuk mengatasi anemia (kurang darah) serta membantu pembentukan sel darah merah.', aturan_pakai: 'Dewasa: 1 kapsul sehari pada waktu atau sesudah makan.', peringatan: 'Dapat menyebabkan feses berwarna hitam yang merupakan hal normal akibat kandungan zat besi.' },
    { id_obat: 16, nama_obat: 'Neurobion Forte (Strip 10 Tablet)', id_kategori: 4, stok: 0, indikasi: 'Suplemen vitamin neurotropik (B1, B6, B12) untuk menjaga kesehatan fungsi saraf serta mengatasi kesemutan dan kebas.', aturan_pakai: 'Dewasa: 1 tablet sehari sesudah makan, ditelan utuh dengan air putih.', peringatan: 'Tidak dianjurkan untuk anak-anak karena tingginya dosis vitamin B di dalam tablet.' },
    { id_obat: 17, nama_obat: 'Betadine Antiseptic Solution 15 ml', id_kategori: 5, stok: 19, indikasi: 'Cairan antiseptik Povidone-Iodine 10% untuk membunuh kuman penyebab infeksi pada luka gores, luka bakar ringan, dan luka lecet.', aturan_pakai: 'Bersihkan luka terlebih dahulu, lalu oleskan Betadine secukupnya 1-2 kali sehari pada area yang luka.', peringatan: 'Hanya untuk pemakaian luar. Hentikan pemakaian jika timbul kemerahan, iritasi, atau pembengkakan pada kulit.' },
    { id_obat: 18, nama_obat: 'Hansaplast Kain Elastis (Amplop 10 Lembar)', id_kategori: 5, stok: 40, indikasi: 'Plester penutup luka berbahan kain lentur yang mengikuti pergerakan kulit untuk melindungi luka dari kotoran dan bakteri.', aturan_pakai: 'Bersihkan dan keringkan kulit di sekitar luka, lalu tempelkan plester tanpa diregangkan. Ganti plester setiap hari.', peringatan: 'Hanya untuk sekali pakai. Jangan gunakan pada luka terbuka yang mengalami perdarahan hebat.' },
    { id_obat: 19, nama_obat: 'Rivanol Kompres Luka 100 ml', id_kategori: 5, stok: 3, indikasi: 'Cairan antiseptik Etakridina Laktat 0,1% untuk membersihkan luka dan mengompres luka bernanah atau borok.', aturan_pakai: 'Tuangkan pada kapas atau kasa steril, lalu usapkan atau kompreskan pada bagian luka.', peringatan: 'Hanya untuk penggunaan luar. Jauhkan dari jangkauan anak-anak dan simpan di tempat sejuk.' },
    { id_obat: 20, nama_obat: 'Bioplacenton Gel 15 gr', id_kategori: 5, stok: 14, indikasi: 'Mengobati luka bakar ringan, luka terinfeksi, dan membantu mempercepat regenerasi jaringan kulit baru.', aturan_pakai: 'Oleskan tipis-tipis pada area luka 4-6 kali sehari sesuai kebutuhan.', peringatan: 'Obat keras terbatas. Hindari kontak langsung dengan mata dan jangan digunakan oleh pasien yang alergi terhadap Neomycin.' }
];

const state = { kategoriAktif: 'semua', kataKunci: '', daftarKategori: KATEGORI_LOKAL, daftarObat: OBAT_LOKAL, gunakanApi: false, requestId: 0 };
const dom = {
    categories: document.getElementById('wadah-kategori'), grid: document.getElementById('grid-obat'), loading: document.getElementById('state-loading'), empty: document.getElementById('state-kosong'), count: document.getElementById('info-jumlah-obat'), search: document.getElementById('input-pencarian'), clear: document.getElementById('btn-reset-cari'), reset: document.getElementById('btn-reset-filter')
};
const iconArrow = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>';

function escapeHTML(value) {
    return String(value ?? '').replace(/[&<>"']/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[char]);
}

function normalizeRows(payload) {
    const rows = Array.isArray(payload) ? payload : payload?.data;
    if (!Array.isArray(rows) || payload?.status === 'error') throw new Error('Respons API tidak valid');
    return rows;
}

async function fetchRows(url) {
    const response = await fetch(url, { headers: { Accept: 'application/json' } });
    if (!response.ok) throw new Error(`HTTP ${response.status}`);
    return normalizeRows(await response.json());
}

function categoryName(id) {
    return state.daftarKategori.find(category => String(category.id_kategori) === String(id))?.nama_kategori || 'Umum';
}

function renderCategories() {
    const buttons = [{ id_kategori: 'semua', nama_kategori: 'Semua obat' }, ...state.daftarKategori];
    dom.categories.innerHTML = buttons.map(category => {
        const active = String(state.kategoriAktif) === String(category.id_kategori);
        return `<button type="button" class="pill${active ? ' active' : ''}" data-id="${escapeHTML(category.id_kategori)}" role="tab" aria-selected="${active}">${escapeHTML(category.nama_kategori)}</button>`;
    }).join('');
}

function stockStatus(stockValue) {
    const stock = Number(stockValue);
    if (stock <= 0) return { label: 'Habis', className: 'badge-habis' };
    if (stock <= 5) return { label: 'Menipis', className: 'badge-menipis' };
    return { label: 'Tersedia', className: 'badge-tersedia' };
}

function visibleMedicines() {
    const term = state.kataKunci.trim().toLocaleLowerCase('id');
    return state.daftarObat.filter(medicine => {
        const categoryMatches = state.kategoriAktif === 'semua' || String(medicine.id_kategori) === String(state.kategoriAktif);
        const searchable = [medicine.nama_obat, medicine.indikasi, medicine.aturan_pakai, medicine.peringatan, medicine.nama_kategori, categoryName(medicine.id_kategori)].join(' ').toLocaleLowerCase('id');
        return categoryMatches && (!term || searchable.includes(term));
    });
}

function renderCatalog() {
    const medicines = visibleMedicines();
    dom.count.textContent = `Menampilkan ${medicines.length} obat`;
    dom.grid.innerHTML = medicines.map(medicine => {
        const status = stockStatus(medicine.stok);
        const data = escapeHTML(JSON.stringify(medicine));
        return `<article class="medicine-card"><div><div class="card-top"><span class="category-tag">${escapeHTML(medicine.nama_kategori || categoryName(medicine.id_kategori))}</span><span class="stock-badge ${status.className}">${status.label}</span></div><h3 class="medicine-name">${escapeHTML(medicine.nama_obat)}</h3><p class="medicine-indication">${escapeHTML(medicine.indikasi)}</p><div class="medicine-dosage"><strong>Aturan pakai</strong>${escapeHTML(medicine.aturan_pakai)}</div></div><div class="card-footer"><span class="stock-number">Sisa stok: <strong>${escapeHTML(medicine.stok)} unit</strong></span><button type="button" class="btn-detail" data-obat="${data}">Detail obat ${iconArrow}</button></div></article>`;
    }).join('');
    dom.empty.hidden = medicines.length > 0;
    dom.grid.hidden = medicines.length === 0;
}

function render() {
    renderCategories();
    renderCatalog();
}

function filterLocal() {
    state.daftarObat = OBAT_LOKAL.map(medicine => ({ ...medicine, nama_kategori: categoryName(medicine.id_kategori) }));
    state.daftarKategori = KATEGORI_LOKAL;
}

async function loadCatalog() {
    const requestId = ++state.requestId;
    dom.loading.hidden = false;
    dom.grid.hidden = true;
    dom.empty.hidden = true;
    try {
        const [categories, medicines] = await Promise.all([
            fetchRows(CONFIG.kategoriUrl),
            fetchRows(CONFIG.obatUrl)
        ]);
        if (requestId !== state.requestId) return;
        state.daftarKategori = categories;
        state.daftarObat = medicines;
        state.useApi = true;
    } catch {
        if (requestId !== state.requestId) return;
        state.useApi = false;
        filterLocal();
    }
    if (requestId !== state.requestId) return;
    dom.loading.hidden = true;
    render();
}

async function refreshFromApi() {
    if (!state.useApi) {
        render();
        return;
    }
    const requestId = ++state.requestId;
    const params = new URLSearchParams();
    if (state.kategoriAktif !== 'semua') params.set('id_kategori', state.kategoriAktif);
    if (state.kataKunci.trim()) params.set('q', state.kataKunci.trim());
    dom.loading.hidden = false;
    dom.grid.hidden = true;
    dom.empty.hidden = true;
    try {
        const url = params.size ? `${CONFIG.obatUrl}?${params}` : CONFIG.obatUrl;
        const medicines = await fetchRows(url);
        if (requestId !== state.requestId) return;
        state.daftarObat = medicines;
    } catch {
        if (requestId !== state.requestId) return;
        state.useApi = false;
        filterLocal();
    }
    if (requestId !== state.requestId) return;
    dom.loading.hidden = true;
    render();
}

function debounce(callback, delay) {
    let timer;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => callback(...args), delay);
    };
}

function openFallbackDialog(medicine) {
    const dialog = document.createElement('dialog');
    dialog.className = 'detail-dialog';
    dialog.innerHTML = `<div class="detail-dialog-content"><button type="button" class="detail-dialog-close" aria-label="Tutup dialog"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg></button><span class="eyebrow">${escapeHTML(categoryName(medicine.id_kategori))}</span><h2>${escapeHTML(medicine.nama_obat)}</h2><dl><dt>Indikasi</dt><dd>${escapeHTML(medicine.indikasi)}</dd><dt>Aturan pakai</dt><dd>${escapeHTML(medicine.aturan_pakai)}</dd><dt>Peringatan</dt><dd>${escapeHTML(medicine.peringatan)}</dd></dl></div>`;
    document.body.append(dialog);
    dialog.querySelector('.detail-dialog-close').addEventListener('click', () => dialog.close());
    dialog.addEventListener('click', event => { if (event.target === dialog) dialog.close(); });
    dialog.addEventListener('close', () => dialog.remove());
    dialog.showModal();
}

function init() {
    dom.categories.addEventListener('click', event => {
        const button = event.target.closest('.pill');
        if (!button) return;
        state.kategoriAktif = button.dataset.id;
        refreshFromApi();
    });
    dom.search.addEventListener('input', debounce(() => {
        state.kataKunci = dom.search.value;
        dom.clear.hidden = !state.kataKunci;
        refreshFromApi();
    }, CONFIG.debounceMs));
    dom.clear.addEventListener('click', () => {
        dom.search.value = '';
        dom.clear.hidden = true;
        state.kataKunci = '';
        refreshFromApi();
        dom.search.focus();
    });
    dom.reset.addEventListener('click', () => {
        state.kategoriAktif = 'semua';
        state.kataKunci = '';
        dom.search.value = '';
        dom.clear.hidden = true;
        refreshFromApi();
    });
    dom.grid.addEventListener('click', event => {
        const button = event.target.closest('.btn-detail');
        if (!button) return;
        const medicine = JSON.parse(button.dataset.obat);
        if (typeof window.bukaModalDetail === 'function') window.bukaModalDetail(medicine);
        else openFallbackDialog(medicine);
    });
    document.addEventListener('keydown', event => {
        if (event.key !== '/' || event.ctrlKey || event.metaKey || event.altKey || event.target.matches('input, textarea, select, [contenteditable="true"]') || event.target.closest('dialog')) return;
        event.preventDefault();
        dom.search.focus();
    });
    loadCatalog();
}

document.addEventListener('DOMContentLoaded', init, { once: true });
