// =============================================================================
// filters.js — Sidebar de filtros + chips activos + navegación GET
// amd/src/filters.js
// =============================================================================
//
// Flujo completo:
//   1. Usuario marca/desmarca un checkbox
//   2. renderChips() actualiza los chips visuales
//   3. applyFilters() recoge TODOS los checkboxes marcados + q del buscador
//      y navega a: /cursos?cat=3&price=free&level=beginner&q=react
//
// Requisitos del HTML (mustache):
//   · Sidebar:    <aside data-catalog-url="/cursos">
//   · Checkbox:   <input class="cv-filter-item__check"
//                        data-param="price"
//                        data-value="free"
//                        checked>
//   · Buscador:   <input data-filter-search value="react">
//   · Chips:      <div class="cv-active-filters">
// =============================================================================
import { qsAll, qsMaybe } from './utils';

// ── URL base del catálogo (leída del DOM en init) ─────────────────────────────
/** @type {string} */
let catalogUrl = '/cursos';
/** @type {string} */
let id_seleccionado = '';
/** @type {string} */
let tipo_seleccionado = '';

// ── API pública ───────────────────────────────────────────────────────────────

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
}

// ── Acordeón de grupos ────────────────────────────────────────────────────────

/**
 * Inicializa el comportamiento de acordeón en cada grupo de filtros.
 *
 * @returns {void}
 */
function initFilterGroups() {
    qsAll('.cv-filter-group__header').forEach((header) => {
        header.classList.add('is-open');
        const body = header.nextElementSibling;

        if (body) {
            body.classList.remove('is-collapsed');
        }

        header.addEventListener('click', () => {
            const isOpen = header.classList.toggle('is-open');
            const bodyEl = header.nextElementSibling;

            if (bodyEl) {
                bodyEl.classList.toggle('is-collapsed', !isOpen);
            }
        });
    });
}


// ── Checkboxes → chips + navegación ──────────────────────────────────────────

/**
 * Inicializa los checkboxes de filtro: chips visuales y navegación GET.
 *
 * @returns {void}
 */
function initFilterCheckboxes() {

    const chipsContainer = qsMaybe('.cv-active-filters');

    if (!chipsContainer) {
        return;
    }

    // Renderizar estado inicial (filtros activos al cargar la página)
    renderChips(chipsContainer);

    // Cambio en cualquier checkbox → chips + navegar
    qsAll('.cv-filter-item__check').forEach((checkbox) => {
        checkbox.addEventListener('change', () => {
            id_seleccionado = checkbox.dataset.value;
            tipo_seleccionado = checkbox.dataset.param;
            renderChips(chipsContainer);
            applyFilters();
        });
    });

    // Buscador de texto → navegar al escribir (con debounce)
    const searchInput = qsMaybe('[data-filter-search]');

    if (searchInput) {
        searchInput.addEventListener('input', debounce(() => {
            applyFilters();
        }, 400));
    }

    // Delegación de clicks en el área de chips
    chipsContainer.addEventListener('click', (e) => {
        const clearBtn = /** @type {HTMLElement} */ (e.target).closest('.cv-active-filters__clear');
        const removeBtn = /** @type {HTMLElement} */ (e.target).closest('.cv-active-filters__chip button');

        if (clearBtn) {
            clearAllFilters(chipsContainer);
        }

        if (removeBtn) {
            removeChipFilter(removeBtn, chipsContainer);
        }
    });
}


/**
 * Desmarca todos los checkboxes y limpia los chips, luego navega.
 *
 * @param {HTMLElement} chipsContainer  Contenedor de chips activos
 * @returns {void}
 */
function clearAllFilters(chipsContainer) {
    qsAll('.cv-filter-item__check').forEach((cb) => {
        /** @type {HTMLInputElement} */ (cb).checked = false;
    });

    const searchInput = /** @type {HTMLInputElement|null} */ (
        qsMaybe('[data-filter-search]')
    );

    if (searchInput) {
        searchInput.value = '';
    }

    renderChips(chipsContainer);
    applyFilters();
}

/**
 * Desmarca el checkbox correspondiente al chip eliminado y navega.
 *
 * @param {HTMLElement} removeBtn       Botón × del chip
 * @param {HTMLElement} chipsContainer  Contenedor de chips activos
 * @returns {void}
 */
function removeChipFilter(removeBtn, chipsContainer) {
    const chip = removeBtn.closest('.cv-active-filters__chip');
    const param = /** @type {HTMLElement} */ (chip)?.dataset.param;
    const value = /** @type {HTMLElement} */ (chip)?.dataset.value;

    if (param && value) {
        qsAll('.cv-filter-item__check').forEach((cb) => {
            const input = /** @type {HTMLInputElement} */ (cb);

            if (input.dataset.param === param && input.dataset.value === value) {
                input.checked = false;
            }
        });
    }

    renderChips(chipsContainer);
    applyFilters();
}

// ── Navegación GET ────────────────────────────────────────────────────────────

/**
 * Recoge todos los filtros activos y navega a la URL del catálogo con los
 * parámetros GET correspondientes.
 *
 * Parámetros construidos: cat, price, level, tag, q
 *
 * @returns {void}
 */
