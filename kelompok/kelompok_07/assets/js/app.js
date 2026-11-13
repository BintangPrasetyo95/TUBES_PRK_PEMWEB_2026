document.addEventListener('DOMContentLoaded', () => {
    const navbar = document.querySelector('[data-navbar]');
    const toggle = document.querySelector('[data-navbar-toggle]');

    if (navbar) {
        const updateScrollState = () => navbar.classList.toggle('is-scrolled', window.scrollY > 8);
        updateScrollState();
        window.addEventListener('scroll', updateScrollState, { passive: true });
    }

    if (navbar && toggle) {
        const setOpen = (open) => {
            navbar.classList.toggle('is-open', open);
            toggle.setAttribute('aria-expanded', String(open));
            toggle.setAttribute('aria-label', open ? 'Tutup menu navigasi' : 'Buka menu navigasi');
        };

        toggle.addEventListener('click', () => setOpen(!navbar.classList.contains('is-open')));

        navbar.querySelectorAll('.navbar-menu a').forEach((link) => {
            link.addEventListener('click', () => setOpen(false));
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && navbar.classList.contains('is-open')) {
                setOpen(false);
                toggle.focus();
            }
        });
    }

    document.querySelectorAll('[data-toggle-password]').forEach((button) => {
        const input = document.getElementById(button.dataset.togglePassword);

        if (!input) {
            return;
        }

        button.addEventListener('click', () => {
            const hidden = input.type === 'password';
            input.type = hidden ? 'text' : 'password';
            button.textContent = hidden ? 'Sembunyikan' : 'Lihat';
            button.setAttribute('aria-pressed', String(hidden));
        });
    });

    document.querySelectorAll('[data-print]').forEach((button) => {
        button.addEventListener('click', () => window.print());
    });
});
