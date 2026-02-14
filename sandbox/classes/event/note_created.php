<?php
namespace sandbox\classes\event;

defined('MOODLE_INTERNAL') || die();

class note_created extends \core\event\base
{

    protected function init()
    {
        $this->data['crud'] = 'c';                        // c=create, r=read, u=update, d=delete
        $this->data['edulevel'] = self::LEVEL_OTHER;      // LEVEL_TEACHING, LEVEL_PARTICIPATING, LEVEL_OTHER
        $this->data['objecttable'] = 'sandbox_ddl_notes'; // Tabla relacionada (opcional)
    }

    public static function get_name()
    {
        return 'Nota creada (sandbox)';
    }

    public function get_description()
    {
        return "El usuario con ID '{$this->userid}' creó la nota con ID '{$this->objectid}'.";
    }

    public function get_url()
    {
        return new \moodle_url('/local/sandbox/ej_events.php', ['id' => $this->objectid]);
    }
}