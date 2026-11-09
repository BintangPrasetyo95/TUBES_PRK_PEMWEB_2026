
(function () {
    'use strict';

    const input = document.getElementById('search');
    const list = document.getElementById('library-list');
    const status = document.getElementById('search-status');

    if (!input || !list || !status) return;

    async function muatData() {
        status.textContent = 'Mencari...';

        try {
            const hasil = await CEKHAK.api.get(
                'modules/pustaka/api/search.php',
                { search: input.value.trim() }
            );

            if (hasil.status !== 'success') {
                throw new Error(hasil.message || 'Pencarian gagal.');
            }

            list.textContent = '';

            (hasil.data || []).forEach(function (item) {
                const card = document.createElement('article');
                card.className = 'card';

                const title = document.createElement('h2');
                title.textContent = item.jenis;

                const description = document.createElement('p');
                description.textContent = item.deskripsi || '';

                const detail = document.createElement('a');
                detail.href = 'detail.php?id=' + encodeURIComponent(item.type_id);
                detail.textContent = 'Lihat Detail';

                card.append(title, description, detail);
                list.appendChild(card);
            });

            status.textContent = (hasil.data || []).length
                ? 'Hasil pencarian diperbarui.'
                : 'Tidak ada hasil pencarian.';

        } catch (error) {
            status.textContent = error.message || 'Terjadi kesalahan.';
        }
    }

    const cariDenganJeda = CEKHAK.ui.debounce(muatData, 300);

    input.addEventListener('input', cariDenganJeda);
})();
