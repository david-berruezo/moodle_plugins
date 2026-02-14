/**
 * Ejercicio 3: core/templates
 *
 * @module     local_sandbox/ej_templates
 * @copyright  2026 Tu Nombre
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/templates', 'core/notification'], function(Templates, Notification) {
    'use strict';

    /**
     * Initialise the templates exercise.
     */
    function init() {

        // ---------------------------------------------------
        // 1. Renderizar un template simple (greeting_card)
        // ---------------------------------------------------

        document.getElementById('btn-render-card').addEventListener('click', function() {

            var name = document.getElementById('tpl-name').value;
            var greeting = document.getElementById('tpl-greeting').value;

            // El "context" es el objeto de datos que recibe el template Mustache
            var context = {
                name: name,
                greeting: greeting,
                timestamp: Math.floor(Date.now() / 1000),
                cssclass: 'border-success'
            };

            // Templates.render(templateName, context) → Promise<string>
            // templateName sigue el formato: componente/nombretemplate
            Templates.render('local_sandbox/greeting_card', context)
                .then(function(html) {
                    // html es el string HTML renderizado
                    document.getElementById('card-output').innerHTML = html;
                    return html;
                })
                .catch(function(error) {
                    Notification.exception(error);
                });
        });

        // ---------------------------------------------------
        // 2. Renderizar lista con datos (user_list)
        // ---------------------------------------------------

        document.getElementById('btn-render-list').addEventListener('click', function() {

            var context = {
                title: 'Development Team',
                hasusers: true,
                count: 3,
                users: [
                    { name: 'Alice García',   email: 'alice@example.com',   role: 'Admin' },
                    { name: 'Bob Martínez',   email: 'bob@example.com',     role: 'Teacher' },
                    { name: 'Carol López',    email: 'carol@example.com',   role: 'Student' }
                ]
            };

            Templates.render('local_sandbox/user_list', context)
                .then(function(html) {
                    document.getElementById('list-output').innerHTML = html;
                    return html;
                })
                .catch(function(error) {
                    Notification.exception(error);
                });
        });

        // ---------------------------------------------------
        // 3. Renderizar lista vacía (para ver la sección {{^hasusers}})
        // ---------------------------------------------------

        document.getElementById('btn-render-empty').addEventListener('click', function() {

            var context = {
                title: 'Empty Team',
                hasusers: false,
                count: 0,
                users: []
            };

            Templates.render('local_sandbox/user_list', context)
                .then(function(html) {
                    document.getElementById('list-output').innerHTML = html;
                    return html;
                })
                .catch(function(error) {
                    Notification.exception(error);
                });
        });

        // ---------------------------------------------------
        // 4. renderForPromise — devuelve {html, js}
        //    Útil cuando el template contiene bloques {{#js}}
        // ---------------------------------------------------

        document.getElementById('btn-render-promise').addEventListener('click', function() {

            var context = {
                name: 'Tester',
                greeting: 'This was rendered with renderForPromise!',
                timestamp: Math.floor(Date.now() / 1000),
                cssclass: 'border-info'
            };

            // renderForPromise devuelve un objeto {html: string, js: string}
            Templates.renderForPromise('local_sandbox/greeting_card', context)
                .then(function(result) {
                    // result.html → el HTML renderizado
                    // result.js  → el JS del template (si tiene {{#js}}...{{/js}})

                    var output = document.getElementById('promise-output');
                    output.innerHTML = result.html;

                    // Ejecutar el JS del template (si lo hubiera)
                    Templates.runTemplateJS(result.js);

                    // Mostrar info en consola
                    window.console.log('[ej_templates] HTML length:', result.html.length);
                    window.console.log('[ej_templates] JS length:', result.js.length);

                    return result;
                })
                .catch(function(error) {
                    Notification.exception(error);
                });
        });

        window.console.log('[ej_templates] Módulo inicializado correctamente.');
    }

    return {
        init: init
    };
});