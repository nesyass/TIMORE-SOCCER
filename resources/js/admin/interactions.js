import { animate as motionAnimate } from 'motion/mini';
import { waapi } from 'animejs';

const motionAllowed = matchMedia('(prefers-reduced-motion: no-preference)');

document.addEventListener('toggle', (event) => {
    const dialog = event.target;
    if (!(dialog instanceof HTMLDialogElement) || event.newState !== 'open' || !motionAllowed.matches) return;
    const panel = dialog.querySelector('.drawer-inner, .modal-content');
    if (!panel) return;

    const start = dialog.classList.contains('navigation-drawer') ? 'translateX(-8px)' : 'translateY(4px)';
    motionAnimate(panel, { transform: [start, 'none'] }, { duration: 0.16, ease: 'easeOut' });
}, true);

const confirmation = document.querySelector('.alert.success');
if (confirmation && motionAllowed.matches) {
    waapi.animate(confirmation, { opacity: [0.76, 1], duration: 160, ease: 'outQuad' });
}
