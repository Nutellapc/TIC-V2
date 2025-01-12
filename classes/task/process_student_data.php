<?php

namespace local_ml_dashboard2\task;

use local_ml_dashboard2\helper\data_processor;

defined('MOODLE_INTERNAL') || die();

/**
 * Tarea programada para procesar datos de estudiantes.
 */
class process_student_data extends \core\task\scheduled_task {

    /**
     * Devuelve el nombre de la tarea (visible en el administrador de tareas).
     */
    public function get_name() {
        return get_string('processtask', 'local_ml_dashboard2');
    }

    /**
     * Código que se ejecuta cuando se corre la tarea.
     */
    public function execute() {
        mtrace("Iniciando procesamiento de datos...");
        data_processor::process_student_activity();
        mtrace("Procesamiento de datos completado.");
    }
}
