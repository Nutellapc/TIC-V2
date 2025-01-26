<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) { // Solo visible para administradores
    // Crea una página de ajustes para el plugin
    $settings = new admin_settingpage('local_ml_dashboard2', get_string('pluginconfig', 'local_ml_dashboard2'));

    // Agrega una opción para habilitar o deshabilitar el plugin
    $settings->add(new admin_setting_configcheckbox('local_ml_dashboard2/enabled',
        get_string('enabled', 'local_ml_dashboard2'),
        get_string('enableddesc', 'local_ml_dashboard2'), 0));

    // Agrega una opción para habilitar/deshabilitar el modelo de Machine Learning
    $settings->add(new admin_setting_configcheckbox('local_ml_dashboard2/enable_ml',
        get_string('enableml', 'local_ml_dashboard2'),
        get_string('enablemldesc', 'local_ml_dashboard2'), 0));

    // Agrega un campo para ingresar la clave de la API de OpenAI
    $settings->add(new admin_setting_configtext(
        'local_ml_dashboard2/openaiapikey',
        get_string('openkey', 'local_ml_dashboard2'),
        get_string('openkeydesc', 'local_ml_dashboard2'),
        '',
        PARAM_TEXT
    ));

    // Añade la página de ajustes al menú de plugins locales
    $ADMIN->add('localplugins', $settings);

    // Verificar si el dashboard está habilitado antes de mostrar el enlace
    if (get_config('local_ml_dashboard2', 'enabled')) {
        // Agregar un enlace al dashboard solo si está habilitado
        $ADMIN->add('localplugins', new admin_externalpage(
            'local_ml_dashboard2_page', // Identificador único
            get_string('viewdashboard', 'local_ml_dashboard2'), // Nombre visible en el menú
            new moodle_url('/local/ml_dashboard2/index.php') // URL del dashboard
        ));
    }
}
