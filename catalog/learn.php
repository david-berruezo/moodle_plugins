<?php
// This file is part of Moodle - http://moodle.org/
//
// @package    local_catalog
// @copyright  2026 Campus Virtual
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

// ==========================================================================
// AULA VIRTUAL — Player de curso tipo Udemy
// ==========================================================================
//
// URL: /local/catalog/learn.php?id=COURSEID&cmid=ACTIVITYID
//
// Requiere que el usuario esté matriculado en el curso.
// Muestra:
//   - Barra superior con título del curso y progreso
//   - Sidebar derecha con temario (secciones + actividades)
//   - Área principal con el contenido de la actividad (vídeo, página, etc.)
//   - Navegación anterior/siguiente entre actividades
// ==========================================================================

require_once(__DIR__ . '/../../config.php');

$courseid = optional_param('id', 0, PARAM_INT);
$slug     = optional_param('slug', '', PARAM_TEXT);
$cmid     = optional_param('cmid', 0,  PARAM_INT);

// Resolver slug → ID
$manager = new \local_catalog\catalog_manager();

if (empty($courseid) && !empty($slug)) {
    $courseid = $manager->get_course_by_slug($slug);
    if (!$courseid) {
        throw new moodle_exception('invalidcourseid', 'error');
    }
} elseif (empty($courseid)) {
    throw new moodle_exception('invalidcourseid', 'error');
}

// --- Control de acceso según tipo de curso ---
$price = $manager->get_price($courseid);

$isenrolled = false;
$isguest = true;

if (isloggedin() && !isguestuser()) {
    $context = context_course::instance($courseid);
    $isenrolled = is_enrolled($context);
    $isguest = false;
    // Si está logueado y matriculado, experiencia completa
} elseif (!$price['is_free']) {
    // Curso de pago + no logueado → redirige al detalle
    // redirect(new moodle_url('/local/catalog/course.php', ['id' => $courseid]));
    redirect(new moodle_url('/cursos/' . $slug));
}

// Cursos gratuitos sin login → acceso en modo lectura (sin completación)
$PAGE->set_context(context_system::instance());

$course  = get_course($courseid);
$context = context_course::instance($courseid);

// --- Obtener datos del curso ---
$player     = new \local_catalog\course_player();
$playerdata = $player->get_player_data($course, $cmid  , $isenrolled, $isguest);
//var_dump($playerdata);
//die();

if (!$playerdata) {
    throw new moodle_exception('invalidcourseid', 'error');
}

// --- Configurar la página ---
$PAGE->set_context($context);

$PAGE->set_url(new moodle_url('/local/catalog/learn.php', [
    'id'   => $courseid,
    'cmid' => $playerdata['current_cmid'],
]));

$PAGE->set_pagelayout('embedded'); // Sin navbar ni footer de Moodle
$PAGE->set_title($course->fullname . ' - ' . $playerdata['current_name']);

// CSS
$PAGE->requires->css(new moodle_url('/local/catalog/styles.css'));

// Añadir JS del catálogo
$PAGE->requires->js_call_amd('local_catalog/main', 'init');

// --- Renderizar ---
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_catalog/course_player', $playerdata);
echo $OUTPUT->footer();


