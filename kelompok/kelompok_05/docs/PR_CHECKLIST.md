# PR_CHECKLIST (review internal ke `staging`, dan sebelum PR final ke upstream)

Template PR resmi ada di `.github/` dan tidak boleh diubah. Checklist ini dipakai internal.

## Penulis PR
- [ ] Branch bernama `feat/kelompok-XX-<fitur>` dan dibuat dari `staging`
- [ ] Hanya menyentuh folder fiturnya sendiri
- [ ] `php tools/cek_aturan.php` hasilnya OK
- [ ] Sudah diuji manual untuk tiap peran yang relevan, termasuk input salah
- [ ] Penggunaan AI sudah dicatat di `docs/AI_LOG.md`
- [ ] Saya bisa menjelaskan setiap bagian kode ini secara lisan

## Reviewer (PIC)
- [ ] Tidak ada composer.json, package.json, vendor/, node_modules/, atau kode framework
- [ ] Tidak ada nama tabel, kolom, fungsi, atau URL di luar kontrak
- [ ] Query memakai PDO prepared statements, output lewat `e()`, form POST memakai CSRF
- [ ] Cek peran dilakukan di server
- [ ] Tidak ada file di atas 5 MB atau file yang tidak perlu (zip, video, installer)
- [ ] Tidak ada rahasia (password nyata, config.local.php) yang ikut ter-commit

## Sebelum PR final ke upstream (hanya PIC)
- [ ] `git diff --stat upstream/main` hanya menunjukkan `kelompok/kelompok_XX/`
- [ ] PHP lokal 8.2 (`php -v`), semua file lolos lint
- [ ] Instalasi bersih diuji di laptop lain (salin, impor SQL, buka browser)
- [ ] README kelompok sudah lengkap

## Konvensi commit
Ikuti contoh dari panduan asisten: `jenis(kelompok_XX): ringkasan singkat`, misalnya
`feat(kelompok_XX): form pemeriksaan dinamis dari requirements`.
Jenis: `feat`, `fix`, `docs`, `refactor`. Satu commit = satu perubahan yang jelas.
Selalu `git add kelompok/kelompok_XX/` (folder sendiri), jangan `git add .` atau `git add -A`.
Pastikan `git config user.name` dan `user.email` benar dan email terdaftar di akun GitHub masing-masing.
