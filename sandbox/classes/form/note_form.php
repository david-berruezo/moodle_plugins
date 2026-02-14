<?php
namespace sandbox\classes\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class note_form extends \moodleform
{

    /**
     * Definir los campos del formulario
     */
    protected function definition()
    {
        $mform = $this->_form;

        // Datos personalizados pasados al formulario
        $priorities = $this->_customdata['priorities'] ?? [];

        // --- Sección: Información de la nota ---
        $mform->addElement('header', 'general', 'Información de la Nota');

        // Campo de texto (input text)
        $mform->addElement('text', 'title', 'Título', ['size' => 50, 'maxlength' => 255]);
        $mform->setType('title', PARAM_TEXT); // Tipo PARAM para sanitización
        $mform->addRule('title', 'El título es obligatorio', 'required', null, 'client');
        $mform->addRule('title', 'Máximo 255 caracteres', 'maxlength', 255, 'client');
        $mform->addHelpButton('title', 'title', 'local_sandbox'); // Ayuda contextual (necesita string)

        // Textarea (editor simple)
        $mform->addElement('textarea', 'content', 'Contenido', ['rows' => 5, 'cols' => 60]);
        $mform->setType('content', PARAM_CLEANHTML);

        // Editor HTML completo (Atto/TinyMCE)
        $mform->addElement('editor', 'description_editor', 'Descripción Rich', [
            'rows' => 8,
            'maxfiles' => EDITOR_UNLIMITED_FILES,
        ]);
        $mform->setType('description_editor', PARAM_RAW); // El editor gestiona su propia limpieza

        // Select (dropdown)
        $mform->addElement('select', 'priority', 'Prioridad', $priorities);
        $mform->setDefault('priority', 1);

        // --- Sección: Opciones ---
        $mform->addElement('header', 'options', 'Opciones');

        // Checkbox
        $mform->addElement('advcheckbox', 'is_public', 'Nota pública', 'Visible para otros usuarios');
        $mform->setDefault('is_public', 0);

        // Radio buttons
        $radioarray = [];
        $radioarray[] = $mform->createElement('radio', 'status', '', 'Pendiente', 0);
        $radioarray[] = $mform->createElement('radio', 'status', '', 'En progreso', 1);
        $radioarray[] = $mform->createElement('radio', 'status', '', 'Completada', 2);
        $mform->addGroup($radioarray, 'statusgroup', 'Estado', ['<br>'], false);
        $mform->setDefault('status', 0);

        // Date selector
        $mform->addElement('date_selector', 'duedate', 'Fecha límite');

        // Date + time selector
        $mform->addElement('date_time_selector', 'reminder', 'Recordatorio', ['optional' => true]);

        // Hidden field
        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);

        // --- Botones ---
        $this->add_action_buttons(true, 'Guardar Nota');
    }

    /**
     * Validación personalizada del formulario
     */
    public function validation($data, $files)
    {
        $errors = parent::validation($data, $files);

        // Validación personalizada
        if (strlen(trim($data['title'])) < 3) {
            $errors['title'] = 'El título debe tener al menos 3 caracteres';
        }

        if (!empty($data['duedate']) && $data['duedate'] < time()) {
            $errors['duedate'] = 'La fecha límite no puede ser en el pasado';
        }

        return $errors;
    }
}