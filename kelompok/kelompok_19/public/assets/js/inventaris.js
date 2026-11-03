// public/assets/js/inventaris.js
// Tugas: AJAX Fetch CRUD Inventaris ke /api/inventaris

const API_INVENTARIS = "/api/inventaris";
const API_SIKLUS = "/api/siklus"; // untuk dropdown form transaksi

document.addEventListener("DOMContentLoaded", () => {
    loadData();
    loadSiklusTanam(); // Panggil fungsi memuat pilihan siklus tanam
});

function resetFormInventaris() {
    document.getElementById("formInventaris").reset();
    document.getElementById("inventarisId").value = "";
    document.getElementById("modalInventarisTitle").textContent = "Tambah Item";
}
// loadData() - GET semua inventaris lalu render ke #tabelInventaris tbody
async function loadData() {
    try {
        const response = await fetch(`${API_INVENTARIS}/index`);
        const result = await response.json();
        
        const tbody = document.querySelector("#tabelInventaris tbody");
        tbody.innerHTML = "";

        if (result.status === 'success' && result.data.length > 0) {
            result.data.forEach((item, index) => {
                tbody.innerHTML += `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${item.nama}</td>
                        <td>${item.kategori}</td>
                        <td>${item.stok} ${item.satuan}</td>
                        <td>Rp ${parseFloat(item.harga_satuan).toLocaleString('id-ID')}</td>
                        <td>
                            <button class="btn btn-sm btn-info" onclick="editData(${item.id})">Edit</button>
                            <button class="btn btn-sm btn-danger" onclick="hapusData(${item.id})">Hapus</button>
                            <button class="btn btn-sm btn-success" onclick="bukaModalTransaksi(${item.id}, '${item.nama}')">+ Transaksi</button>
                        </td>
                    </tr>
                `;
            });
        } else {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center">Belum ada data inventaris.</td></tr>`;
        }
    } catch (error) {
        console.error("Gagal memuat data:", error);
    }
}
// simpanData(event) - POST/PUT berdasarkan #inventarisId
document.getElementById("formInventaris").addEventListener("submit", async function simpanData(event) {
    event.preventDefault();
    
    const id = document.getElementById("inventarisId").value;
    const data = {
        nama: document.getElementById("inventarisNama").value,
        kategori: document.getElementById("inventarisKategori").value,
        stok: document.getElementById("inventarisStok").value,
        satuan: document.getElementById("inventarisSatuan").value,
        harga_satuan: document.getElementById("inventarisHargaSatuan").value,
    };

    const url = id ? `${API_INVENTARIS}/update/${id}` : `${API_INVENTARIS}/create`;

    try {
        const response = await fetch(url, {
            method: "POST", // Controller diset menerima format POST JSON Payload
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(data)
        });
        const result = await response.json();

        if (result.status === "success") {
            alert(result.message);
            bootstrap.Modal.getInstance(document.getElementById("modalInventaris")).hide();
            loadData(); // Refresh tabel otomatis
        } else {
            alert(result.message);
        }
    } catch (error) {
        console.error("Gagal menyimpan data:", error);
    }
});
// editData(id) - ambil satu data, isi form modal
async function editData(id) {
    try {
        const response = await fetch(`${API_INVENTARIS}/show/${id}`);
        const result = await response.json();

        if (result.status === 'success') {
            const item = result.data;
            document.getElementById("inventarisId").value = item.id;
            document.getElementById("inventarisNama").value = item.nama;
            document.getElementById("inventarisKategori").value = item.kategori;
            document.getElementById("inventarisStok").value = item.stok;
            document.getElementById("inventarisSatuan").value = item.satuan;
            document.getElementById("inventarisHargaSatuan").value = item.harga_satuan;

            document.getElementById("modalInventarisTitle").textContent = "Edit Item";
            new bootstrap.Modal(document.getElementById("modalInventaris")).show();
        } else {
            alert(result.message);
        }
    } catch (error) {
        console.error("Gagal mengambil data edit:", error);
    }
}
// hapusData(id) - konfirmasi lalu DELETE
async function hapusData(id) {
    if (confirm("Apakah yakin ingin menghapus item ini? Semua riwayat transaksi barang ini juga akan terhapus otomatis (CASCADE).")) {
        try {
            const response = await fetch(`${API_INVENTARIS}/delete/${id}`, {
                method: 'POST'
            });
            const result = await response.json();

            if (result.status === 'success') {
                alert(result.message);
                loadData();
            } else {
                alert(result.message);
            }
        } catch (error) {
            console.error("Gagal menghapus data:", error);
        }
    }
}

// ========================================================
// 5. LOGIKA TRANSAKSI INVENTARIS (MASUK / KELUAR)
// ========================================================

async function loadSiklusTanam() {
    try {
        const response = await fetch(`${API_SIKLUS}/index`); 
        const result = await response.json();

        const select = document.getElementById('transaksiSiklusId');
        if(!select) return; 

        select.innerHTML = '<option value="">-- Pilih Siklus Tanam --</option>';

        if (result.success && result.data.length > 0) {
            result.data.forEach(siklus => {
                if(siklus.status === 'Aktif') {
                    select.innerHTML += `<option value="${siklus.id}">${siklus.nama_tanaman}</option>`;
                }
            });
        }
    } catch (error) {
        console.error('Gagal mengambil data siklus:', error);
    }
}

function bukaModalTransaksi(id, nama) {
    document.getElementById('formTransaksi').reset();
    document.getElementById('transaksiInventarisId').value = id;
    document.getElementById('transaksiNamaItem').value = nama;
    
    // Sembunyikan dan reset required pada siklus tanam secara default
    document.getElementById('grupSiklusTanam').style.display = 'none';
    document.getElementById('transaksiSiklusId').required = false;

    new bootstrap.Modal(document.getElementById('modalTransaksi')).show();
}

// Logika Tampilkan Dropdown Siklus Tanam jika transaksi = 'Keluar'
document.getElementById('transaksiJenis')?.addEventListener('change', function() {
    const grupSiklus = document.getElementById('grupSiklusTanam');
    const inputSiklus = document.getElementById('transaksiSiklusId');

    if (this.value === 'Keluar') {
        grupSiklus.style.display = 'block';
        inputSiklus.required = true;
    } else {
        grupSiklus.style.display = 'none';
        inputSiklus.required = false;
        inputSiklus.value = ''; 
    }
});

// Submit Data Transaksi
document.getElementById('formTransaksi')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const data = {
        inventaris_id: document.getElementById('transaksiInventarisId').value,
        jenis: document.getElementById('transaksiJenis').value,
        tanggal: document.getElementById('transaksiTanggal').value,
        jumlah: document.getElementById('transaksiJumlah').value,
        siklus_id: document.getElementById('transaksiSiklusId').value
    };

    try {
        const response = await fetch(`${API_INVENTARIS}/createTransaksi`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await response.json();

        if (result.status === 'success') {
            alert('Transaksi berhasil dicatat!');
            bootstrap.Modal.getInstance(document.getElementById('modalTransaksi')).hide();
            loadData(); // Update sisa stok di tabel utama
        } else {
            alert(result.message);
        }
    } catch (error) {
        console.error('Error save transaksi:', error);
    }
});