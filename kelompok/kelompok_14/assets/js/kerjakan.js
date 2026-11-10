// ============================================================================
// Skrip Interaksi Kerjakan Kuis (assets/js/kerjakan.js)
// Kelompok 14 - Tugas Besar Praktikum Pemrograman Web 2026
// PIC: Wulan (Sesi 6 - kerjakan.js)
//
// Alur Kerja & Fitur (KRJ-02 s.d. KRJ-07, AC-12, AC-13, AC-14, REL-02, REL-03):
// 1. Timer hitung mundur disinkronkan dengan server (KRJ-03)
// 2. Navigasi kartu soal satu per layar dan grid nomor soal (KRJ-02, KRJ-06)
// 3. Pengiriman jawaban per soal secara asinkron via fetch() ke api/jawab.php (KRJ-04)
// 4. Kunci jawaban dan pembahasan muncul setelah menjawab (KRJ-05, ACC-03)
// 5. Pencegahan kecurangan kirim ganda (409) dan penanganan waktu habis (410)
// 6. Dialog konfirmasi keluar dan dialog konfirmasi selesai kuis (KRJ-07)
// ============================================================================

document.addEventListener('DOMContentLoaded', function () {
  var wadahApp = document.getElementById('appKerjakan');
  if (!wadahApp) {
    return; // Halaman bukan halaman kerjakan kuis
  }

  // --------------------------------------------------------------------------
  // 1. Inisialisasi Data dari Atribut HTML
  // --------------------------------------------------------------------------
  var kuisId = parseInt(wadahApp.getAttribute('data-kuis-id'), 10) || 0;
  var totalSoal = parseInt(wadahApp.getAttribute('data-total-soal'), 10) || 0;
  var sisaDetik = parseInt(wadahApp.getAttribute('data-sisa-detik'), 10) || 0;
  var jumlahTerjawab = parseInt(wadahApp.getAttribute('data-terjawab-awal'), 10) || 0;
  var tokenCsrf = document.getElementById('tokenCsrf') ? document.getElementById('tokenCsrf').value : '';

  var nomorAktif = 1;
  var intervalTimer = null;
  var sedangMengirim = false;

  // Elemen DOM Penting
  var timerDisplay = document.getElementById('timerDisplay');
  var wadahTimer = document.getElementById('wadahTimer');
  var progressFill = document.getElementById('progressFill');
  var angkaTerjawab = document.getElementById('angkaTerjawab');
  var btnSebelumnya = document.getElementById('btnSebelumnya');
  var btnLanjut = document.getElementById('btnLanjut');
  var btnSelesaiCepat = document.getElementById('btnSelesaiCepat');

  var modalKeluar = document.getElementById('modalKeluar');
  var tombolKeluarFokus = document.getElementById('tombolKeluarFokus');
  var btnBatalKeluar = document.getElementById('btnBatalKeluar');

  var modalSelesai = document.getElementById('modalSelesai');
  var btnBatalKumpul = document.getElementById('btnBatalKumpul');
  var btnKonfirmasiKumpul = document.getElementById('btnKonfirmasiKumpul');
  var pesanKonfirmasiSelesai = document.getElementById('pesanKonfirmasiSelesai');
  var peringatanBelumSelesai = document.getElementById('peringatanBelumSelesai');

  // --------------------------------------------------------------------------
  // 2. Fungsi Pembantu: Format Waktu (Detik ke Menit:Detik)
  // --------------------------------------------------------------------------
  function formatWaktu(detik) {
    if (detik < 0) {
      detik = 0;
    }
    var menit = Math.floor(detik / 60);
    var sisa = detik % 60;
    var strMenit = menit < 10 ? '0' + menit : menit;
    var strDetik = sisa < 10 ? '0' + sisa : sisa;
    return strMenit + ':' + strDetik;
  }

  // Perbarui progress bar
  function perbaruiProgressBar() {
    if (totalSoal > 0 && progressFill) {
      var persen = (jumlahTerjawab / totalSoal) * 100;
      progressFill.style.width = persen + '%';
      progressFill.setAttribute('aria-valuenow', Math.round(persen));
    }
    if (angkaTerjawab) {
      angkaTerjawab.textContent = jumlahTerjawab;
    }
  }

  // --------------------------------------------------------------------------
  // 3. Logika Timer Server Hitung Mundur (KRJ-03)
  // --------------------------------------------------------------------------
  function mulaiTimer() {
    if (timerDisplay) {
      timerDisplay.textContent = formatWaktu(sisaDetik);
    }

    intervalTimer = setInterval(function () {
      sisaDetik--;

      if (timerDisplay) {
        timerDisplay.textContent = formatWaktu(sisaDetik);
      }

      // Beri warna peringatan jika sisa waktu di bawah 1 menit (60 detik)
      if (sisaDetik <= 60 && wadahTimer) {
        wadahTimer.classList.add('timer-peringatan');
      }

      // Jika waktu habis, hentikan timer dan kumpulkan otomatis (AC-14)
      if (sisaDetik <= 0) {
        clearInterval(intervalTimer);
        waktuHabisOtomatis();
      }
    }, 1000);
  }

  // Fungsi pengiriman otomatis ketika waktu habis
  function waktuHabisOtomatis() {
    alert('Waktu pengerjaan kuis telah habis! Jawaban kamu akan disimpan.');
    kirimSelesaiKuis('waktu_habis');
  }

  // --------------------------------------------------------------------------
  // 4. Navigasi Kartu Soal (Pindah Soal & Grid Nomor, KRJ-02, KRJ-06)
  // --------------------------------------------------------------------------
  function bukaSoal(nomorBaru) {
    if (nomorBaru < 1 || nomorBaru > totalSoal) {
      return;
    }

    // 1. Sembunyikan slide soal yang sedang aktif
    var slideLama = document.getElementById('slideSoal-' + nomorAktif);
    if (slideLama) {
      slideLama.style.display = 'none';
    }

    // 2. Hilangkan tanda aktif pada tombol nomor grid sebelumnya
    var btnNomorLama = document.getElementById('btnNomor-' + nomorAktif);
    if (btnNomorLama) {
      btnNomorLama.classList.remove('nomor-aktif');
    }

    // 3. Tampilkan slide soal yang dituju
    var slideBaru = document.getElementById('slideSoal-' + nomorBaru);
    if (slideBaru) {
      slideBaru.style.display = 'block';
    }

    // 4. Beri tanda aktif pada tombol nomor grid baru
    var btnNomorBaru = document.getElementById('btnNomor-' + nomorBaru);
    if (btnNomorBaru) {
      btnNomorBaru.classList.add('nomor-aktif');
    }

    // Update nomor aktif
    nomorAktif = nomorBaru;

    // 5. Atur status tombol Sebelumnya dan Lanjut
    if (btnSebelumnya) {
      btnSebelumnya.disabled = (nomorAktif === 1);
    }

    if (btnLanjut) {
      if (nomorAktif === totalSoal) {
        btnLanjut.innerHTML = '&#10004; Selesai Kuis';
        btnLanjut.classList.add('btn-sukses');
      } else {
        btnLanjut.innerHTML = 'Lanjut &rarr;';
        btnLanjut.classList.remove('btn-sukses');
      }
    }

    // Tampilkan tombol selesai cepat jika sudah ada soal yang terjawab
    if (btnSelesaiCepat) {
      if (jumlahTerjawab > 0) {
        btnSelesaiCepat.style.display = 'inline-block';
      } else {
        btnSelesaiCepat.style.display = 'none';
      }
    }

    // Gulir halus ke kartu soal agar nyaman di layar HP (mobile-first)
    if (slideBaru && window.innerWidth < 600) {
      slideBaru.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
  }

  // Tombol Sebelumnya
  if (btnSebelumnya) {
    btnSebelumnya.addEventListener('click', function () {
      bukaSoal(nomorAktif - 1);
    });
  }

  // Tombol Lanjut
  if (btnLanjut) {
    btnLanjut.addEventListener('click', function () {
      if (nomorAktif < totalSoal) {
        bukaSoal(nomorAktif + 1);
      } else {
        // Soal terakhir -> buka modal konfirmasi selesai
        tampilkanModalSelesai();
      }
    });
  }

  // Tombol Selesai Cepat
  if (btnSelesaiCepat) {
    btnSelesaiCepat.addEventListener('click', function () {
      tampilkanModalSelesai();
    });
  }

  // Klik pada Nomor di Grid Soal
  var tombolGrid = document.querySelectorAll('.btn-nomor');
  tombolGrid.forEach(function (btn) {
    btn.addEventListener('click', function () {
      var target = parseInt(btn.getAttribute('data-target-nomor'), 10);
      if (target) {
        bukaSoal(target);
      }
    });
  });

  // --------------------------------------------------------------------------
  // 5. Pemilihan Jawaban & Pengiriman AJAX ke api/jawab.php (KRJ-04, KRJ-05)
  // --------------------------------------------------------------------------
  var semuaOpsi = document.querySelectorAll('.opsi-item');
  semuaOpsi.forEach(function (btnOpsi) {
    btnOpsi.addEventListener('click', function () {
      // Jika opsi sudah terkunci atau sedang ada request berlangsung, abaikan
      if (btnOpsi.classList.contains('terkunci') || sedangMengirim) {
        return;
      }

      var soalId = parseInt(btnOpsi.getAttribute('data-soal-id'), 10);
      var opsiId = parseInt(btnOpsi.getAttribute('data-opsi-id'), 10);
      var labelPilihan = btnOpsi.getAttribute('data-label') || '';

      var slideInduk = btnOpsi.closest('.soal-slide');
      var nomorIni = parseInt(slideInduk.getAttribute('data-nomor'), 10);

      // Kunci semua opsi pada soal ini seketika agar tidak bisa diklik dua kali
      var daftarOpsiSoal = slideInduk.querySelectorAll('.opsi-item');
      daftarOpsiSoal.forEach(function (item) {
        item.classList.add('terkunci');
        item.disabled = true;
      });

      // Beri tanda bahwa opsi ini yang dipilih
      btnOpsi.classList.add('opsi-terpilih');

      sedangMengirim = true;

      // Kirim jawaban via POST JSON ke endpoint api/jawab.php
      fetch('../api/jawab.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': tokenCsrf
        },
        body: JSON.stringify({
          kuis_id: kuisId,
          soal_id: soalId,
          opsi_id: opsiId,
          csrf_token: tokenCsrf
        })
      })
      .then(function (res) {
        return res.json().then(function (data) {
          return { status: res.status, body: data };
        });
      })
      .then(function (hasil) {
        sedangMengirim = false;
        var data = hasil.body;

        // 1. Tangani Waktu Habis (410)
        if (hasil.status === 410 || data.kode === 'waktu_habis') {
          alert('Waktu pengerjaan telah habis!');
          if (data.redirect) {
            window.location.href = data.redirect;
          }
          return;
        }

        // 2. Tangani Kesalahan / Penolakan Lain
        if (!hasil.body.ok) {
          if (hasil.status === 409) {
            alert('Jawaban untuk soal ini sudah pernah dikirim sebelumnya.');
          } else {
            alert('Gagal mengirim jawaban: ' + (data.kode || 'Terjadi kesalahan'));
          }
          return;
        }

        // 3. Jawaban Berhasil Diterima (200 OK)
        var isBenar = data.benar;
        var kunciOpsiId = data.opsi_benar_id;
        var pembahasanTeks = data.pembahasan;

        // Sinkronkan sisa waktu dari server jika ada
        if (typeof data.sisa_detik === 'number') {
          sisaDetik = data.sisa_detik;
          if (timerDisplay) {
            timerDisplay.textContent = formatWaktu(sisaDetik);
          }
        }

        // Update tampilan opsi yang diklik (Benar atau Salah)
        if (isBenar) {
          btnOpsi.classList.add('opsi-benar');
        } else {
          btnOpsi.classList.add('opsi-salah');

          // Tampilkan garis penanda pada opsi kunci jawaban yang benar
          daftarOpsiSoal.forEach(function (item) {
            var idItem = parseInt(item.getAttribute('data-opsi-id'), 10);
            if (idItem === kunciOpsiId) {
              item.classList.add('opsi-kunci');
            }
          });
        }

        // Tampilkan Kotak Umpan Balik dan Pembahasan (ACC-03)
        var boxUmpanBalik = document.getElementById('umpanBalik-' + nomorIni);
        if (boxUmpanBalik) {
          boxUmpanBalik.className = 'umpan-balik-box ' + (isBenar ? 'benar' : 'salah');
          boxUmpanBalik.style.display = 'block';

          var wadahStatus = boxUmpanBalik.querySelector('.umpan-balik-status');
          if (wadahStatus) {
            if (isBenar) {
              wadahStatus.innerHTML = '<span aria-hidden="true">&#10004;</span> <span>Benar! Jawaban kamu tepat.</span>';
            } else {
              wadahStatus.innerHTML = '<span aria-hidden="true">&#10008;</span> <span>Belum tepat. Kunci jawaban: ' + (data.kunci_label || 'lihat opsi bertanda') + '</span>';
            }
          }

          var wadahTeks = boxUmpanBalik.querySelector('.teks-pembahasan');
          if (wadahTeks) {
            wadahTeks.textContent = pembahasanTeks || 'Tidak ada pembahasan tambahan.';
          }
        }

        // Update Tombol Nomor pada Grid Soal
        var btnGrid = document.getElementById('btnNomor-' + nomorIni);
        if (btnGrid) {
          btnGrid.classList.add('nomor-terjawab');
          btnGrid.classList.add(isBenar ? 'nomor-benar' : 'nomor-salah');
        }

        // Perbarui jumlah soal terjawab dan progress bar
        jumlahTerjawab++;
        perbaruiProgressBar();

        // Aktifkan tombol selesai cepat
        if (btnSelesaiCepat) {
          btnSelesaiCepat.style.display = 'inline-block';
        }
      })
      .catch(function (err) {
        sedangMengirim = false;
        console.error('Error pengiriman jawaban:', err);
        alert('Gagal mengirim jawaban karena gangguan jaringan. Silakan periksa koneksi internet Anda (REL-03).');

        // Buka kembali opsi agar user bisa mencoba lagi
        daftarOpsiSoal.forEach(function (item) {
          item.classList.remove('terkunci');
          item.disabled = false;
        });
        btnOpsi.classList.remove('opsi-terpilih');
      });
    });
  });

  // --------------------------------------------------------------------------
  // 6. Dialog Modal Konfirmasi Keluar (KRJ-02)
  // --------------------------------------------------------------------------
  if (tombolKeluarFokus && modalKeluar) {
    tombolKeluarFokus.addEventListener('click', function (e) {
      e.preventDefault();
      modalKeluar.classList.add('aktif');
    });
  }

  if (btnBatalKeluar && modalKeluar) {
    btnBatalKeluar.addEventListener('click', function () {
      modalKeluar.classList.remove('aktif');
    });
  }

  // --------------------------------------------------------------------------
  // 7. Dialog Modal Konfirmasi Selesai Kuis (KRJ-07)
  // --------------------------------------------------------------------------
  function tampilkanModalSelesai() {
    if (!modalSelesai) {
      return;
    }

    var belumTerjawab = totalSoal - jumlahTerjawab;

    if (belumTerjawab > 0 && peringatanBelumSelesai) {
      peringatanBelumSelesai.style.display = 'block';
      peringatanBelumSelesai.textContent = 'Perhatian: Masih ada ' + belumTerjawab + ' soal yang belum kamu jawab! Soal kosong akan dihitung salah.';
    } else if (peringatanBelumSelesai) {
      peringatanBelumSelesai.style.display = 'none';
    }

    if (pesanKonfirmasiSelesai) {
      if (belumTerjawab === 0) {
        pesanKonfirmasiSelesai.textContent = 'Semua soal sudah terjawab! Apakah kamu yakin ingin mengumpulkan kuis ini sekarang?';
      } else {
        pesanKonfirmasiSelesai.textContent = 'Apakah kamu yakin ingin menyelesaikan kuis sekarang?';
      }
    }

    modalSelesai.classList.add('aktif');
  }

  if (btnBatalKumpul && modalSelesai) {
    btnBatalKumpul.addEventListener('click', function () {
      modalSelesai.classList.remove('aktif');
    });
  }

  if (btnKonfirmasiKumpul) {
    btnKonfirmasiKumpul.addEventListener('click', function () {
      kirimSelesaiKuis('selesai');
    });
  }

  // --------------------------------------------------------------------------
  // 8. Pengiriman Selesai Kuis ke api/selesai.php (KRJ-07, REL-02)
  // --------------------------------------------------------------------------
  function kirimSelesaiKuis(statusAkhir) {
    if (btnKonfirmasiKumpul) {
      btnKonfirmasiKumpul.disabled = true;
      btnKonfirmasiKumpul.textContent = 'Menyimpan...';
    }

    if (intervalTimer) {
      clearInterval(intervalTimer);
    }

    fetch('../api/selesai.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': tokenCsrf
      },
      body: JSON.stringify({
        kuis_id: kuisId,
        csrf_token: tokenCsrf
      })
    })
    .then(function (res) {
      return res.json();
    })
    .then(function (data) {
      if (data.ok && data.redirect) {
        window.location.href = data.redirect;
      } else {
        alert('Gagal menyelesaikan kuis: ' + (data.kode || 'Terjadi kesalahan sistem'));
        if (btnKonfirmasiKumpul) {
          btnKonfirmasiKumpul.disabled = false;
          btnKonfirmasiKumpul.textContent = 'Coba Lagi';
        }
      }
    })
    .catch(function (err) {
      console.error('Error saat menyelesaikan kuis:', err);
      alert('Terjadi kesalahan jaringan saat menyelesaikan kuis. Mencoba dialihkan ke halaman hasil...');
      window.location.href = 'hasil.php?id=' + kuisId;
    });
  }

  // Jalankan timer saat halaman siap
  mulaiTimer();
  perbaruiProgressBar();
});
