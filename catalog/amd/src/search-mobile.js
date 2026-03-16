// =============================================================================
// SEARCH MOBILE — panel de búsqueda deslizante en móvil
// =============================================================================
import { qsMaybe } from './utils';

/**
 * initMobileSearch
 */
export function initMobileSearch() {
    const panel     = qsMaybe('#cv-mob-search');
    const openBtn   = qsMaybe('#cv-mob-search-open');
    const closeBtn  = qsMaybe('#cv-mob-search-close');

    if (!panel){return;}

    openBtn?.addEventListener('click', () => {
        panel.classList.add('is-open');
        panel.querySelector('input')?.focus();
    });

    closeBtn?.addEventListener('click', () => {
        panel.classList.remove('is-open');
    });

    // Escape también cierra
    panel.addEventListener('keydown', (e) => {
        if (e.key === 'Escape'){panel.classList.remove('is-open');}
    });
}
