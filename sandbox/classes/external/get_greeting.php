<?php
namespace local_sandbox\external;

defined('MOODLE_INTERNAL') || die();

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * Función externa que devuelve un saludo personalizado.
 */
class get_greeting extends external_api
{

    /**
     * Define los parámetros de entrada.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters
    {
        return new external_function_parameters([
            'name' => new external_value(PARAM_TEXT, 'Nombre de la persona'),
            'timeofday' => new external_value(PARAM_ALPHA, 'Momento del día: morning, afternoon, evening',
                VALUE_DEFAULT, 'morning'),
        ]);
    }

    /**
     * Ejecuta la función.
     *
     * @param string $name Nombre de la persona
     * @param string $timeofday Momento del día
     * @return array
     */
    public static function execute(string $name, string $timeofday = 'morning'): array
    {
        // Validar parámetros.
        $params = self::validate_parameters(self::execute_parameters(), [
            'name' => $name,
            'timeofday' => $timeofday,
        ]);

        // Validar contexto.
        $context = \context_system::instance();
        self::validate_context($context);

        // Lógica de negocio.
        $greetings = [
            'morning' => 'Good morning',
            'afternoon' => 'Good afternoon',
            'evening' => 'Good evening',
        ];

        $greeting = $greetings[$params['timeofday']] ?? 'Hello';
        $message = "{$greeting}, {$params['name']}! Welcome to Moodle.";

        return [
            'greeting' => $message,
            'timestamp' => time(),
            'lang' => current_language(),
        ];
    }

    /**
     * Define la estructura de retorno.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure
    {
        return new external_single_structure([
            'greeting' => new external_value(PARAM_TEXT, 'El saludo generado'),
            'timestamp' => new external_value(PARAM_INT, 'Timestamp del servidor'),
            'lang' => new external_value(PARAM_ALPHA, 'Idioma actual'),
        ]);
    }
}