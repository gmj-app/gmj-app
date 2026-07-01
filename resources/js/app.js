import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

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
