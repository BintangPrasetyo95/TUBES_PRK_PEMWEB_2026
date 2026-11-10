function renderAspirasi(container) {
  container.innerHTML = `
    <section class="max-w-xl mx-auto p-6 bg-white rounded-xl shadow">
      <h2 class="text-2xl font-bold mb-4">Ruang Aspirasi Warga</h2>
      <form id="form-aspirasi" class="space-y-4">
        <input name="nama_pengirim" required placeholder="Nama"
          class="w-full border rounded p-2">
        <input name="email" type="email" required placeholder="Email"
          class="w-full border rounded p-2">
        <textarea name="pesan_aspirasi" required rows="4"
          placeholder="Tulis aspirasimu..."
          class="w-full border rounded p-2"></textarea>
        <button class="bg-blue-600 text-white px-4 py-2 rounded">Kirim</button>
        <p id="aspirasi-notif" class="text-sm"></p>
      </form>
    </section>`;

  document.getElementById('form-aspirasi').addEventListener('submit', async (e) => {
    e.preventDefault();
    const body = Object.fromEntries(new FormData(e.target));
    const notif = document.getElementById('aspirasi-notif');
    try {
      const res = await fetch('api/aspirasi.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body)
      });
      const json = await res.json();
      notif.textContent = json.message;
      if (json.status === 'success') e.target.reset();
    } catch {
      notif.textContent = 'Terjadi kesalahan jaringan';
    }
  });
}