<?php
// =============================================================================
// STRINGS REGISTRO — añadir a lang/es/local_catalog.php
// =============================================================================

// ── Registro ──────────────────────────────────────────────────────────────────
$string['register_title']            = 'Crea tu cuenta gratis';
$string['register_sub']              = 'Únete a los profesionales de formación del Grupo Aubay';
$string['register_cta']              = 'Crear cuenta gratis';
$string['register_already']          = '¿Ya tienes cuenta?';

// Campos
$string['reg_firstname']             = 'Nombre';
$string['reg_firstname_placeholder'] = 'Tu nombre';
$string['reg_lastname']              = 'Apellidos';
$string['reg_lastname_placeholder']  = 'Tus apellidos';
$string['reg_email']                 = 'Email';
$string['reg_email_placeholder']     = 'tu@empresa.com';
$string['reg_password']              = 'Contraseña';
$string['reg_password_placeholder']  = 'Mínimo 8 caracteres';
$string['reg_password_hint']         = 'Usa letras, números y símbolos para mayor seguridad';

// Checkboxes
$string['reg_accept_prefix']         = 'Acepto los';
$string['reg_accept_and']            = 'y la';
$string['reg_marketing']             = 'Quiero recibir novedades y recomendaciones de cursos por email';

// Errores de validación
$string['reg_error_firstname']       = 'El nombre es obligatorio.';
$string['reg_error_lastname']        = 'Los apellidos son obligatorios.';
$string['reg_error_email_empty']     = 'El email es obligatorio.';
$string['reg_error_email_invalid']   = 'Introduce un email válido.';
$string['reg_error_email_exists']    = 'Ya existe una cuenta con ese email. ¿Quieres iniciar sesión?';
$string['reg_error_password_empty']  = 'La contraseña es obligatoria.';
$string['reg_error_terms']           = 'Debes aceptar los términos de uso para continuar.';
$string['reg_error_general']         = 'Ha ocurrido un error al crear la cuenta. Inténtalo de nuevo.';

// Éxito
$string['reg_success_title']         = '¡Cuenta creada!';
$string['reg_success_sub']           = 'Te hemos enviado un email de confirmación. Revisa tu bandeja de entrada y haz clic en el enlace para activar tu cuenta.';

// Varios
$string['toggle_password']           = 'Mostrar/ocultar contraseña';



/*
// =============================================================================
// SCSS — añadir a scss/components/_auth.scss
// Fila de dos columnas para nombre + apellidos
// =============================================================================

.cv-form {

    // Fila de dos columnas (nombre + apellidos)
    &__row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: t.$space-4;

        @include m.below(m.$bp-sm) {
            grid-template-columns: 1fr;
        }
    }

    // Checkbox personalizado
    &__checkbox {
        display: flex;
        align-items: flex-start;
        gap: t.$space-2;
        cursor: pointer;
    }

    &__checkbox-input {
        flex-shrink: 0;
        width: 1.8rem;
        height: 1.8rem;
        margin-top: 0.1rem;
        accent-color: t.$color-brand;
        cursor: pointer;

        &--error {
            outline: 2px solid #dc2626;
            outline-offset: 2px;
        }
    }

    &__checkbox-label {
        font-size: t.$text-sm;
        color: t.$color-text-body;
        line-height: 1.5;

        a {
            color: t.$color-brand;
            font-weight: 600;
            text-decoration: none;
            &:hover { text-decoration: underline; }
        }
    }
}

// Pantalla de éxito
.cv-auth {
    &__success {
        text-align: center;
        padding-block: t.$space-6;
    }

    &__success-icon {
        @include m.flex-center;
        width: 7.2rem;
        height: 7.2rem;
        background: #f0fdf4;
        border-radius: t.$radius-full;
        margin: 0 auto t.$space-6;
        color: #16a34a;
    }
}

// Página legal
.cv-legal {
    padding-block: t.$space-10;

    &__inner {
        max-width: 72rem;
        margin-inline: auto;
    }

    &__title {
        font-size: t.$text-4xl;
        font-weight: 700;
        color: t.$color-text;
        margin-bottom: t.$space-2;
    }

    &__meta {
        font-size: t.$text-sm;
        color: t.$color-text-muted;
        margin-bottom: t.$space-8;
        padding-bottom: t.$space-8;
        border-bottom: 1px solid t.$color-border;
    }

    &__content {
        h2 {
            font-size: t.$text-xl;
            font-weight: 700;
            color: t.$color-text;
            margin-top: t.$space-8;
            margin-bottom: t.$space-3;
        }
        p {
            font-size: t.$text-md;
            color: t.$color-text-body;
            line-height: 1.7;
            margin-bottom: t.$space-4;
        }
    }

    &__back {
        margin-top: t.$space-10;
        padding-top: t.$space-6;
        border-top: 1px solid t.$color-border;
    }

    &__back-link {
        font-size: t.$text-sm;
        color: t.$color-brand;
        text-decoration: none;
        &:hover { text-decoration: underline; }
    }
}
*/



/*
// =============================================================================
// .HTACCESS — añadir estas 4 líneas en la sección de páginas estáticas
// (antes del bloque de exclusión de archivos reales)
// =============================================================================

# Registro (antes del bloque de exclusión — igual que login)
RewriteRule ^registro/?$   /local/catalog/register.php [L,QSA]

# Páginas legales
RewriteRule ^privacidad/?$ /local/catalog/privacy.php  [L,QSA]
RewriteRule ^terminos/?$   /local/catalog/terms.php    [L,QSA]
*/
