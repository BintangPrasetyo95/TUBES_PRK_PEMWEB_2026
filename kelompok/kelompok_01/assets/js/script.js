
document.addEventListener("DOMContentLoaded", function () {
    const menuToggle = document.getElementById("menuToggle");
    const sidebar = document.getElementById("sidebar");

    if (!menuToggle || !sidebar) return;

    menuToggle.addEventListener("click", function () {
        sidebar.classList.toggle("open");
    });

    // Tutup sidebar setelah memilih menu di HP
    const menuItems = sidebar.querySelectorAll(".menu-item");

    menuItems.forEach(function (item) {
        item.addEventListener("click", function () {
            sidebar.classList.remove("open");
        });
    });

    // Tutup sidebar dengan tombol Escape
    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape") {
            sidebar.classList.remove("open");
        }
    });
});