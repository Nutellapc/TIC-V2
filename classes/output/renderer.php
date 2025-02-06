<?php
namespace local_ml_dashboard2\output;

defined('MOODLE_INTERNAL') || die();

use plugin_renderer_base;
use renderable;
use stdClass;

class renderer extends plugin_renderer_base {
    public function render_dashboard_alert() {
        global $PAGE, $USER;

        // Verifica si el usuario es profesor o administrador
        $showalert = has_capability('moodle/course:update', $PAGE->context);

        // Datos para la plantilla
        $data = new stdClass();
        $data->showalert = $showalert;
        $data->dashboard_url = new \moodle_url('/local/ml_dashboard2/index.php');

        return $this->render_from_template('local_ml_dashboard2/dashboard_alert', $data);
    }
}
