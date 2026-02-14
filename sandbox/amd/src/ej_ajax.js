/**
 * Ejercicio 2: core/ajax
 *
 * @module     local_sandbox/ej_ajax
 * @copyright  2026 Tu Nombre
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/ajax', 'core/notification'], function(Ajax, Notification) {
    'use strict';

    /**
     * Inicializa los event listeners.
     */
    function init() {

        // ---------------------------------------------------
        // 1. Llamada simple a un Web Service
        // ---------------------------------------------------

        document.getElementById('btn-call-ws').addEventListener('click', function() {

            var name = document.getElementById('input-name').value;
            var timeofday = document.getElementById('select-time').value;

            // Ajax.call() recibe un ARRAY de objetos.
            // Cada objeto es una llamada a un Web Service.
            // Devuelve un ARRAY de Promises (una por llamada).
            var promises = Ajax.call([
                {
                    methodname: 'local_sandbox_get_greeting',  // Nombre del WS
                    args: {                                     // Parámetros
                        name: name,
                        timeofday: timeofday
                    }
                }
            ]);

            // promises[0] corresponde a la primera (y única) llamada
            promises[0]
                .then(function(response) {
                    // response contiene lo que devuelve execute_returns()
                    var resultDiv = document.getElementById('ws-result');
                    resultDiv.style.display = 'block';
                    resultDiv.className = 'mt-3 alert alert-success';
                    resultDiv.innerHTML =
                        '<strong>Respuesta del servidor:</strong><br>' +
                        'Greeting: ' + response.greeting + '<br>' +
                        'Timestamp: ' + response.timestamp + '<br>' +
                        'Lang: ' + response.lang;

                    window.console.log('[ej_ajax] Respuesta:', response);
                    return response;
                })
                .catch(function(error) {
                    // Si algo falla, Notification.exception muestra el error
                    Notification.exception(error);
                });
        });

        // ---------------------------------------------------
        // 2. Llamada batch (múltiples WS en una sola petición HTTP)
        // ---------------------------------------------------

        document.getElementById('btn-call-batch').addEventListener('click', function() {

            // Ajax.call() con MÚLTIPLES objetos = 1 sola petición HTTP
            // Esto es mucho más eficiente que hacer 3 llamadas separadas
            var promises = Ajax.call([
                {
                    methodname: 'local_sandbox_get_greeting',
                    args: { name: 'Alice', timeofday: 'morning' }
                },
                {
                    methodname: 'local_sandbox_get_greeting',
                    args: { name: 'Bob', timeofday: 'afternoon' }
                },
                {
                    methodname: 'local_sandbox_get_greeting',
                    args: { name: 'Charlie', timeofday: 'evening' }
                }
            ]);

            // Usar Promise.all para esperar a las 3 respuestas
            Promise.all(promises)
                .then(function(results) {
                    var resultDiv = document.getElementById('batch-result');
                    resultDiv.style.display = 'block';
                    resultDiv.className = 'mt-3 alert alert-info';

                    var html = '<strong>Resultados del batch (3 llamadas, 1 petición HTTP):</strong><br>';
                    results.forEach(function(r, index) {
                        html += (index + 1) + '. ' + r.greeting + '<br>';
                    });
                    resultDiv.innerHTML = html;

                    return results;
                })
                .catch(function(error) {
                    Notification.exception(error);
                });
        });

        // ---------------------------------------------------
        // 3. Manejo de errores
        // ---------------------------------------------------

        document.getElementById('btn-call-error').addEventListener('click', function() {

            var promises = Ajax.call([
                {
                    methodname: 'local_sandbox_funcion_que_no_existe',
                    args: {}
                }
            ]);

            promises[0]
                .then(function(response) {
                    // No debería llegar aquí
                    window.console.log('Respuesta inesperada:', response);
                    return response;
                })
                .catch(function(error) {
                    // Mostramos el error de dos formas:

                    // 1. Con Notification.exception (diálogo modal de Moodle)
                    Notification.exception(error);

                    // 2. Manualmente en nuestro div
                    var resultDiv = document.getElementById('error-result');
                    resultDiv.style.display = 'block';
                    resultDiv.className = 'mt-3 alert alert-danger';
                    resultDiv.innerHTML =
                        '<strong>Error capturado:</strong><br>' +
                        'Mensaje: ' + error.message + '<br>' +
                        'Error code: ' + (error.errorcode || 'N/A');
                });
        });

        window.console.log('[ej_ajax] Módulo inicializado correctamente.');
    }

    return {
        init: init
    };
});