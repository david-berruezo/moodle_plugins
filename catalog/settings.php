<?php
// =============================================================================
// SETTINGS HOME — Añadir a /local/catalog/settings.php
//
// Este bloque añade tres pestañas al área de ajustes del plugin:
//   1. General         → ya existe en tu settings.php
//   2. Home — Secciones → categoría Data&IA, textos de features
//   3. Home — Testimonios → 3 slots de testimonios
//
// Usa $settings->add() si ya tienes un settings.php lineal,
// o crea un admin_settingpage por pestaña como se muestra aquí.
// =============================================================================

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {

    // ─── Categoría principal del plugin (si no existe ya) ────────────────────
    if (!$ADMIN->locate('local_catalog')) {
        $ADMIN->add('localplugins', new admin_category(
            'local_catalog',
            get_string('pluginname', 'local_catalog')
        ));
    }

    // ─── Pestaña: Secciones de la Home ───────────────────────────────────────
    $page_home = new admin_settingpage(
        'local_catalog_home',
        get_string('settings_home', 'local_catalog')
    );

    // — Categoría "Lo más nuevo en..." —
    $page_home->add(new admin_setting_heading(
        'local_catalog/home_sections_heading',
        get_string('settings_home_sections', 'local_catalog'),
        ''
    ));

    $page_home->add(new admin_setting_configtext(
        'local_catalog/home_datacat_id',
        get_string('home_datacat_id', 'local_catalog'),
        get_string('home_datacat_id_desc', 'local_catalog'),
        '0',           // valor por defecto = desactivado
        PARAM_INT
    ));

    // — Feature 1 —
    $page_home->add(new admin_setting_heading(
        'local_catalog/features_heading',
        get_string('settings_features', 'local_catalog'),
        get_string('settings_features_desc', 'local_catalog')
    ));

    $page_home->add(new admin_setting_configtext(
        'local_catalog/feature1_title',
        get_string('feature1_title', 'local_catalog'),
        '',
        get_string('feature1_title_default', 'local_catalog'),
        PARAM_TEXT
    ));
    $page_home->add(new admin_setting_configtextarea(
        'local_catalog/feature1_text',
        get_string('feature1_text', 'local_catalog'),
        '',
        get_string('feature1_text_default', 'local_catalog'),
        PARAM_TEXT
    ));

    // — Feature 2 —
    $page_home->add(new admin_setting_configtext(
        'local_catalog/feature2_title',
        get_string('feature2_title', 'local_catalog'),
        '',
        get_string('feature2_title_default', 'local_catalog'),
        PARAM_TEXT
    ));
    $page_home->add(new admin_setting_configtextarea(
        'local_catalog/feature2_text',
        get_string('feature2_text', 'local_catalog'),
        '',
        get_string('feature2_text_default', 'local_catalog'),
        PARAM_TEXT
    ));

    // — Feature 3 —
    $page_home->add(new admin_setting_configtext(
        'local_catalog/feature3_title',
        get_string('feature3_title', 'local_catalog'),
        '',
        get_string('feature3_title_default', 'local_catalog'),
        PARAM_TEXT
    ));
    $page_home->add(new admin_setting_configtextarea(
        'local_catalog/feature3_text',
        get_string('feature3_text', 'local_catalog'),
        '',
        get_string('feature3_text_default', 'local_catalog'),
        PARAM_TEXT
    ));

    // — Feature 4 —
    $page_home->add(new admin_setting_configtext(
        'local_catalog/feature4_title',
        get_string('feature4_title', 'local_catalog'),
        '',
        get_string('feature4_title_default', 'local_catalog'),
        PARAM_TEXT
    ));

    $page_home->add(new admin_setting_configtextarea(
        'local_catalog/feature4_text',
        get_string('feature4_text', 'local_catalog'),
        '',
        get_string('feature4_text_default', 'local_catalog'),
        PARAM_TEXT
    ));

    $ADMIN->add('local_catalog', $page_home);

    // ─── Pestaña: Testimonios ─────────────────────────────────────────────────
    $page_test = new admin_settingpage(
        'local_catalog_testimonials',
        get_string('settings_testimonials', 'local_catalog')
    );

    $page_test->add(new admin_setting_heading(
        'local_catalog/testimonials_heading',
        get_string('settings_testimonials', 'local_catalog'),
        get_string('settings_testimonials_desc', 'local_catalog')
    ));

    for ($i = 1; $i <= 3; $i++) {

        $page_test->add(new admin_setting_heading(
            "local_catalog/testimonial{$i}_heading",
            get_string('testimonial_n', 'local_catalog', $i),
            ''
        ));

        $page_test->add(new admin_setting_configtext(
            "local_catalog/testimonial{$i}_name",
            get_string('testimonial_name', 'local_catalog'),
            '',
            '',
            PARAM_TEXT
        ));

        $page_test->add(new admin_setting_configtext(
            "local_catalog/testimonial{$i}_role",
            get_string('testimonial_role', 'local_catalog'),
            '',
            '',
            PARAM_TEXT
        ));

        $page_test->add(new admin_setting_configtextarea(
            "local_catalog/testimonial{$i}_text",
            get_string('testimonial_text', 'local_catalog'),
            '',
            '',
            PARAM_TEXT
        ));
    }

    $ADMIN->add('local_catalog', $page_test);
}
