<?php
// This file is part of Moodle - http://moodle.org/
//
// @package    local_nacex_tracking
// @copyright  2024 Nacex Formación
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

$string['pluginname'] = 'Nacex - Seguimiento de formación';
$string['manage_title'] = 'Gestión de cursos obligatorios';
$string['assign_course'] = 'Asignar curso obligatorio';
$string['department'] = 'Departamento';
$string['duedate'] = 'Fecha límite';
$string['assign'] = 'Asignar';
$string['tracking_summary'] = 'Resumen por departamento';
$string['all_departments'] = 'Todos los departamentos';
$string['mandatory_courses'] = 'Cursos obligatorios asignados';
$string['completed'] = 'Completado';
$string['in_progress'] = 'En progreso';
$string['pending'] = 'Pendiente';
$string['overdue'] = 'Vencido';
$string['my_progress'] = 'Mi progreso de formación';
$string['no_mandatory_courses'] = 'No tienes cursos obligatorios asignados.';
$string['completiondate'] = 'Fecha de finalización';
$string['go_to_course'] = 'Ir al curso';
$string['course_assigned'] = 'Curso obligatorio asignado correctamente.';
$string['course_deleted'] = 'Asignación eliminada correctamente.';
$string['confirm_delete'] = '¿Estás seguro de eliminar esta asignación?';
$string['status_completed'] = 'Completado';
$string['status_in_progress'] = 'En progreso';
$string['status_pending'] = 'Pendiente';
$string['status_overdue'] = 'Vencido';
$string['settings_departments'] = 'Departamentos';
$string['settings_departments_desc'] = 'Lista de departamentos separados por comas.';
$string['settings_notifications'] = 'Habilitar notificaciones';
$string['settings_notifications_desc'] = 'Enviar recordatorios por email a los empleados con cursos pendientes.';
$string['settings_reminder_days'] = 'Días de antelación para recordatorio';
$string['settings_reminder_days_desc'] = 'Número de días antes de la fecha límite para enviar el recordatorio.';
$string['settings_overdue_days'] = 'Días para marcar como vencido';
$string['settings_overdue_days_desc'] = 'Número de días después de la fecha límite para marcar como vencido (0 = inmediato).';
$string['reminder_subject'] = 'Recordatorio: Curso obligatorio "{$a}"';
$string['reminder_body'] = 'Hola {$a->firstname},

Te recordamos que tienes pendiente el curso obligatorio "{$a->coursename}".
Fecha límite: {$a->duedate}

Por favor, accede a la plataforma y completa el curso.

Saludos,
Departamento de Formación - Nacex';
$string['task_sync_completion'] = 'Sincronizar estado de finalización de cursos';
$string['task_send_reminders'] = 'Enviar recordatorios de cursos pendientes';
$string['messageprovider:reminder'] = 'Recordatorio de curso obligatorio';
