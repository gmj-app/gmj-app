import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.data('siteNavigation', () => ({
    open: false,
    accountOpen: false,
    notificationsOpen: false,
    dark: document.documentElement.classList.contains('dark'),
    toggleMobileMenu() {
        this.open = !this.open;
        this.accountOpen = false;
        this.notificationsOpen = false;
    },
    toggleAccountMenu() {
        this.accountOpen = !this.accountOpen;
        this.notificationsOpen = false;
    },
    toggleNotifications() {
        this.notificationsOpen = !this.notificationsOpen;
        this.accountOpen = false;
    },
    closeAll() {
        this.open = false;
        this.accountOpen = false;
        this.notificationsOpen = false;
    },
    async toggleTheme() {
        this.dark = !this.dark;
        const theme = this.dark ? 'dark' : 'light';

        document.documentElement.classList.toggle('dark', this.dark);
        document.documentElement.dataset.theme = theme;
        localStorage.setItem('theme', theme);
        document.cookie = `theme=${theme}; Path=/; Max-Age=31536000; SameSite=Lax`;

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const themeUrl = document.querySelector('meta[name="theme-update-url"]')?.getAttribute('content');

        if (!csrfToken || !themeUrl) return;

        try {
            await fetch(themeUrl, {
                method: 'PATCH',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ theme }),
            });
        } catch (error) {
            console.error('Unable to persist theme preference.', error);
        }
    },
}));

const resetModalState = () => {
    document.body.classList.remove('overflow-hidden', 'overflow-y-hidden', 'pointer-events-none');
    document.documentElement.classList.remove('overflow-hidden', 'overflow-y-hidden');
    document.body.style.removeProperty('overflow');

    document.querySelectorAll('[data-modal-root]').forEach((modal) => {
        modal.hidden = true;
        modal.style.display = 'none';
        modal.setAttribute('aria-hidden', 'true');
        modal.classList.remove('pointer-events-auto', 'visible');
        modal.classList.add('pointer-events-none', 'invisible');
    });

    document.querySelectorAll('[data-modal-backdrop]').forEach((backdrop) => {
        backdrop.hidden = true;
        backdrop.setAttribute('aria-hidden', 'true');
        backdrop.classList.add('pointer-events-none');
    });

    document.querySelectorAll('[data-app-root][inert], main[inert]').forEach((element) => {
        element.removeAttribute('inert');
    });

    window.dispatchEvent(new CustomEvent('reset-modals'));
};

window.resetModalState = resetModalState;

Alpine.start();

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', resetModalState, { once: true });
} else {
    resetModalState();
}

window.addEventListener('pageshow', () => {
    resetModalState();
    requestAnimationFrame(resetModalState);
});

document.querySelectorAll('[data-daily-journey]').forEach((root) => {
    const countdown = root.querySelector('[data-game-countdown]');
    const tick = () => { const left = Math.max(0, new Date(countdown.dataset.endsAt).getTime() - Date.now()); const seconds = Math.floor(left / 1000); countdown.textContent = `${String(Math.floor(seconds / 3600)).padStart(2, '0')}:${String(Math.floor((seconds % 3600) / 60)).padStart(2, '0')}:${String(seconds % 60).padStart(2, '0')}`; };
    tick(); setInterval(tick, 1000);
    root.querySelector('[data-game-play]')?.addEventListener('click', async () => {
        if (root.dataset.authenticated !== '1') { window.location.assign(root.dataset.loginUrl); return; }
        const expanded = root.querySelector('[data-game-expanded]'); expanded.hidden = false; expanded.scrollIntoView({ behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'start' });
        if (expanded.dataset.mounted) return; expanded.dataset.mounted = '1';
        try { const { mountDailyJourney } = await import('./daily-journey/index.js'); await mountDailyJourney(root); }
        catch (error) { expanded.dataset.mounted = ''; root.querySelector('[data-game-status]').textContent = error.message || 'Could not start a new run. Please try again.'; }
    });
});
