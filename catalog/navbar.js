/**
 * local_catalog — navbar.js
 * Dropdowns (hover + teclado) y drawer móvil
 * Incluir en cada PHP: $PAGE->requires->js(new moodle_url('/local/catalog/navbar.js'));
 */
(function () {
    'use strict';

    // =========================================================================
    // DESKTOP POPPERS (hover + delay)
    // =========================================================================
    var DELAY = 200; // ms de gracia antes de cerrar

    document.querySelectorAll('.cv-popper').forEach(function (popper) {
        var content = popper.querySelector('.cv-popper__content');
        if (!content) return;

        var timer;

        function open() {
            clearTimeout(timer);
            // Cerrar todos los demás
            document.querySelectorAll('.cv-popper__content.is-open').forEach(function (c) {
                if (c !== content) c.classList.remove('is-open');
            });
            content.classList.add('is-open');

            // ARIA
            var trigger = popper.querySelector('[aria-expanded]');
            if (trigger) trigger.setAttribute('aria-expanded', 'true');
        }

        function close() {
            timer = setTimeout(function () {
                content.classList.remove('is-open');
                var trigger = popper.querySelector('[aria-expanded]');
                if (trigger) trigger.setAttribute('aria-expanded', 'false');
            }, DELAY);
        }

        popper.addEventListener('mouseenter', open);
        popper.addEventListener('mouseleave', close);
        content.addEventListener('mouseenter', function () { clearTimeout(timer); });
        content.addEventListener('mouseleave', close);

        // Click para accesibilidad / touch
        var btn = popper.querySelector('button[aria-expanded], a[aria-expanded]');
        if (btn) {
            btn.addEventListener('click', function (e) {
                if (content.classList.contains('is-open')) {
                    content.classList.remove('is-open');
                    btn.setAttribute('aria-expanded', 'false');
                } else {
                    open();
                    e.preventDefault(); // no navega al hacer click en toggle
                }
            });
        }
    });

    // Cerrar todo al presionar Escape
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.cv-popper__content.is-open').forEach(function (c) {
                c.classList.remove('is-open');
            });
        }
    });

    // Cerrar al click fuera
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.cv-popper')) {
            document.querySelectorAll('.cv-popper__content.is-open').forEach(function (c) {
                c.classList.remove('is-open');
            });
        }
    });

    // =========================================================================
    // EXPLORAR — subcategorías (hover en nivel 1 → mostrar nivel 2)
    // =========================================================================
    document.querySelectorAll('.cv-explore__item--trigger').forEach(function (btn) {
        btn.addEventListener('mouseenter', function () {
            var catId = btn.dataset.cat;
            var level2 = document.getElementById('cv-explore-level2');
            if (!level2) return;

            // Quitar activos
            document.querySelectorAll('.cv-explore__item--trigger.is-active').forEach(function (b) {
                b.classList.remove('is-active');
                b.setAttribute('aria-expanded', 'false');
            });
            document.querySelectorAll('.cv-explore__subpanel.is-visible').forEach(function (p) {
                p.classList.remove('is-visible');
            });

            // Activar
            btn.classList.add('is-active');
            btn.setAttribute('aria-expanded', 'true');
            var panel = document.getElementById('cv-explore-sub-' + catId);
            if (panel) panel.classList.add('is-visible');
        });
    });

    // =========================================================================
    // DRAWER MÓVIL
    // =========================================================================
    var drawer  = document.getElementById('cv-drawer');
    var overlay = document.getElementById('cv-drawer-overlay');
    var hamburger = document.getElementById('cv-hamburger');
    var closeBtn  = document.getElementById('cv-drawer-close');

    function openDrawer() {
        if (!drawer) return;
        drawer.classList.add('is-open');
        overlay.classList.add('is-open');
        hamburger && hamburger.setAttribute('aria-expanded', 'true');
        drawer.focus();
        document.body.style.overflow = 'hidden';
    }

    function closeDrawer() {
        if (!drawer) return;
        drawer.classList.remove('is-open');
        overlay.classList.remove('is-open');
        hamburger && hamburger.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
        // Volver al panel principal
        showPanel('main');
    }

    hamburger && hamburger.addEventListener('click', openDrawer);
    closeBtn  && closeBtn.addEventListener('click', closeDrawer);
    overlay   && overlay.addEventListener('click', closeDrawer);

    // Escape cierra drawer
    drawer && drawer.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeDrawer();
    });

    // ── Navegación entre paneles ──────────────────────────────────────────
    function showPanel(id) {
        document.querySelectorAll('.cv-drawer__panel').forEach(function (p) {
            p.classList.remove('is-active');
        });
        var target = document.getElementById('cv-drawer-' + id);
        if (target) target.classList.add('is-active');
    }

    // Botones que abren sub-paneles
    document.querySelectorAll('[data-panel]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            showPanel(btn.dataset.panel);
        });
    });

    // Botones "volver"
    document.querySelectorAll('[data-panel-back]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            showPanel(btn.dataset.panelBack);
        });
    });

    // =========================================================================
    // BÚSQUEDA MÓVIL
    // =========================================================================
    var mobileSearch      = document.getElementById('cv-mobile-search');
    var mobileSearchOpen  = document.getElementById('cv-mobile-search-toggle');
    var mobileSearchClose = document.getElementById('cv-mobile-search-close');

    mobileSearchOpen && mobileSearchOpen.addEventListener('click', function () {
        mobileSearch.classList.add('is-open');
        mobileSearch.setAttribute('aria-hidden', 'false');
        var input = mobileSearch.querySelector('input');
        if (input) { input.focus(); }
        mobileSearchOpen.setAttribute('aria-expanded', 'true');
    });

    mobileSearchClose && mobileSearchClose.addEventListener('click', function () {
        mobileSearch.classList.remove('is-open');
        mobileSearch.setAttribute('aria-hidden', 'true');
        mobileSearchOpen && mobileSearchOpen.setAttribute('aria-expanded', 'false');
    });

})();
