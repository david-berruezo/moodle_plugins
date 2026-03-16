// =============================================================================
// popper.js — Dropdowns desktop con hover + delay de cierre
// amd/src/popper.js
// =============================================================================
import { qsAll } from './utils';

/** @type {number} Milisegundos de gracia antes de cerrar el dropdown */
const HOVER_DELAY_MS = 180;

/**
 * Cierra todos los poppers abiertos, excepto el indicado.
 *
 * @param {HTMLElement|null} except  Elemento `.cv-popper__content` que NO se cierra
 * @returns {void}
 */
function closeAll(except = null) {
    qsAll('.cv-popper__content.is-open').forEach((content) => {
        if (content !== except) {
            content.classList.remove('is-open');
            content.closest('.cv-popper')
                ?.querySelector('[aria-expanded]')
                ?.setAttribute('aria-expanded', 'false');
        }
    });
}

/**
 * Inicializa todos los `.cv-popper[data-trigger="hover"]` del documento.
 * Delega el cierre global al click fuera y a la tecla Escape.
 *
 * @returns {void}
 */
export function initPoppers() {
    qsAll('.cv-popper[data-trigger="hover"]').forEach(initSinglePopper);

    document.addEventListener('click', (e) => {
        if (!/** @type {HTMLElement} */ (e.target).closest('.cv-popper')) {
            closeAll();
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeAll();
        }
    });
}

/**
 * Enlaza los eventos hover y click a un único elemento `.cv-popper`.
 *
 * @param {HTMLElement} popper  Contenedor `.cv-popper`
 * @returns {void}
 */
function initSinglePopper(popper) {
    const content = popper.querySelector('.cv-popper__content');

    if (!content) {
        return;
    }

    /** @type {ReturnType<typeof setTimeout>} */
    let closeTimer;

    /**
     * Abre el dropdown de este popper y cierra el resto.
     *
     * @returns {void}
     */
    function open() {
        clearTimeout(closeTimer);
        closeAll(/** @type {HTMLElement} */ (content));
        content.classList.add('is-open');
        popper.querySelector('[aria-expanded]')
            ?.setAttribute('aria-expanded', 'true');
    }

    /**
     * Programa el cierre del dropdown tras HOVER_DELAY_MS ms.
     *
     * @returns {void}
     */
    function scheduleClose() {
        closeTimer = setTimeout(() => {
            content.classList.remove('is-open');
            popper.querySelector('[aria-expanded]')
                ?.setAttribute('aria-expanded', 'false');
        }, HOVER_DELAY_MS);
    }

    popper.addEventListener('mouseenter', open);
    popper.addEventListener('mouseleave', scheduleClose);

    content.addEventListener('mouseenter', () => clearTimeout(closeTimer));
    content.addEventListener('mouseleave', scheduleClose);

    const btn = popper.querySelector('button[aria-expanded], a[aria-expanded]');

    btn?.addEventListener('click', (e) => {
        if (content.classList.contains('is-open')) {
            clearTimeout(closeTimer);
            content.classList.remove('is-open');
            btn.setAttribute('aria-expanded', 'false');
        } else {
            open();
            e.preventDefault();
        }
    });
}