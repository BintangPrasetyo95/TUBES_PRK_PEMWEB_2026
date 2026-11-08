// File: js/app.js
function loadPage(page) {
    const contentArea = document.getElementById('app-content');
    contentArea.innerHTML = ''; // Bersihin layar dulu
    contentArea.className = 'fade-in';
    setTimeout(() => contentArea.classList.remove('fade-in'), 400);

    if (page === 'beranda') {
        contentArea.innerHTML = `
            <div class="text-center py-10">
                <h2 class="text-4xl font-extrabold text-gray-800 mb-4">Selamat Datang di CiviLens</h2>
                <p class="text-lg text-gray-500">Platform partisipasi masyarakat FISIP.</p>
            </div>
        `;
    }
    else if (page === 'hoaks') {
        renderHoaks(contentArea); // Manggil fungsi buatan Aya
    }
    else if (page === 'aspirasi') {
        renderAspirasi(contentArea); // Manggil fungsi buatan Gigih
    }
    else if (page === 'petisi') {
        renderPetisi(contentArea); // Manggil fungsi buatan Dendi
    }
}

// Buka beranda otomatis pas pertama kali web jalan
document.addEventListener('DOMContentLoaded', () => loadPage('beranda'));