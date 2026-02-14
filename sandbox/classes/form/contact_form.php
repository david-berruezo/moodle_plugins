<?php
namespace sandbox\classes\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class contact_form extends \moodleform
{

    /**
     * Definir los campos del formulario
     */
    protected function definition()
    {
        $mform = $this->_form;

        // Datos personalizados pasados al formulario
        $categories = $this->_customdata['categories'] ?? [];

        // --- Sección: Datos de contacto ---
        $mform->addElement('header', 'contactinfo', 'Datos de Contacto');

        // Nombre
        $mform->addElement('text', 'fullname', 'Nombre completo', ['size' => 40, 'maxlength' => 100]);
        $mform->setType('fullname', PARAM_TEXT);
        $mform->addRule('fullname', 'El nombre es obligatorio', 'required', null, 'client');

        // Email
        $mform->addElement('text', 'email', 'Correo electrónico', ['size' => 40, 'maxlength' => 255]);
        $mform->setType('email', PARAM_EMAIL);
        $mform->addRule('email', 'El correo es obligatorio', 'required', null, 'client');
        $mform->addRule('email', 'Introduce un correo válido', 'email', null, 'client');

        // Teléfono (opcional)
        $mform->addElement('text', 'phone', 'Teléfono (opcional)', ['size' => 20, 'maxlength' => 20]);
        $mform->setType('phone', PARAM_TEXT);

        // --- Sección: Mensaje ---
        $mform->addElement('header', 'messageinfo', 'Detalle del Mensaje');

        // Categoría (select)
        $mform->addElement('select', 'category', 'Categoría', $categories);
        $mform->addRule('category', 'Selecciona una categoría', 'required', null, 'client');

        // Asunto
        $mform->addElement('text', 'subject', 'Asunto', ['size' => 50, 'maxlength' => 200]);
        $mform->setType('subject', PARAM_TEXT);
        $mform->addRule('subject', 'El asunto es obligatorio', 'required', null, 'client');

        // Mensaje
        $mform->addElement('textarea', 'message', 'Mensaje', ['rows' => 6, 'cols' => 60]);
        $mform->setType('message', PARAM_CLEANHTML);
        $mform->addRule('message', 'El mensaje es obligatorio', 'required', null, 'client');

        // --- Sección: Opciones adicionales ---
        $mform->addElement('header', 'extras', 'Opciones Adicionales');

        // Urgencia (radio buttons)
        $radioarray = [];
        $radioarray[] = $mform->createElement('radio', 'urgency', '', 'Baja', 0);
        $radioarray[] = $mform->createElement('radio', 'urgency', '', 'Normal', 1);
        $radioarray[] = $mform->createElement('radio', 'urgency', '', 'Alta', 2);
        $mform->addGroup($radioarray, 'urgencygroup', 'Urgencia', ['&nbsp;&nbsp;'], false);
        $mform->setDefault('urgency', 1);

        // Recibir copia por email
        $mform->addElement('advcheckbox', 'send_copy', 'Copia', 'Enviarme una copia del mensaje');
        $mform->setDefault('send_copy', 1);

        // Aceptar términos
        $mform->addElement('advcheckbox', 'accept_terms', 'Términos', 'Acepto la política de privacidad');
        $mform->addRule('accept_terms', 'Debes aceptar los términos', 'required', null, 'client');

        // Hidden field
        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);

        // --- Botones ---
        $this->add_action_buttons(true, 'Enviar Mensaje');
    }

    /**
     * Validación personalizada del formulario
     */
    public function validation($data, $files)
    {
        $errors = parent::validation($data, $files);

        if (strlen(trim($data['fullname'])) < 2) {
            $errors['fullname'] = 'El nombre debe tener al menos 2 caracteres';
        }

        if (strlen(trim($data['subject'])) < 5) {
            $errors['subject'] = 'El asunto debe tener al menos 5 caracteres';
        }

        if (strlen(trim($data['message'])) < 10) {
            $errors['message'] = 'El mensaje debe tener al menos 10 caracteres';
        }

        if (!empty($data['phone']) && !preg_match('/^[0-9+\-\s()]{6,20}$/', $data['phone'])) {
            $errors['phone'] = 'El teléfono no tiene un formato válido';
        }

        if (empty($data['accept_terms'])) {
            $errors['accept_terms'] = 'Debes aceptar la política de privacidad';
        }

        return $errors;
    }
}
