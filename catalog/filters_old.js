// =============================================================================
// FILTERS — sidebar de filtros + chips activos + toggle móvil
// =============================================================================
import { qsAll, qsMaybe } from './utils';

// ── URL base del catálogo (leída del DOM en init) ─────────────────────────────
/** @type {string} */
let catalogUrl = '/cursos';

/**
 * Inicializa todos los comportamientos del sidebar de filtros.
 *
 * @returns {void}
 */
export function initFilters() {
    const sidebar = qsMaybe('.cv-catalog__sidebar');

    if (sidebar?.dataset.catalogUrl) {
        catalogUrl = sidebar.dataset.catalogUrl;
    }

    initFilterGroups();
    initFilterCheckboxes();
    initMobileToggle();
    initSortSelect();
    initRatingFilter();
}

// ── Acordeón de grupos ────────────────────────────────────────────────────────
/**
 * Inicializa el comportamiento de acordeón en cada grupo de filtros.
 *
 * @returns {void}
 */
function initFilterGroups() {
    qsAll('.cv-filter-group__header').forEach((header) => {
        // Abrir todos por defecto
        header.classList.add('is-open');
        const body = header.nextElementSibling;
        if (body){body.classList.remove('is-collapsed');}
        header.addEventListener('click', () => {
            const isOpen = header.classList.toggle('is-open');
            const bodyEl = header.nextElementSibling;
            if (bodyEl){bodyEl.classList.toggle('is-collapsed', !isOpen);}
        });
    });
}

// ── Checkboxes → chips activos ────────────────────────────────────────────────
/**
 * initFilterCheckboxes
 */
function initFilterCheckboxes() {
    const chipsContainer = qsMaybe('.cv-active-filters');
    if (!chipsContainer){return;}

    qsAll('.cv-filter-item__check').forEach((checkbox) => {
        checkbox.addEventListener('change', () => {
            renderChips(chipsContainer);
        });
    });

    // Botón limpiar todo
    chipsContainer.addEventListener('click', (e) => {
        const clearBtn = /** @type {HTMLElement} */ (e.target).closest('.cv-active-filters__clear');
        const removeBtn = /** @type {HTMLElement} */ (e.target).closest('.cv-active-filters__chip button');

        if (clearBtn) {
            qsAll('.cv-filter-item__check').forEach((cb) => {
                /** @type {HTMLInputElement} */ (cb).checked = false;
            });
            qsAll('.cv-filter-rating__row').forEach((r) => r.classList.remove('is-selected'));
            renderChips(chipsContainer);
        }

        if (removeBtn) {
            const chip = removeBtn.closest('.cv-active-filters__chip');
            const label = chip?.dataset.label;
            if (label) {
                qsAll('.cv-filter-item').forEach((item) => {
                    const lbl = item.querySelector('.cv-filter-item__label');
                    const cb  = item.querySelector('.cv-filter-item__check');
                    if (lbl?.textContent?.trim() === label && cb) {
                        /** @type {HTMLInputElement} */ (cb).checked = false;
                    }
                });
            }
            renderChips(chipsContainer);
        }
    });
}

/**
 * @param {string} container
 */
function renderChips(container) {

    container.innerHTML = '';

    const active = /** @type {HTMLInputElement[]} */ (
        qsAll('.cv-filter-item__check').filter((cb) => /** @type {HTMLInputElement} */ (cb).checked)
    );

    const ratingSelected = qsMaybe('.cv-filter-rating__row.is-selected');

    if (active.length === 0 && !ratingSelected){ return;}

    active.forEach((cb) => {
        const label = cb.closest('.cv-filter-item')?.querySelector('.cv-filter-item__label')?.textContent?.trim() ?? '';
        container.appendChild(buildChip(label));
    });

    if (ratingSelected) {
        const label = ratingSelected.querySelector('.cv-filter-rating__label')?.textContent?.trim() ?? '';
        container.appendChild(buildChip(`${label} y más`));
    }

    const clearBtn = document.createElement('button');
    clearBtn.className = 'cv-active-filters__clear';
    clearBtn.textContent = 'Borrar filtros';
    container.appendChild(clearBtn);
}


/**
 * @param {string} label
 * @returns {*}
 */
function buildChip(label) {
    const chip = document.createElement('div');
    chip.className = 'cv-active-filters__chip';
    chip.dataset.label = label;
    chip.innerHTML = `${label}<button aria-label="Quitar filtro ${label}">×</button>`;
    return chip;
}

// ── Rating filter ─────────────────────────────────────────────────────────────
/**
 * initRatingFilter
 */
function initRatingFilter() {
    qsAll('.cv-filter-rating__row').forEach((row) => {
        row.addEventListener('click', () => {
            qsAll('.cv-filter-rating__row').forEach((r) => r.classList.remove('is-selected'));
            row.classList.add('is-selected');
            const chipsContainer = qsMaybe('.cv-active-filters');
            if (chipsContainer){renderChips(chipsContainer);}
        });
    });
}

// ── Toggle sidebar móvil ──────────────────────────────────────────────────────
/**
 * initMobileToggle
 */
function initMobileToggle() {
    const toggle  = qsMaybe('.cv-results-bar__mobile-toggle');
    const sidebar = qsMaybe('.cv-catalog__sidebar');
    const overlay = qsMaybe('#cv-overlay');
    if (!toggle || !sidebar){ return;}

    toggle.addEventListener('click', () => {
        sidebar.classList.toggle('is-open');
        overlay?.classList.toggle('is-open');
    });

    overlay?.addEventListener('click', () => {
        sidebar.classList.remove('is-open');
        overlay.classList.remove('is-open');
    });
}

// ── Sort select ───────────────────────────────────────────────────────────────
/**
 * initSortSelect
 */
function initSortSelect() {
    qsMaybe('.cv-results-bar__select')?.addEventListener('change', (e) => {
        const val = /** @type {HTMLSelectElement} */ (e.target).value;
        window.console.log('[filters] sort changed to:', val);
        // En producción: re-fetch o re-sort el grid
    });
}
