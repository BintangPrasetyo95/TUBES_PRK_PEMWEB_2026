/**
 * assets/js/tema-init.js
 * Memuat preferensi tema dari localStorage sebelum CSS dan halaman dirender
 * Bertujuan agar tidak terjadi kedipan warna layar (Flash of Unstyled Content / FOUC)
 * Dibuat oleh: Farel (Hari 1 Sesi Pagi)
 */
(function () {
  var tema = 'sky-bubblegum';
  try {
    var s = localStorage.getItem('dq-tema');
    if (['lavender-dream', 'mint-cloud', 'peach-pop', 'sky-bubblegum'].indexOf(s) > -1) {
      tema = s;
    }
  } catch (e) {
    /* jika penyimpanan diblokir peramban, gunakan tema default */
  }
  document.documentElement.setAttribute('data-tema', tema);
})();
