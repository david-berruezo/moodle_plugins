// =============================================================================
// AUTH STATE — alterna visibilidad de elementos según estado de sesión
// En producción el estado lo inyecta PHP/Moodle en el HTML directamente,
// este módulo solo sirve para el demo estático.
// =============================================================================
import { qsMaybe } from './utils';

/**
 * @param isLogged
 */
function applyAuthState(isLogged) {
    const btnLogin  = document.getElementById('btn-login');
    const btnLogout = document.getElementById('btn-logout');
    if (btnLogin)  {toggle(btnLogin,  !isLogged);}
    if (btnLogout) {toggle(btnLogout,  isLogged);}
}

/**
 * Inicializa los botones de demo (Login / Logout).
 * Expone `window.setLoggedIn` para poder llamarlo desde el HTML de demo.
 */
export function initAuthDemo() {
    // Arrancar en estado "invitado"
    applyAuthState(false);

    // Exponer en window para los botones inline del HTML de demo
    window.setLoggedIn = (value) => applyAuthState(Boolean(value));
}

// ── Helper ────────────────────────────────────────────────────────────────────

/**
 *
 * @param id
 * @param visible
 * @param displayValue
 */
function toggle(id, visible, displayValue = 'flex') {
    const el = qsMaybe(`#${id}`);
    if (!el) {return;} // el elemento no existe en esta página → salir sin error
    el.style.display = visible ? displayValue : 'none';
}
