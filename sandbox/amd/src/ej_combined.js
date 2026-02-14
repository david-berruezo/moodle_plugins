/**
 * Ejercicio 4: Combinando core/ajax + core/templates + core/notification
 *
 * Este es el PATRÓN REAL de desarrollo Moodle:
 * 1. El usuario interactúa con la página
 * 2. JS llama a un Web Service vía core/ajax
 * 3. La respuesta se renderiza con core/templates
 * 4. Se muestra una notificación con core/notification
 *
 * @module     local_sandbox/ej_combined
 * @copyright  2026 Tu Nombre
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(
    ['core/ajax', 'core/templates', 'core/notification'],
    function(Ajax, Templates, Notification) {
        'use strict';

        /** Contador de llamadas para el historial */
        var callCount = 0;

        /**
         * Muestra/oculta el spinner de carga.
         *
         * @param {boolean} show
         */
        function toggleLoading(show) {
            document.getElementById('combined-loading').style.display = show ? 'block' : 'none';
        }

        /**
         * Añade una entrada al historial de llamadas.
         *
         * @param {string} name
         * @param {string} timeofday
         * @param {boolean} success
         */
        function addToHistory(name, timeofday, success) {
            callCount++;
            var list = document.getElementById('history-list');
            var li = document.createElement('li');
            li.className = 'list-group-item list-group-item-' + (success ? 'success' : 'danger');
            li.textContent = '#' + callCount + ' — ' +
                name + ' (' + timeofday + ') — ' +
                (success ? 'OK' : 'ERROR') + ' — ' +
                new Date().toLocaleTimeString();
            // Insertar al principio
            list.insertBefore(li, list.firstChild);
        }

        /**
         * Flujo principal: AJAX → Template → Notification.
         *
         * @param {string} name
         * @param {string} timeofday
         */
        function generateGreeting(name, timeofday) {

            // Paso 1: Mostrar loading
            toggleLoading(true);
            document.getElementById('combined-output').innerHTML = '';

            // Paso 2: Llamar al Web Service vía core/ajax
            var promises = Ajax.call([
                {
                    methodname: 'local_sandbox_get_greeting',
                    args: {
                        name: name,
                        timeofday: timeofday
                    }
                }
            ]);

            promises[0]
                .then(function(response) {
                    // Paso 3: Preparar el contexto para el template
                    var cssClasses = {
                        morning:   'border-warning',
                        afternoon: 'border-success',
                        evening:   'border-primary'
                    };

                    var context = {
                        name: name,
                        greeting: response.greeting,
                        timestamp: response.timestamp,
                        cssclass: cssClasses[timeofday] || 'border-secondary'
                    };

                    // Paso 4: Renderizar el template
                    return Templates.renderForPromise('local_sandbox/greeting_card', context);
                })
                .then(function(result) {
                    // Paso 5: Inyectar el HTML en la página
                    toggleLoading(false);
                    document.getElementById('combined-output').innerHTML = result.html;
                    Templates.runTemplateJS(result.js);

                    // Paso 6: Mostrar notificación de éxito
                    Notification.addNotification({
                        message: 'Greeting generated successfully for ' + name + '!',
                        type: 'success'
                    });

                    // Paso 7: Añadir al historial
                    addToHistory(name, timeofday, true);

                    return result;
                })
                .catch(function(error) {
                    toggleLoading(false);

                    // Mostrar error con el sistema de notificaciones
                    Notification.addNotification({
                        message: 'Failed to generate greeting: ' + error.message,
                        type: 'error'
                    });

                    addToHistory(name, timeofday, false);

                    // También mostrar el diálogo modal de error (más detallado)
                    Notification.exception(error);
                });
        }

        /**
         * Inicializa el módulo.
         */
        function init() {
            document.getElementById('btn-combined').addEventListener('click', function() {
                var name = document.getElementById('combined-name').value.trim();
                var timeofday = document.getElementById('combined-time').value;

                if (!name) {
                    Notification.addNotification({
                        message: 'Please enter a name.',
                        type: 'warning'
                    });
                    return;
                }

                generateGreeting(name, timeofday);
            });

            window.console.log('[ej_combined] Módulo inicializado correctamente.');
        }

        return {
            init: init
        };
    }
);