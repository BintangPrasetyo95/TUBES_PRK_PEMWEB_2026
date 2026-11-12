const endpoint = '../backend/laporan/laporan_process.php';

const tanggalDari = document.getElementById('tanggalDari');
const tanggalSampai = document.getElementById('tanggalSampai');
const filterButton = document.getElementById('filterButton');

let reportChart;

const formatRupiah = (angka) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0
    }).format(Number(angka) || 0);
};

const escapeHTML = (value) => {
    return String(value ?? '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    })[char]);
};

const getTanggalAwal = () => {
    const sekarang = new Date();

    return `${sekarang.getFullYear()}-${String(sekarang.getMonth() + 1).padStart(2, '0')}-01`;
};

const getTanggalAkhir = () => {
    const sekarang = new Date();
    const akhir = new Date(
        sekarang.getFullYear(),
        sekarang.getMonth() + 1,
        0
    );

    return `${akhir.getFullYear()}-${String(akhir.getMonth() + 1).padStart(2, '0')}-${String(akhir.getDate()).padStart(2, '0')}`;
};

async function ambilData(aksi, dari, sampai) {
    const url = `${endpoint}?aksi=${aksi}&dari=${dari}&sampai=${sampai}`;

    const response = await fetch(url);
    const data = await response.json();

    if (!response.ok) {
        throw new Error(data.error || 'Gagal mengambil data laporan.');
    }

    return data;
}

function tampilkanRingkasan(data) {
    let pemasukan = 0;
    let pengeluaran = 0;

    data.forEach(item => {
        if (item.jenis === 'pemasukan') {
            pemasukan = Number(item.total) || 0;
        }

        if (item.jenis === 'pengeluaran') {
            pengeluaran = Number(item.total) || 0;
        }
    });

    document.getElementById('totalPemasukan').textContent =
        formatRupiah(pemasukan);

    document.getElementById('totalPengeluaran').textContent =
        formatRupiah(pengeluaran);

    document.getElementById('saldoLaporan').textContent =
        formatRupiah(pemasukan - pengeluaran);
}

function tampilkanGrafik(transaksi, dari, sampai) {
    const context = document.getElementById('reportChart');

    if (reportChart) {
        reportChart.destroy();
    }

    const labels = [];
    const tanggalMap = {};


    const tanggalMulai = new Date(dari + 'T00:00:00Z');
    const tanggalAkhir = new Date(sampai + 'T00:00:00Z');

    for (
        let tanggal = new Date(tanggalMulai);
        tanggal <= tanggalAkhir;
        tanggal.setUTCDate(tanggal.getUTCDate() + 1)
    ) {
        const key = tanggal.toISOString().slice(0, 10);

        labels.push(key);

        tanggalMap[key] = {
            pemasukan: 0,
            pengeluaran: 0
        };
    }

    transaksi.forEach(item => {
        const tanggal = String(item.tanggal).slice(0, 10);

        if (!tanggalMap[tanggal]) {
            return;
        }

        const jumlah = Number(item.jumlah) || 0;

        if (item.jenis === 'pemasukan') {
            tanggalMap[tanggal].pemasukan += jumlah;
        } else if (item.jenis === 'pengeluaran') {
            tanggalMap[tanggal].pengeluaran += jumlah;
        }
    });

    const dataPemasukan = labels.map(
        tanggal => tanggalMap[tanggal].pemasukan
    );

    const dataPengeluaran = labels.map(
        tanggal => tanggalMap[tanggal].pengeluaran
        );

    console.log('Data transaksi:', transaksi);
console.log('Pemasukan:', dataPemasukan);
console.log('Pengeluaran:', dataPengeluaran);

    reportChart = new Chart(context, {
        type: 'line',

        data: {
            labels: labels,

            datasets: [
                {
                    label: 'Pemasukan',
                    data: dataPemasukan,

                    borderColor: '#27966b',
                    backgroundColor: 'rgba(39, 150, 107, 0.12)',

                    fill: true,
                    tension: 0.35,
                    borderWidth: 3,

                    pointRadius: 3,
                    pointHoverRadius: 6,
                    pointBackgroundColor: '#27966b'
                },

                {
                    label: 'Pengeluaran',
                    data: dataPengeluaran,

                    borderColor: '#e47777',
                    backgroundColor: 'rgba(228, 119, 119, 0.10)',

                    fill: true,
                    tension: 0.35,
                    borderWidth: 3,

                    pointRadius: 3,
                    pointHoverRadius: 6,
                    pointBackgroundColor: '#e47777'
                }
            ]
        },

        options: {
            responsive: true,
            maintainAspectRatio: false,

            interaction: {
                mode: 'index',
                intersect: false
            },

            plugins: {
                legend: {
                    display: true,
                    position: 'top',

                    labels: {
                        usePointStyle: true,
                        padding: 20
                    }
                },

                tooltip: {
                    callbacks: {
                        title: items => {
                            const tanggal = items[0].label;
                            const [tahun, bulan, hari] = tanggal.split('-');

                            return `${hari}/${bulan}/${tahun}`;
                        },

                        label: context => {
                            return `${context.dataset.label}: ${formatRupiah(context.raw)}`;
                        }
                    }
                }
            },

            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Tanggal Transaksi'
                    },

                    ticks: {
                        maxTicksLimit: 10,
                        maxRotation: 0
                    },

                    grid: {
                        display: false
                    }
                },

                y: {
                    beginAtZero: true,

                    title: {
                        display: true,
                        text: 'Jumlah (Rupiah)'
                    },

                    ticks: {
    precision: 0,
    callback: value => {
        value = Math.round(value);

        if (value >= 1000000) {
            return 'Rp' + (value / 1000000).toFixed(1) + ' jt';
        }

        if (value >= 1000) {
            return 'Rp' + (value / 1000).toFixed(0) + ' rb';
        }

        return 'Rp' + value.toLocaleString('id-ID');
    }
}
                }
            }
        }
    });
}

