// assets/js/pratinjau.js
// Skrip sederhana untuk Mode Pratinjau Pengajar (menilai di browser tanpa menyimpan)

document.addEventListener('DOMContentLoaded', function () {
  var wadahKuis = document.getElementById('wadahKuis');
  if (!wadahKuis) {
    return;
  }

  var daftarKartu = document.querySelectorAll('.kartu-soal-pratinjau');
  var totalSoal = parseInt(wadahKuis.getAttribute('data-total-soal') || '0', 10);
  var durasiMenit = parseInt(wadahKuis.getAttribute('data-durasi-menit') || '15', 10);

  var teksProgress = document.getElementById('teksProgress');
  var progressFill = document.getElementById('progressFill');
  var timerDisplay = document.getElementById('timerDisplay');
  var wadahTimer = document.getElementById('wadahTimer');

  var tombolNomor = document.querySelectorAll('.tombol-nomor-pratinjau');
  var kartuHasil = document.getElementById('kartuHasilPratinjau');
  var hasilPersen = document.getElementById('hasilPersenPratinjau');
  var hasilRincian = document.getElementById('hasilRincianPratinjau');
  var tombolSelesai = document.getElementById('tombolSelesaiPratinjau');

  // Catatan jawaban sementara selama pratinjau
  var sudahDijawab = {};
  var jumlahTerjawab = 0;
  var jumlahBenar = 0;
  var skorTotal = 0;
  var skorMaksimal = 0;

  // Hitung skor maksimal dari semua soal
  daftarKartu.forEach(function (kartu) {
    skorMaksimal += parseInt(kartu.getAttribute('data-poin') || '0', 10);
  });

  // 1. Fungsi untuk pindah ke kartu soal tertentu
  function bukaSoal(indexTujuan) {
    daftarKartu.forEach(function (kartu, idx) {
      if (idx === indexTujuan) {
        kartu.removeAttribute('hidden');
      } else {
        kartu.setAttribute('hidden', 'hidden');
      }
    });

    if (teksProgress) {
      teksProgress.textContent = 'Soal ' + (indexTujuan + 1) + ' dari ' + totalSoal;
    }

    // Tandai tombol nomor yang sedang dibuka
    tombolNomor.forEach(function (btn) {
      var target = parseInt(btn.getAttribute('data-target') || '0', 10);
      btn.classList.remove('btn-accent');

      if (target === indexTujuan) {
        btn.classList.remove('btn-outline');
        btn.classList.add('btn-accent');
      } else if (!sudahDijawab[target]) {
        btn.classList.add('btn-outline');
      }
    });
  }

  // 2. Fungsi untuk tampilkan ringkasan hasil di akhir pratinjau
  function tampilkanHasil() {
    wadahKuis.setAttribute('hidden', 'hidden');
    if (teksProgress) {
      teksProgress.setAttribute('hidden', 'hidden');
    }

    var grid = document.getElementById('gridNomorSoal');
    if (grid && grid.parentElement) {
      grid.parentElement.setAttribute('hidden', 'hidden');
    }

    var jumlahSalah = totalSoal - jumlahBenar;
    var persen = skorMaksimal > 0 ? Math.round((skorTotal / skorMaksimal) * 100) : 0;

    if (hasilPersen) {
      hasilPersen.textContent = persen + '%';
    }
    if (hasilRincian) {
      hasilRincian.textContent =
        'Skor: ' + skorTotal + ' / ' + skorMaksimal +
        ' | Benar: ' + jumlahBenar +
        ' | Salah: ' + jumlahSalah;
    }
    if (kartuHasil) {
      kartuHasil.removeAttribute('hidden');
    }
  }

  // 3. Saat pengajar mengklik pilihan jawaban (A - E)
  var semuaOpsi = document.querySelectorAll('.tombol-opsi-pratinjau');
  semuaOpsi.forEach(function (tombol) {
    tombol.addEventListener('click', function () {
      var soalIndex = parseInt(tombol.getAttribute('data-soal-index') || '0', 10);

      // Kalau soal ini sudah dijawab, tidak bisa diganti lagi
      if (sudahDijawab[soalIndex]) {
        return;
      }

      sudahDijawab[soalIndex] = true;
      jumlahTerjawab++;

      var kartu = document.getElementById('kartu-soal-' + soalIndex);
      var poin = parseInt(kartu.getAttribute('data-poin') || '0', 10);
      var pembahasan = kartu.getAttribute('data-pembahasan') || '';
      var apakahBenar = tombol.getAttribute('data-benar') === '1';

      if (apakahBenar) {
        jumlahBenar++;
        skorTotal += poin;
      }

      // Warnai dan kunci tombol pilihan di soal ini memakai class dari komponen.css
      var labelKunci = '';
      var opsiSoal = kartu.querySelectorAll('.tombol-opsi-pratinjau');
      opsiSoal.forEach(function (btn) {
        btn.disabled = true;
        btn.classList.remove('btn-outline');

        if (btn.getAttribute('data-benar') === '1') {
          labelKunci = btn.getAttribute('data-label') || '';
          btn.classList.add('btn-primary');
        } else if (btn === tombol && !apakahBenar) {
          btn.classList.add('btn-danger');
        } else {
          btn.classList.add('btn-batal');
        }
      });

      // Tampilkan kotak umpan balik & pembahasan
      var kotakUmpan = document.getElementById('umpan-balik-' + soalIndex);
      if (kotakUmpan) {
        var judulUmpan = kotakUmpan.querySelector('.judul-umpan-balik');
        var isiPembahasan = kotakUmpan.querySelector('.isi-pembahasan');

        kotakUmpan.classList.remove('alert-sukses', 'alert-galat');
        if (apakahBenar) {
          kotakUmpan.classList.add('alert-sukses');
          judulUmpan.textContent = 'Benar! (+' + poin + ' poin)';
        } else {
          kotakUmpan.classList.add('alert-galat');
          judulUmpan.textContent = 'Belum tepat. Jawaban yang benar: ' + labelKunci;
        }

        isiPembahasan.textContent = pembahasan !== '' ? 'Pembahasan: ' + pembahasan : 'Tidak ada pembahasan.';
        kotakUmpan.removeAttribute('hidden');
      }

      // Update progress bar
      if (progressFill && totalSoal > 0) {
        var persenProgress = Math.round((jumlahTerjawab / totalSoal) * 100);
        progressFill.style.width = persenProgress + '%';
      }

      // Tandai tombol nomor soal sudah dijawab
      tombolNomor.forEach(function (btnNomor) {
        if (parseInt(btnNomor.getAttribute('data-target') || '0', 10) === soalIndex) {
          btnNomor.classList.remove('btn-outline', 'btn-accent');
          btnNomor.classList.add('btn-primary');
        }
      });
    });
  });

  // 4. Tombol "Soal Berikutnya"
  var tombolLanjut = document.querySelectorAll('.tombol-lanjut-pratinjau');
  tombolLanjut.forEach(function (tombol) {
    tombol.addEventListener('click', function () {
      var target = parseInt(tombol.getAttribute('data-target') || '0', 10);
      bukaSoal(target);
    });
  });

  // 5. Tombol Grid Nomor Soal
  tombolNomor.forEach(function (tombol) {
    tombol.addEventListener('click', function () {
      var target = parseInt(tombol.getAttribute('data-target') || '0', 10);
      bukaSoal(target);
    });
  });

  // 6. Tombol "Lihat Hasil Pratinjau"
  if (tombolSelesai) {
    tombolSelesai.addEventListener('click', function () {
      tampilkanHasil();
    });
  }

  // 7. Timer hitung mundur sederhana
  var sisaDetik = durasiMenit * 60;
  var hitungMundur = setInterval(function () {
    sisaDetik--;
    if (sisaDetik <= 0) {
      clearInterval(hitungMundur);
      if (timerDisplay) {
        timerDisplay.textContent = '00:00';
      }
      tampilkanHasil();
      return;
    }

    var menit = Math.floor(sisaDetik / 60);
    var detik = sisaDetik % 60;
    if (timerDisplay) {
      timerDisplay.textContent = (menit < 10 ? '0' : '') + menit + ':' + (detik < 10 ? '0' : '') + detik;
    }

    // Warnai timer pada 1 menit terakhir sesuai komponen.css (.timer-peringatan)
    if (sisaDetik <= 60 && wadahTimer) {
      wadahTimer.classList.add('timer-peringatan');
    }
  }, 1000);

  // Buka soal pertama saat halaman siap
  bukaSoal(0);
});
