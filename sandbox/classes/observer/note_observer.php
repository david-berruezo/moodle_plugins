<?php
namespace sandbox\classes\observer;

defined('MOODLE_INTERNAL') || die();

class note_observer
{

    /**
     * Observador para el evento note_created
     */
    public static function on_note_created(\sandbox\classes\event\note_created $event)
    {
        // Aquí puedes hacer lo que quieras cuando se crea una nota:
        // - Enviar una notificación
        // - Registrar en un log externo
        // - Actualizar un contador

        $data = $event->get_data();
        // En un plugin real, harías algo útil aquí.
        // Por ahora, solo registramos en error_log para demostrar que funciona.
        error_log("[SANDBOX OBSERVER] Nota creada: objectid={$data['objectid']}, userid={$data['userid']}");
    }

    /**
     * Observador para eventos del core: usuario logueado
     */
    public static function on_user_loggedin(\core\event\user_loggedin $event)
    {
        error_log("[SANDBOX OBSERVER] Usuario logueado: userid={$event->userid}");
    }
}