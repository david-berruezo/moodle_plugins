// =============================================================================
// UTILS — helpers puros, sin side-effects
// =============================================================================

/**
 * Genera el HTML de las estrellas SVG para un rating dado.
 * @param {number} rating  valor entre 0 y 5 (decimales permitidos)
 * @returns {string} HTML string
 */
export function buildStars(rating) {
    const full  = Math.floor(rating);
    const half  = (rating % 1) >= 0.5 ? 1 : 0;
    const empty = 5 - full - half;

    const starFull  = (fill) => `<svg viewBox="0 0 24 24" width="12" height="12" aria-hidden="true">
<path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"
 fill="${fill}" stroke="none"/>
</svg>`;

    return [
        ...Array(full).fill(starFull('#F0A500')),
        ...(half ? [starFull('#F0A500') /* TODO: half-star SVG real */] : []),
        ...Array(empty).fill(starFull('#d1d7dc')),
    ].join('');
}

/**
 * Consulta un elemento del DOM lanzando error si no existe.
 * @template {HTMLElement} T
 * @param {string} selector
 * @param {Document|HTMLElement} [root=document]
 * @returns {T}
 */
export function qs(selector, root = document) {
    const el = /** @type {T} */ (root.querySelector(selector));
    if (!el){throw new Error(`qs: no element for "${selector}"`);}
    return el;
}

/**
 * Versión silenciosa de qs (devuelve null si no existe).
 * @template {HTMLElement} T
 * @param {string} selector
 * @param {Document|HTMLElement} [root=document]
 * @returns {T|null}
 */
export function qsMaybe(selector, root = document) {
    return /** @type {T|null} */ (root.querySelector(selector));
}

/**
 * Shortcut para querySelectorAll → Array.
 * @param {string} selector
 * @param {Document|HTMLElement} [root=document]
 * @returns {HTMLElement[]}
 */
export function qsAll(selector, root = document) {
    return /** @type {HTMLElement[]} */ ([...root.querySelectorAll(selector)]);
}

/**
 * Establece el año actual en un elemento (para el footer de copyright).
 * @param {string} selector
 */
export function setCurrentYear(selector) {
    const el = qsMaybe(selector);
    if (el){el.textContent = String(new Date().getFullYear());}
}
