// =============================================================================
// MAIN — punto de entrada ES Module
// <script type="module" src="js/main.js"></script>
// =============================================================================

import { COURSES_TRENDING, COURSES_DATA_AI } from './data';
import { populateCarousel }                  from './card';
import { initCarousels }                     from './carousel';
import { initPoppers }                       from './popper';
import { initExplore }                       from './explore';
import { initDrawer }                        from './drawer';
import { initMobileSearch }                  from './search-mobile';
import { initAuthDemo }                      from './auth-state';
import { setCurrentYear }                    from './utils';

import { initFilters }                       from './filters';
import { initTabs }                          from './tabs';
import { initAccordions }                    from './accordion';

export const init = () => {
    // Navbar (presente en todas las páginas)
    initPoppers();
    initExplore();
    initDrawer();
    initMobileSearch();

// Home: carousels
    if (document.getElementById('carousel-trending-track')) {
        populateCarousel('carousel-trending-track', COURSES_TRENDING);
    }
    if (document.getElementById('carousel-data-track')) {
        populateCarousel('carousel-data-track', COURSES_DATA_AI);
    }
    initCarousels();

// Páginas interiores
    initFilters();
    initTabs();
    initAccordions();

// Utilidades
setCurrentYear('#footer-year');

// Demo auth (solo estático, eliminar en producción)
    initAuthDemo();
};