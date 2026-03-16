// =============================================================================
// explore.js — Mega-menú "Explorar" con jerarquía dinámica
// amd/src/explore.js
// =============================================================================
//
// HTML esperado (generado por home.mustache + get_menu_data()):
//
//   Triggers (col--main):
//     <button class="cv-explore__item--trigger" data-cat="cat-1">
//
//   Paneles (col--sub):
//     <div class="cv-explore__sub" id="cv-sub-cat-1">
//
//   Popper contenedor (popper.js añade/quita "is-open"):
//     <div class="cv-popper__content" id="cv-explore-menu">
//
// =============================================================================
import { qsAll, qsMaybe } from './utils';

/**
 * Activa el panel correspondiente a catKey y desactiva los demás.
 *
 * @param {string} catKey  Valor de data-cat del trigger, ej: "cat-1"
 * @returns {void}
 */
function showPanel(catKey) {
    qsAll('.cv-explore__item--trigger').forEach((btn) => {
        btn.classList.remove('is-active');
        btn.setAttribute('aria-expanded', 'false');
    });

    qsAll('.cv-explore__sub').forEach((panel) => {
        panel.classList.remove('is-active');
    });

    const activeTrigger = qsMaybe(`.cv-explore__item--trigger[data-cat="${catKey}"]`);
    activeTrigger?.classList.add('is-active');
    activeTrigger?.setAttribute('aria-expanded', 'true');

    const activePanel = document.getElementById(`cv-sub-${catKey}`);
    activePanel?.classList.add('is-active');
}

/**
 * Inicializa el mega-menú Explorar: hover en triggers, observer de apertura
 * y subtrees expandibles en el drawer móvil.
 *
 * @returns {void}
 */
export function initExplore() {

    // ── Desktop: hover/focus sobre triggers → cambiar panel ──────────────────
    qsAll('.cv-explore__item--trigger').forEach((btn) => {

        btn.addEventListener('mouseenter', () => {
            const catKey = /** @type {HTMLElement} */ (btn).dataset.cat;
            if (catKey) {
                showPanel(catKey);
            }
        });

        btn.addEventListener('focus', () => {
            const catKey = /** @type {HTMLElement} */ (btn).dataset.cat;
            if (catKey) {
                showPanel(catKey);
            }
        });
    });

    // ── Activar el primer panel cuando el popper se abre ─────────────────────
    const exploreMenu = document.getElementById('cv-explore-menu');

    if (!exploreMenu) {
        return;
    }

    const observer = new MutationObserver(() => {
        if (exploreMenu.classList.contains('is-open')) {
            const firstTrigger = /** @type {HTMLElement|null} */ (
                exploreMenu.querySelector('.cv-explore__item--trigger')
            );
            if (firstTrigger?.dataset.cat) {
                showPanel(firstTrigger.dataset.cat);
            }
        }
    });

    observer.observe(exploreMenu, {
        attributes: true,
        attributeFilter: ['class'],
    });

    // ── Mobile: drawer subtrees expandibles ──────────────────────────────────
    qsAll('[data-drawer-cat]').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            const key = /** @type {HTMLElement} */ (trigger).dataset.drawerCat;
            const subtree = document.getElementById(`drawer-sub-${key}`);

            if (!subtree) {
                return;
            }

            const isOpen = !subtree.hidden;
            subtree.hidden = isOpen;
            trigger.setAttribute('aria-expanded', String(!isOpen));

            const chevron = trigger.querySelector('svg');

            if (chevron) {
                chevron.style.transition = 'transform 0.2s ease';
                chevron.style.transform = isOpen ? '' : 'rotate(180deg)';
            }
        });
    });
}