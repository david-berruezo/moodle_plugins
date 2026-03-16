// =============================================================================
// ACCORDION — currículum del curso + FAQ
// Soporta: cv-curriculum__section-header / cv-plan-faq__question
// =============================================================================
import { qsAll } from './utils';

/**
 * Inicializa todos los acordeones de la página.
 * Selector genérico: [data-accordion-trigger]
 * El siguiente sibling del trigger es el panel colapsable.
 */
export function initAccordions() {
    // Currículum del curso
    qsAll('.cv-curriculum__section-header').forEach((header) => {
        initSingleAccordion(header, '.cv-curriculum__section-body', 'is-open');
    });

    // Player sidebar sections
    qsAll('.cv-player-sidebar__section-header').forEach((header) => {
        // El panel en el player no colapsa por defecto, se controla por clase
        header.addEventListener('click', () => {
            const body = header.closest('.cv-player-sidebar__section')
                               ?.querySelector('.cv-player-sidebar__lessons');
            if (body) {
                const isOpen = header.classList.toggle('is-open');
                body.style.display = isOpen ? '' : 'none';
            }
        });
    });

    // FAQ (plan personal, etc.)
    qsAll('.cv-plan-faq__question').forEach((btn) => {
        initSingleAccordion(btn, '.cv-plan-faq__answer', 'is-open');
    });

    // Acordeón genérico [data-accordion-trigger]
    qsAll('[data-accordion-trigger]').forEach((trigger) => {
        initSingleAccordion(trigger, '[data-accordion-body]', 'is-open', true);
    });
}

/**
 * @param {HTMLElement} trigger   botón que dispara open/close
 * @param {string}      bodySelector  selector del panel (relativo al trigger.parentElement)
 * @param {string}      openClass  clase CSS de estado abierto
 * @param {boolean}     [useDataSelector]  usar data-accordion-body dentro del mismo contenedor
 */
function initSingleAccordion(trigger, bodySelector, openClass, useDataSelector = false) {
    trigger.addEventListener('click', () => {
        const container = trigger.closest('[data-accordion]') ?? trigger.parentElement;
        const body = useDataSelector
            ? container?.querySelector('[data-accordion-body]')
            : trigger.nextElementSibling?.matches(bodySelector.replace(/^\./, '').split(' ')[0])
                ? trigger.nextElementSibling
                : trigger.parentElement?.querySelector(bodySelector);

        if (!body){
            return;
        }

        const isOpen = trigger.classList.toggle('is-open');
        body.classList.toggle(openClass, isOpen);
        trigger.setAttribute('aria-expanded', String(isOpen));
    });
}