function tampilkanKategori(data) {
    const container = document.getElementById('categoryList');

    if (!data.length) {
        container.innerHTML =
            '<p class="empty-message">Belum ada data kategori pada periode ini.</p>';

        return;
    }

    container.innerHTML = data.map(item => `
        <div class="category-item">
            <div class="category-info">
                <strong>${escapeHTML(item.nama_kategori)}</strong>

                <small>
                    ${item.jenis === 'pemasukan' ? 'Pemasukan' : 'Pengeluaran'}
                </small>
            </div>

            <strong>${formatRupiah(item.total)}</strong>
        </div>
    `).join('');
}

function tampilkanTransaksi(data) {
    const container = document.getElementById('transactionList');

    if (!data.length) {
        container.innerHTML =
            '<tr><td colspan="5" class="empty-message">Belum ada transaksi pada periode ini.</td></tr>';

        return;
    }

    container.innerHTML = data.map(item => {
        const jenis = item.jenis === 'pemasukan' ? 'income' : 'expense';

        const label = item.jenis === 'pemasukan'
            ? 'Pemasukan'
            : 'Pengeluaran';

        return `
            <tr>
                <td>${escapeHTML(item.tanggal)}</td>

                <td>${escapeHTML(item.keterangan || '-')}</td>

                <td>${escapeHTML(item.nama_kategori)}</td>

                <td>
                    <span class="transaction-type ${jenis}">
                        ${label}
                    </span>
                </td>

                <td>${formatRupiah(item.jumlah)}</td>
            </tr>
        `;
    }).join('');
}

async function muatLaporan() {
    const dari = tanggalDari.value;
    const sampai = tanggalSampai.value;

    if (!dari || !sampai || dari > sampai) {
        alert('Silakan pilih rentang tanggal yang valid.');
        return;
    }

    filterButton.disabled = true;
    filterButton.textContent = 'Memuat...';

    try {
        const [total, kategori, transaksi] = await Promise.all([
            ambilData('total', dari, sampai),
            ambilData('kategori', dari, sampai),
            ambilData('transaksi', dari, sampai)
        ]);

        tampilkanRingkasan(total);

        tampilkanGrafik(transaksi, dari, sampai);

        tampilkanKategori(kategori);
        tampilkanTransaksi(transaksi);

    } catch (error) {
        alert(error.message);

    } finally {
        filterButton.disabled = false;
        filterButton.textContent = 'Tampilkan';
    }
}

tanggalDari.value = getTanggalAwal();
tanggalSampai.value = getTanggalAkhir();

filterButton.addEventListener('click', muatLaporan);

muatLaporan();