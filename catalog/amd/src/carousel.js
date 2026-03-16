// =============================================================================
// CAROUSEL — navegación por flechas (desktop)
// =============================================================================
import { qsAll } from './utils';

const VISIBLE = 4; // cards visibles por defecto en desktop

/**
 * Inicializa todos los `.cv-carousel` de la página.
 */
export function initCarousels() {
    qsAll('.cv-carousel').forEach(initSingleCarousel);
}

/**
 * Inicializa un carousel individual.
 * @param {HTMLElement} carousel
 */
function initSingleCarousel(carousel) {
    const track   = carousel.querySelector('.cv-carousel__track');
    const prevBtn = /** @type {HTMLButtonElement|null} */ (carousel.querySelector('.cv-carousel__prev'));
    const nextBtn = /** @type {HTMLButtonElement|null} */ (carousel.querySelector('.cv-carousel__next'));

    if (!track || !prevBtn || !nextBtn){return;}

    let offset = 0;

    /** Devuelve todos los items actuales (se regeneran vía populateCarousel) */
    const getItems = () => /** @type {HTMLElement[]} */ ([...track.querySelectorAll('.cv-carousel__item')]);

    /**
     * render function
     */
    function render() {
        const items = getItems();
        const total = items.length;

        items.forEach((item, i) => {
            item.style.display = (i >= offset && i < offset + VISIBLE) ? '' : 'none';
        });

        prevBtn.disabled = offset === 0;
        nextBtn.disabled = offset + VISIBLE >= total;
    }

    prevBtn.addEventListener('click', () => {
        offset = Math.max(0, offset - VISIBLE);
        render();
    });

    nextBtn.addEventListener('click', () => {
        const total = getItems().length;
        offset = Math.min(total - VISIBLE, offset + VISIBLE);
        render();
    });

    // Esperar a que populateCarousel haya insertado los items
    // (se llama desde main.js tras populate, pero por si acaso usamos MutationObserver)
    const observer = new MutationObserver(() => {
        if (getItems().length > 0) {
            observer.disconnect();
            render();
        }
    });
    observer.observe(track, { childList: true });

    // Si ya hay items al inicializar
    if (getItems().length > 0) {
        observer.disconnect();
        render();
    }
}
