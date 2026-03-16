// =============================================================================
// drawer.js — Menú lateral móvil + búsqueda móvil
// amd/src/drawer.js
// =============================================================================
//
// IDs usados en home.mustache:
//   #cv-drawer           → nav lateral
//   #cv-overlay          → overlay oscuro
//   #cv-hamburger        → botón abrir (mobile bar)
//   #cv-drawer-close     → botón × dentro del drawer
//   #cv-mob-search       → barra de búsqueda móvil (atributo hidden)
//   #cv-mob-search-open  → botón lupa en mobile bar
//   #cv-mob-search-close → botón × en barra búsqueda
//
//   Paneles del drawer: id="dp-main", id="dp-profile"
//     [data-panel="profile"]   → abre dp-profile
//     [data-panel-back="main"] → vuelve a dp-main
//
// =============================================================================
import { qsMaybe, qsAll } from './utils';

/** @type {HTMLElement|null} */
let drawerEl = null;

/** @type {HTMLElement|null} */
let overlayEl = null;

// ── Drawer ────────────────────────────────────────────────────────────────────

/**
 * Abre el drawer lateral y bloquea el scroll del documento.
 *
 * @returns {void}
 */
export function openDrawer() {
    drawerEl?.classList.add('is-open');
    overlayEl?.classList.add('is-open');
    qsMaybe('#cv-hamburger')?.setAttribute('aria-expanded', 'true');
    document.body.style.overflow = 'hidden';
    showPanel('main');
}

/**
 * Cierra el drawer lateral y restaura el scroll del documento.
 *
 * @returns {void}
 */
export function closeDrawer() {
    drawerEl?.classList.remove('is-open');
    overlayEl?.classList.remove('is-open');
    qsMaybe('#cv-hamburger')?.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
}

/**
 * Muestra el panel con id `dp-{id}` y oculta el resto.
 *
 * @param {string} id  Sufijo del panel, ej: "main" activa #dp-main
 * @returns {void}
 */
export function showPanel(id) {
    qsAll('.cv-drawer__panel', drawerEl ?? document).forEach((p) => {
        p.classList.remove('is-active');
    });
    document.getElementById(`dp-${id}`)?.classList.add('is-active');
}

// ── Búsqueda móvil ────────────────────────────────────────────────────────────

/**
 * Muestra la barra de búsqueda móvil y enfoca el input.
 *
 * @returns {void}
 */
function openMobileSearch() {
    const search = document.getElementById('cv-mob-search');

    if (!search) {
        return;
    }

    search.hidden = false;
    search.querySelector('input')?.focus();
    qsMaybe('#cv-mob-search-open')?.setAttribute('aria-expanded', 'true');
}

/**
 * Oculta la barra de búsqueda móvil.
 *
 * @returns {void}
 */
function closeMobileSearch() {
    const search = document.getElementById('cv-mob-search');

    if (!search) {
        return;
    }

    search.hidden = true;
    qsMaybe('#cv-mob-search-open')?.setAttribute('aria-expanded', 'false');
}

// ── Inicialización ────────────────────────────────────────────────────────────

/**
 * Inicializa el drawer móvil, los paneles de navegación y la búsqueda móvil.
 * No hace nada si el drawer no está presente en el DOM.
 *
 * @returns {void}
 */
export function initDrawer() {
    drawerEl = document.getElementById('cv-drawer');
    overlayEl = document.getElementById('cv-overlay');

    if (!drawerEl) {
        return;
    }

    // Apertura / cierre
    qsMaybe('#cv-hamburger')?.addEventListener('click', openDrawer);
    qsMaybe('#cv-drawer-close')?.addEventListener('click', closeDrawer);
    overlayEl?.addEventListener('click', closeDrawer);

    drawerEl.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeDrawer();
        }
    });

    // Navegación entre paneles: [data-panel="xxx"]
    qsAll('[data-panel]', drawerEl).forEach((btn) => {
        btn.addEventListener('click', () => {
            const panelId = /** @type {HTMLElement} */ (btn).dataset.panel;
            if (panelId) {
                showPanel(panelId);
            }
        });
    });

    // Botones "volver": [data-panel-back="xxx"]
    qsAll('[data-panel-back]', drawerEl).forEach((btn) => {
        btn.addEventListener('click', () => {
            const panelId = /** @type {HTMLElement} */ (btn).dataset.panelBack;
            if (panelId) {
                showPanel(panelId);
            }
        });
    });

    // Búsqueda móvil
    qsMaybe('#cv-mob-search-open')?.addEventListener('click', openMobileSearch);
    qsMaybe('#cv-mob-search-close')?.addEventListener('click', closeMobileSearch);

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeMobileSearch();
        }
    });
}