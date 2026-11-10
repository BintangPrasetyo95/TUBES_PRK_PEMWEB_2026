// Manajemen Penilaian Terpadu
(function () {
    const $ = id => document.getElementById(id);
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const URL_API = 'api/penilaian.php';
    const JENIS = ['Tugas', 'Kuis', 'UTS', 'UAS'];

    let idPenugasan = '';
    let daftar = [];
    let editingId = null;
    let idNilaiAktif = null;

    async function req(url, method = 'GET', data = null) {
        const options = { method, headers: { 'X-CSRF-Token': csrfToken } };
        if (data !== null) {
            options.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(data);
        }
        const response = await fetch(url, options);
        if (response.status === 401) { location.href = 'login.php'; throw new Error('Sesi berakhir.'); }
        let result;
        try { result = await response.json(); }
        catch { throw new Error('Respons server tidak valid. Periksa instalasi PHP dan database.'); }
        if (!response.ok) throw new Error(result.message || 'Permintaan gagal.');
        return result;
    }

    function info(message, error = false) {
        const box = $('notice');
        box.hidden = false;
        box.className = 'message ' + (error ? 'error' : 'success');
        box.textContent = message;
    }

    function cell(text, className) {
        const td = document.createElement('td');
        td.textContent = text;
        if (className) td.className = className;
        return td;
    }

    function emptyRow(body, span, text) {
        const tr = document.createElement('tr');
        const td = cell(text, 'empty');
        td.colSpan = span;
        tr.append(td);
        body.append(tr);
    }

    function fmt(value) {
        return value === null || value === undefined ? '—' : Number(value).toString();
    }

    /* ---------- Konteks (Kelas, Mapel, Periode) ---------- */
    async function loadKonteks() {
        const result = await req(URL_API + '?aksi=konteks');
        const { kelas = [], mapel = [], periode = [] } = result.data || {};
        const collator = new Intl.Collator('id', { numeric: true, sensitivity: 'base' });

        // Urutkan Kelas (X -> XI -> XII lalu nama kelas secara natural)
        const bobotTingkat = t => ({ X: 1, '10': 1, XI: 2, '11': 2, XII: 3, '12': 3 }[String(t).toUpperCase()] || 9);
        kelas.sort((a, b) => bobotTingkat(a.tingkat) - bobotTingkat(b.tingkat) || collator.compare(a.nama_kelas, b.nama_kelas));

        // Urutkan Mata Pelajaran (A - Z)
        mapel.sort((a, b) => collator.compare(a.nama_mapel, b.nama_mapel));

        const bobotSem = s => (String(s).toLowerCase() === 'genap' ? 1 : 2);
        periode.sort((a, b) => collator.compare(b.tahun_ajaran, a.tahun_ajaran) || bobotSem(a.semester) - bobotSem(b.semester));

        const selKelas = $('pn-kelas');
        const selMapel = $('pn-mapel');
        const selPeriode = $('pn-periode');

        const prevKelas = selKelas.value;
        const prevMapel = selMapel.value;
        const prevPeriode = selPeriode.value;

        selKelas.replaceChildren(new Option(kelas.length ? 'Pilih kelas' : 'Belum ada kelas', ''));
        kelas.forEach(k => selKelas.add(new Option(k.nama_kelas, k.id_kelas)));

        selMapel.replaceChildren(new Option(mapel.length ? 'Pilih mata pelajaran' : 'Belum ada mapel', ''));
        mapel.forEach(m => selMapel.add(new Option(m.nama_mapel, m.id_mapel)));

        selPeriode.replaceChildren(new Option(periode.length ? 'Pilih periode' : 'Belum ada periode', ''));
        let defaultPeriode = '';
        periode.forEach(p => {
            const aktif = Number(p.status_aktif) === 1;
            if (aktif && !defaultPeriode) defaultPeriode = String(p.id_periode);
            selPeriode.add(new Option(p.tahun_ajaran + ' · ' + p.semester + (aktif ? ' (aktif)' : ''), p.id_periode));
        });

        selKelas.value = prevKelas;
        selMapel.value = prevMapel;
        selPeriode.value = prevPeriode || defaultPeriode || (periode[0] ? String(periode[0].id_periode) : '');
    }

    async function pilihKonteks() {
        const id_kelas = $('pn-kelas').value;
        const id_mapel = $('pn-mapel').value;
        const id_periode = $('pn-periode').value;

        if (!id_kelas || !id_mapel || !id_periode) {
            idPenugasan = '';
            await refresh();
            return;
        }
        try {
            const res = await req(URL_API + '?aksi=siapkan_konteks', 'POST', { id_kelas, id_mapel, id_periode });
            idPenugasan = String(res.data.id_penugasan);
            await refresh();
        } catch (error) {
            idPenugasan = '';
            await refresh();
            info(error.message, true);
        }
    }

    async function refresh() {
        const show = idPenugasan !== '';
        ['pn-daftar-panel', 'pn-rekap-panel'].forEach(id => $(id).hidden = !show);
        $('pn-add').hidden = !show;
        tutupNilai();
        if (!show) { $('pn-count').textContent = '—'; return; }
        const [d, r] = await Promise.all([
            req(URL_API + '?aksi=daftar&id_penugasan=' + idPenugasan),
            req(URL_API + '?aksi=rekap&id_penugasan=' + idPenugasan)
        ]);
        daftar = d.data;
        renderDaftar();
        renderRekap(r.data);
    }

    /* ---------- Daftar penilaian ---------- */
    function renderDaftar() {
        const body = $('pn-body');
        body.replaceChildren();
        $('pn-count').textContent = daftar.length + ' penilaian';
        if (!daftar.length) { emptyRow(body, 6, 'Belum ada penilaian. Mulai dengan menambahkan penilaian baru.'); return; }
        daftar.forEach((item, i) => {
            const tr = document.createElement('tr');
            tr.append(
                cell(i + 1), cell(item.judul_penilaian), cell(item.jenis_komponen),
                cell(item.terisi + ' / ' + item.jumlah_siswa), cell(fmt(item.rata_rata))
            );
            const aksi = document.createElement('td');
            aksi.className = 'actions';
            const nilai = document.createElement('button');
            nilai.type = 'button'; nilai.textContent = 'Input nilai';
            nilai.onclick = () => bukaNilai(item.id_penilaian);
            const edit = document.createElement('button');
            edit.type = 'button'; edit.textContent = 'Edit';
            edit.onclick = () => bukaForm(item);
            const hapus = document.createElement('button');
            hapus.type = 'button'; hapus.textContent = 'Hapus'; hapus.className = 'danger';
            hapus.onclick = async () => {
                if (!confirm('Hapus penilaian ini beserta seluruh nilai siswanya?')) return;
                hapus.disabled = true;
                try {
                    const res = await req(URL_API + '?id=' + item.id_penilaian, 'DELETE');
                    await refresh();
                    info(res.message || 'Penilaian berhasil dihapus.');
                } catch (error) { info(error.message, true); hapus.disabled = false; }
            };
            aksi.append(nilai, edit, hapus);
            tr.append(aksi);
            body.append(tr);
        });
    }

    /* ---------- Rekap per komponen ---------- */
    function renderRekap(rows) {
        const body = $('pn-rekap-body');
        body.replaceChildren();
        if (!rows.length) { emptyRow(body, 7, 'Belum ada siswa pada kelas ini.'); return; }
        rows.forEach((s, i) => {
            const tr = document.createElement('tr');
            tr.append(cell(i + 1), cell(s.nisn), cell(s.nama_siswa), ...JENIS.map(j => cell(fmt(s[j]))));
            body.append(tr);
        });
    }

    /* ---------- Form tambah / edit penilaian ---------- */
    function bukaForm(item = null) {
        editingId = item ? item.id_penilaian : null;
        $('pn-dialog-title').textContent = item ? 'Edit penilaian' : 'Tambah penilaian';
        $('pn-form-error').textContent = '';
        const form = $('pn-form');
        form.elements.judul_penilaian.value = item ? item.judul_penilaian : '';
        form.elements.jenis_komponen.value = item ? item.jenis_komponen : '';
        $('pn-dialog').showModal();
    }

    async function submitForm(event) {
        event.preventDefault();
        const button = event.target.querySelector('[type="submit"]');
        button.disabled = true;
        try {
            const data = Object.fromEntries(new FormData(event.target));
            data.judul_penilaian = data.judul_penilaian.trim();
            if (!data.judul_penilaian) throw new Error('Judul penilaian wajib diisi.');
            if (!editingId) data.id_penugasan = idPenugasan;
            const res = await req(URL_API + (editingId ? '?id=' + editingId : ''), editingId ? 'PUT' : 'POST', data);
            $('pn-dialog').close();
            await refresh();
            info(res.message || 'Penilaian berhasil disimpan.');
        } catch (error) { $('pn-form-error').textContent = error.message; }
        finally { button.disabled = false; }
    }

    /* ---------- Input nilai satu kelas ---------- */
    async function bukaNilai(id) {
        try {
            const res = await req(URL_API + '?aksi=nilai&id_penilaian=' + id);
            idNilaiAktif = id;
            const { penilaian, siswa } = res.data;
            $('pn-nilai-title').textContent = penilaian.judul_penilaian + ' (' + penilaian.jenis_komponen + ')';
            $('pn-nilai-error').textContent = '';
            const body = $('pn-nilai-body');
            body.replaceChildren();
            if (!siswa.length) emptyRow(body, 4, 'Belum ada siswa pada kelas ini.');
            siswa.forEach((s, i) => {
                const tr = document.createElement('tr');
                const td = document.createElement('td');
                const input = document.createElement('input');
                input.type = 'number'; input.min = 0; input.max = 100; input.step = '0.01';
                input.style.maxWidth = '120px';
                input.style.marginTop = '0';
                input.name = 's_' + s.id_siswa;
                input.value = s.nilai === null ? '' : Number(s.nilai);
                input.setAttribute('aria-label', 'Nilai ' + s.nama_siswa);
                td.append(input);
                tr.append(cell(i + 1), cell(s.nisn), cell(s.nama_siswa), td);
                body.append(tr);
            });
            $('pn-nilai-panel').hidden = false;
            $('pn-nilai-panel').scrollIntoView({ behavior: 'smooth', block: 'start' });
        } catch (error) { info(error.message, true); }
    }

    function tutupNilai() {
        idNilaiAktif = null;
        $('pn-nilai-panel').hidden = true;
        $('pn-nilai-body').replaceChildren();
    }

    async function submitNilai(event) {
        event.preventDefault();
        const button = event.target.querySelector('[type="submit"]');
        $('pn-nilai-error').textContent = '';
        const data = {};
        for (const input of event.target.querySelectorAll('input[name^="s_"]')) {
            const v = input.value.trim();
            if (v !== '' && (Number.isNaN(Number(v)) || Number(v) < 0 || Number(v) > 100)) {
                input.focus();
                $('pn-nilai-error').textContent = 'Nilai harus berupa angka antara 0 sampai 100.';
                return;
            }
            data[input.name] = v;
        }
        button.disabled = true;
        try {
            const res = await req(URL_API + '?aksi=simpan_nilai&id_penilaian=' + idNilaiAktif, 'POST', data);
            await refresh();
            info(res.message || 'Nilai berhasil disimpan.');
        } catch (error) { $('pn-nilai-error').textContent = error.message; }
        finally { button.disabled = false; }
    }

    /* ---------- Inisialisasi ---------- */
    async function init() {
        try {
            await loadKonteks();
            ['pn-kelas', 'pn-mapel', 'pn-periode'].forEach(id => {
                $(id).addEventListener('change', pilihKonteks);
            });
            window.addEventListener('focus', () => { loadKonteks().catch(() => { }); });
            $('pn-add').onclick = () => bukaForm();
            ['pn-dialog-x', 'pn-dialog-batal'].forEach(id => $(id).onclick = () => $('pn-dialog').close());
            $('pn-form').onsubmit = submitForm;
            $('pn-nilai-form').onsubmit = submitNilai;
            $('pn-nilai-tutup').onclick = tutupNilai;
        } catch (error) { info(error.message, true); }
    }
    init();
})();