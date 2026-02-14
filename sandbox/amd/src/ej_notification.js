/**
 * Ejercicio 1: core/notification
 *
 * @module     local_sandbox/ej_notification
 * @copyright  2026 Tu Nombre
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/notification'], function(Notification) {
    'use strict';

    /**
     * Inicializa los event listeners de los botones.
     */
    function init() {
        // ---------------------------------------------------
        // 1. Notificaciones inline (aparecen arriba de la página)
        // ---------------------------------------------------

        document.getElementById('btn-success').addEventListener('click', function() {
            // addNotification añade una notificación visible en la página
            Notification.addNotification({
                message: 'Operación completada con éxito. Los datos se han guardado.',
                type: 'success'   // Tipos: 'success', 'error', 'warning', 'info'
            });
        });

        document.getElementById('btn-error').addEventListener('click', function() {
            Notification.addNotification({
                message: 'Error: no se pudo conectar con el servidor. Inténtalo de nuevo.',
                type: 'error'
            });
        });

        document.getElementById('btn-warning').addEventListener('click', function() {
            Notification.addNotification({
                message: 'Advertencia: los cambios no guardados se perderán.',
                type: 'warning'
            });
        });

        document.getElementById('btn-info').addEventListener('click', function() {
            Notification.addNotification({
                message: 'Información: el sistema se actualizará esta noche a las 02:00.',
                type: 'info'
            });
        });

        // ---------------------------------------------------
        // 2. Notificación con redirección
        //    fetchNotifications() recupera notificaciones pendientes de sesión
        // ---------------------------------------------------

        document.getElementById('btn-redirect').addEventListener('click', function() {
            // En un caso real, harías una llamada AJAX, guardarías algo,
            // y luego redirigirías. Aquí simulamos con addNotification
            // seguido de un reload.
            Notification.addNotification({
                message: 'Datos guardados. Recargando página...',
                type: 'success'
            });

            // Esperar 1.5 segundos y recargar
            setTimeout(function() {
                window.location.reload();
            }, 1500);
        });

        // ---------------------------------------------------
        // 3. Alert modal (diálogo que el usuario debe cerrar)
        // ---------------------------------------------------

        document.getElementById('btn-alert').addEventListener('click', function() {
            Notification.alert(
                'Título del Alert',                          // título
                'Este es un diálogo de alerta de Moodle. ' + // mensaje
                'El usuario debe cerrarlo para continuar.',
                'Entendido'                                  // texto del botón
            );
        });

        // ---------------------------------------------------
        // 4. Confirm modal (diálogo con Sí/No)
        // ---------------------------------------------------

        document.getElementById('btn-confirm').addEventListener('click', function() {
            Notification.confirm(
                'Confirmar acción',                           // título
                '¿Estás seguro de que quieres eliminar este elemento? ' +
                'Esta acción no se puede deshacer.',
                'Sí, eliminar',                               // texto botón confirmar
                'Cancelar',                                   // texto botón cancelar
                function() {
                    // Callback: el usuario pulsó "Sí"
                    var result = document.getElementById('confirm-result');
                    result.style.display = 'block';
                    result.className = 'mt-2 p-2 alert alert-success';
                    result.textContent = 'El usuario confirmó la eliminación.';
                },
                function() {
                    // Callback: el usuario pulsó "Cancelar"
                    var result = document.getElementById('confirm-result');
                    result.style.display = 'block';
                    result.className = 'mt-2 p-2 alert alert-warning';
                    result.textContent = 'El usuario canceló la acción.';
                }
            );
        });

        // Log para confirmar que el módulo se cargó
        window.console.log('[ej_notification] Módulo inicializado correctamente.');
    }

    // Exponemos la función init
    return {
        init: init
    };
});