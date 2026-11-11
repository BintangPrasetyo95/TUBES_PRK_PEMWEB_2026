
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const input = document.getElementById('search');
        const filter = document.getElementById('type-filter');
        const reset = document.getElementById('reset-filter');
        const list = document.getElementById('library-list');
        const status = document.getElementById('search-status');

        if (!input || !filter || !reset || !list || !status) {
            console.error('Elemen filter Pustaka tidak ditemukan.');
            return;
        }

        async function muatData() {
            status.textContent = 'Memuat data...';

            const params = new URLSearchParams({
                search: input.value.trim(),
                type_id: filter.value
            });

            try {
                const response = await fetch(
                    CEKHAK.url('modules/pustaka/api/search.php') +
                    '?' + params.toString(),
                    {
                        method: 'GET',
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'fetch'
                        },
                        credentials: 'same-origin'
                    }
                );

                const hasil = await response.json();

                if (!response.ok || hasil.status !== 'success') {
                    throw new Error(hasil.message || 'Gagal memuat data.');
                }

                list.textContent = '';

                const kelompok = new Map();

                (hasil.data || []).forEach(function (item) {
                    if (!kelompok.has(String(item.type_id))) {
                        kelompok.set(String(item.type_id), item);
                    }
                });

                kelompok.forEach(function (item) {
                    const card = document.createElement('article');
                    card.className = 'card';

                    const title = document.createElement('h3');
                    title.textContent = item.jenis;

                    const description = document.createElement('p');
                    description.textContent =
                        item.deskripsi || 'Belum ada deskripsi.';

                    const detail = document.createElement('a');
                    detail.href = CEKHAK.url(
                        'modules/pustaka/detail.php?id=' +
                        encodeURIComponent(item.type_id)
                    );
                    detail.className = 'btn';
                    detail.textContent = 'Lihat Detail';

                    card.append(title, description, detail);
                    list.appendChild(card);
                });

                status.textContent = kelompok.size
                    ? kelompok.size + ' jenis dokumen ditemukan.'
                    : 'Tidak ada hasil yang ditemukan.';

            } catch (error) {
                console.error(error);
                status.textContent = error.message ||
                    'Terjadi kesalahan saat memuat data.';
            }
        }

        input.addEventListener('input', function () {
            muatData();
        });

        filter.addEventListener('change', function () {
            muatData();
        });

        reset.addEventListener('click', function () {
            input.value = '';
            filter.value = '0';
            muatData();
        });
    });
})();