function applyFilters() {

    const params = new URLSearchParams();

    // Recoger todos los checkboxes marcados
    /** @type {HTMLInputElement[]} */
    const checked = /** @type {HTMLInputElement[]} */ (
        qsAll('.cv-filter-item__check').filter(
            (cb) => /** @type {HTMLInputElement} */ (cb).checked
        )
    );

    checked.forEach((cb) => {
        const param = cb.dataset.param;
        const value = cb.dataset.value;
        if (param && value) {
            // Múltiples valores del mismo param se añaden como param[]=v1&param[]=v2
            // Moodle optional_param lee solo el primero, así que por ahora:
            // Si ya existe el param, lo sobrescribe (comportamiento radio)
            // Para multi-select real habría que usar param[] en PHP
            if (param == tipo_seleccionado && id_seleccionado == value){
                params.set(param, value);
            }else if (param != tipo_seleccionado){
                params.set(param, value);
            }

        }
    });

    // Añadir q del buscador de texto
    const searchInput = /** @type {HTMLInputElement|null} */ (
        qsMaybe('[data-filter-search]')
    );

    if (searchInput?.value.trim()) {
        params.set('q', searchInput.value.trim());
    }

    // Navegar
    const qs = params.toString();
    window.location.href = qs ? `${catalogUrl}?${qs}` : catalogUrl;
}

// ── Chips visuales ────────────────────────────────────────────────────────────

/**
 * Renderiza los chips de filtros activos en el contenedor dado.
 *
 * @param {HTMLElement} container  Elemento `.cv-active-filters`
 * @returns {void}
 */
function renderChips(container) {
    container.innerHTML = '';

    /** @type {HTMLInputElement[]} */
    const active = /** @type {HTMLInputElement[]} */ (
        qsAll('.cv-filter-item__check').filter(
            (cb) => /** @type {HTMLInputElement} */ (cb).checked
        )
    );

    // Texto del buscador como chip
    const searchInput = /** @type {HTMLInputElement|null} */ (
        qsMaybe('[data-filter-search]')
    );

    const searchVal = searchInput?.value.trim() ?? '';

    if (active.length === 0 && !searchVal) {
        return;
    }

    // Chip por cada checkbox activo
    active.forEach((cb) => {
        const label = cb.closest('.cv-filter-item')
            ?.querySelector('.cv-filter-item__label')
            ?.textContent
            ?.trim() ?? '';

        // Guardamos param+value en el chip para poder desmarcar al quitar
        container.appendChild(
            buildChip(label, cb.dataset.param ?? '', cb.dataset.value ?? '')
        );
    });

    // Chip del buscador de texto
    if (searchVal) {
        container.appendChild(buildChip(`"${searchVal}"`, 'q', searchVal));
    }

    const clearBtn = document.createElement('button');
    clearBtn.className = 'cv-active-filters__clear';
    clearBtn.textContent = 'Borrar filtros';
    container.appendChild(clearBtn);
}


/**
 * Construye un elemento chip para un filtro activo.
 *
 * @param {string} label  Texto visible del chip
 * @param {string} param  Parámetro GET (cat, price, level, tag, q)
 * @param {string} value  Valor del parámetro
 * @returns {HTMLElement}
 */
function buildChip(label, param, value) {
    const chip = document.createElement('div');
    chip.className = 'cv-active-filters__chip';
    chip.dataset.label = label;
    chip.dataset.param = param;
    chip.dataset.value = value;
    chip.innerHTML = `${label}<button aria-label="Quitar filtro ${label}">×</button>`;
    return chip;
}

// ── Toggle sidebar móvil ──────────────────────────────────────────────────────

/**
 * Inicializa el toggle del sidebar de filtros en móvil.
 *
 * @returns {void}
 */
function initMobileToggle() {
    const toggle  = qsMaybe('.cv-results-bar__mobile-toggle');
    const sidebar = qsMaybe('.cv-catalog__sidebar');
    const overlay = qsMaybe('#cv-overlay');

    if (!toggle || !sidebar) {
        return;
    }

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
 * Inicializa el selector de ordenación. Al cambiar renavega con sort=valor.
 *
 * @returns {void}
 */
function initSortSelect() {
    qsMaybe('.cv-results-bar__select')?.addEventListener('change', (e) => {
        const val = /** @type {HTMLSelectElement} */ (e.target).value;
        const current = new URLSearchParams(window.location.search);
        current.set('sort', val);
        window.location.href = `${catalogUrl}?${current.toString()}`;
    });
}

// ── Helpers ───────────────────────────────────────────────────────────────────

/**
 * Crea una versión con debounce de la función dada.
 *
 * @param {() => void} fn      Función a ejecutar
 * @param {number}     delay   Milisegundos de espera
 * @returns {() => void}
 */
function debounce(fn, delay) {
    /** @type {ReturnType<typeof setTimeout>} */
    let timer;

    return function () {
        clearTimeout(timer);
        timer = setTimeout(() => {
            fn();
        }, delay);
    };
}
