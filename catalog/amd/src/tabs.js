// =============================================================================
// TABS — sistema de pestañas genérico
// Uso: <div data-tabs> contiene .cv-tabs__btn[data-tab="id"] y .cv-tab-panel[data-tab="id"]
// =============================================================================
import { qsAll, qsMaybe } from './utils';

/**
 * Inicializa todos los grupos de tabs de la página.
 * Cada grupo debe tener un ancestro común con [data-tabs].
 */
export function initTabs() {
    qsAll('[data-tabs]').forEach(initTabGroup);
}

/**
 * @param {HTMLElement} group
 */
function initTabGroup(group) {
    const btns   = qsAll('.cv-tabs__btn', group);
    const panels = qsAll('.cv-tab-panel', group);

    if (!btns.length){return;}

    /**
     * @param {string} targetId
     */
    function activate(targetId) {
        btns.forEach((btn) => {
            const isActive = btn.dataset.tab === targetId;
            btn.classList.toggle('is-active', isActive);
            btn.setAttribute('aria-selected', String(isActive));
        });

        panels.forEach((panel) => {
            panel.classList.toggle('is-active', panel.dataset.tab === targetId);
        });
    }

    btns.forEach((btn) => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.tab;
            if (id){activate(id);}
        });
    });

    // Activar la primera pestaña si ninguna está marcada
    const firstActive = btns.find((b) => b.classList.contains('is-active'));
    if (!firstActive && btns[0]?.dataset.tab) {
        activate(btns[0].dataset.tab);
    }
}

/**
 * Activa una pestaña específica programáticamente.
 * @param {string} tabId
 * @param {HTMLElement|Document} [root=document]
 */
export function activateTab(tabId, root = document) {
    const btn = qsMaybe(`[data-tab="${tabId}"].cv-tabs__btn`, root);
    btn?.click();
}