/*
/var/www/html/moodle_uno/public/local/catalog/learn.php:66:
array (size=38)
  'courseid' => string '4' (length=1)
  'coursename' => string 'React JS de Cero a Experto' (length=26)
  'coursedescription' => string 'Construcción de un Divertido e Interactivo Juego de Emparejamiento de Cartas de Frutas con ReactJS, Animaciones y Hooks' (length=120)
  'courseurl' => string 'http://moodle-uno.test/local/catalog/course.php?id=4' (length=52)
  'catalogurl' => string 'http://moodle-uno.test/local/catalog/index.php' (length=46)
  'dashboardurl' => string 'http://moodle-uno.test/my/' (length=26)
  'personalizados' =>
    array (size=7)
      'course_level' => string '4' (length=1)
      'course_duration' => string '1 dia 4h' (length=8)
      'course_preview_video' => string 'https://www.youtube.com/watch?v=iHqa6ojKnHI&list=PLL0TiOXBeDai6x37_wQwWX6_qNZUM4FBm' (length=83)
      'course_long_description' => string 'Este curso es una introducción a React JS para que comiences a familiarizarte con una de las librerías más usadas de Javascript.
Te mostraré qué es React, todas sus ventajas y porque debes empezar a aprender con ello. También veremos los nuevos Hooks de React, useState, useEffect, React Router DOM, React Router DOM Dinámico, JSX, Props y mucho más.
Haremos un proyecto real en el que haremos peticiones a una API externa para recuperar datos y mostrarlos en nuestra aplicación. Con este proyecto ap'... (length=1405)
      'course_objectives' => string '
Los conocimientos básicos de React
Hacer peticiones a APIS externas a través de React
Conocer la estructura de los proyectos de React
Conocer las ventajas de React
Crear nuevas aplicaciones
React Hooks
React Router DOM V6
Subir proyectos a Github
JSX
React Developer Tools
Ternarios React
' (length=304)
      'course_requirements' => string '
Realmente ninguno. Aunque si tienes unos pequeños conocimientos de HTML, CSS y JS te puede ayudar bastante.
' (length=112)
      'course_target' => string '
Cualquier persona que quiera comenzar en el mundo de React
Cualquier desarrollador web
' (length=91)
  'current_cmid' => string '7' (length=1)
  'current_name' => string 'Introducción' (length=13)
  'current_type' => string 'page' (length=4)
  'current_content' =>
    array (size=6)
      'is_video' => boolean true
      'is_page' => boolean false
      'is_other' => boolean false
      'video_url' => string 'https://www.youtube.com/embed/iHqa6ojKnHI' (length=41)
      'html_content' => string '' (length=0)
      'activity_url' => string 'http://moodle-uno.test/mod/page/view.php?id=7' (length=45)
  'current_number' => int 1
  'total_activities' => int 4
  'is_video' => boolean true
  'is_page' => boolean false
  'is_other' => boolean false
  'video_url' => string 'https://www.youtube.com/embed/iHqa6ojKnHI' (length=41)
  'html_content' => string '' (length=0)
  'activity_url' => string 'http://moodle-uno.test/mod/page/view.php?id=7' (length=45)
  'instructores' =>
    array (size=1)
      0 =>
        array (size=11)
          'id' => string '2' (length=1)
          'fullname' => string 'David Berruezo' (length=14)
          'description' => string 'Espero poder enseñar mis conocimientos a cualquier alumno de Udemy e ir mejorando el contenido constantemente. Trayendo contenido actual e importante para cualquier persona interesada en crear web desarrollador web. Siempre me ha apasionado poder mostrar a los demás mis conocimientos y así poder ir mejorando en la medida de lo posible, todo lo relacionado con el mundo de la informática y desarrollo web.

Mi principal intención es que cualquier persona pueda crear su propia página web, ya sea desde u'... (length=565)
          'bio' => string '<p>Espero poder enseñar mis conocimientos a cualquier alumno de Udemy e ir mejorando el contenido constantemente. Trayendo contenido actual e importante para cualquier persona interesada en crear web desarrollador web. Siempre me ha apasionado poder mostrar a los demás mis conocimientos y así poder ir mejorando en la medida de lo posible, todo lo relacionado con el mundo de la informática y desarrollo web.</p>
<p></p>
<p>Mi principal intención es que cualquier persona pueda crear su propia página we'... (length=586)
          'hasbio' => boolean true
          'avatarurl' => string 'http://moodle-uno.test/pluginfile.php/5/user/icon/boost/f1?rev=28' (length=65)
          'profileurl' => string 'http://moodle-uno.test/user/profile.php?id=2' (length=44)
          'coursecount' => int 4
          'studentcount' => int 3
          'rating' => string '4.5' (length=3)
          'reviewcount' => int 0
  'tiene_instructores' => boolean true
  'instructores_nombres' => string 'David Berruezo' (length=14)
  'hasprev' => boolean false
  'hasnext' => boolean true
  'prevurl' => string '' (length=0)
  'nexturl' => string 'http://moodle-uno.test/local/catalog/learn.php?id=4&cmid=8' (length=58)
  'sections' =>
    array (size=2)
      0 =>
        array (size=8)
          'name' => string 'Introducción' (length=13)
          'num' => int 0
          'activities' =>
            array (size=2)
              ...
          'activitycount' => int 2
          'completedcount' => int 0
          'isopen' => boolean true
          'containscurrent' => boolean true
          'progress_text' => string '0/2' (length=3)
      1 =>
        array (size=8)
          'name' => string 'Primeros pasos' (length=14)
          'num' => int 1
          'activities' =>
            array (size=2)
              ...
          'activitycount' => int 2
          'completedcount' => int 0
          'isopen' => boolean true
          'containscurrent' => boolean false
          'progress_text' => string '0/2' (length=3)
  'progress' => float 0
  'progress_text' => string '0/2 (0%)' (length=8)
  'completed_count' => int 0
  'total_count' => int 2
  'cancomplete' => boolean true
  'completeurl' => string 'http://moodle-uno.test/local/catalog/complete.php?id=4&cmid=7&sesskey=Srcnh2i75D' (length=80)
  'iscompleted' => boolean false
  'isguest' => boolean false
  'isenrolled' => boolean true
  'showenroll' => boolean false
  'enrollurl' => string 'http://moodle-uno.test/local/catalog/course.php?id=4' (length=52)
*/