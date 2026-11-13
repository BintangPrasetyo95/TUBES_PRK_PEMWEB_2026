// Skrip bersama CEKHAK
document.addEventListener('DOMContentLoaded', function () {
  var tombol = document.querySelector('[data-nav-toggle]');
  var menu = document.querySelector('[data-nav-menu]');
  if (tombol && menu) {
    tombol.addEventListener('click', function () {
      menu.classList.toggle('terbuka');
    });
  }
});
