
function renderHoaks(contentArea) {
    // Menampilkan halaman form Lapor Hoaks
    contentArea.innerHTML = `
        <section class="max-w-3xl mx-auto">

            <header class="mb-6 pb-4 border-b border-gray-200">
                <span class="text-sm font-semibold text-blue-700 uppercase tracking-wider">
                    CiviLens - Ruang Publik
                </span>

                <h1 class="text-2xl font-bold text-gray-900 mt-2">
                    Laporkan Informasi Hoaks
                </h1>

                <p class="text-gray-600 text-sm mt-2">
                    Temukan informasi yang diduga mengandung hoaks?
                    Bantu masyarakat dengan melaporkannya melalui formulir berikut.
                </p>
            </header>

            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <p class="text-sm text-blue-800">
                    <strong>Informasi:</strong>
                    Setiap laporan akan diperiksa terlebih dahulu oleh admin
                    sebelum ditentukan sebagai fakta atau hoaks.
                </p>
            </div>

            <form id="form-lapor-hoaks"
                class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm space-y-5">

                <div>
                    <label for="judul_isu"
                        class="block text-sm font-semibold text-gray-700 mb-2">
                        Judul Isu / Informasi
                    </label>

                    <input
                        type="text"
                        id="judul_isu"
                        name="judul_isu"
                        maxlength="255"
                        required
                        placeholder="Masukkan judul informasi yang diduga hoaks"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label for="link_sumber"
                        class="block text-sm font-semibold text-gray-700 mb-2">
                        Link Sumber Informasi
                    </label>

                    <input
                        type="url"
                        id="link_sumber"
                        name="link_sumber"
                        maxlength="255"
                        required
                        placeholder="https://contoh.com/informasi"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">

                    <p class="text-xs text-gray-500 mt-1">
                        Masukkan tautan berita, artikel, atau unggahan yang dilaporkan.
                    </p>
                </div>

                <div>
                    <label for="keterangan"
                        class="block text-sm font-semibold text-gray-700 mb-2">
                        Keterangan Laporan
                    </label>

                    <textarea
                        id="keterangan"
                        name="keterangan"
                        rows="5"
                        required
                        placeholder="Jelaskan alasan informasi tersebut diduga hoaks..."
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>

                <div id="hoaks-message"
                    class="hidden p-3 rounded-lg text-sm"
                    role="alert"
                    aria-live="polite">
                </div>

                <button
                    type="submit"
                    id="btn-submit-hoaks"
                    class="w-full bg-blue-700 hover:bg-blue-800 text-white font-semibold py-3 rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed">

                    Kirim Laporan
                </button>

            </form>

        </section>
    `;

    // Mengambil elemen form
    const form = document.getElementById('form-lapor-hoaks');
    const message = document.getElementById('hoaks-message');
    const submitButton = document.getElementById('btn-submit-hoaks');

    // Fungsi menampilkan notifikasi
    function showMessage(text, success) {
        message.textContent = text;
        message.classList.remove(
            'hidden',
            'bg-green-50',
            'text-green-800',
            'border-green-200',
            'bg-red-50',
            'text-red-800',
            'border-red-200'
        );

        if (success) {
            message.classList.add(
                'bg-green-50',
                'text-green-800',
                'border',
                'border-green-200'
            );
        } else {
            message.classList.add(
                'bg-red-50',
                'text-red-800',
                'border',
                'border-red-200'
            );
        }
    }

    // Menangani pengiriman form
    form.addEventListener('submit', async function(event) {
        event.preventDefault();

        // Mengambil nilai input
        const judul = document.getElementById('judul_isu').value.trim();
        const link = document.getElementById('link_sumber').value.trim();
        const keterangan = document.getElementById('keterangan').value.trim();

        // Validasi input
        if (!judul || !link || !keterangan) {
            showMessage('Semua kolom wajib diisi.', false);
            return;
        }

        // Validasi URL
        try {
            const parsedUrl = new URL(link);

            if (!['http:', 'https:'].includes(parsedUrl.protocol)) {
                throw new Error('URL tidak valid');
            }
        } catch (error) {
            showMessage('Masukkan link sumber yang valid.', false);
            return;
        }

        // Mengubah tombol menjadi loading
        submitButton.disabled = true;
        submitButton.textContent = 'Mengirim laporan...';

        try {
            // Mengirim data ke backend menggunakan Fetch API
            const response = await fetch('api/hoaks.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    judul_isu: judul,
                    link_sumber: link,
                    keterangan: keterangan
                })
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(
                    result.message || 'Gagal mengirim laporan.'
                );
            }

            // Menampilkan notifikasi berhasil
            showMessage(
                result.message + ' Status laporan: ' + result.data.status,
                true
            );

            // Mengosongkan form
            form.reset();

        } catch (error) {
            showMessage(
                error.message || 'Terjadi kesalahan saat mengirim laporan.',
                false
            );

        } finally {
            // Mengaktifkan kembali tombol
            submitButton.disabled = false;
            submitButton.textContent = 'Kirim Laporan';
        }
    });
}
