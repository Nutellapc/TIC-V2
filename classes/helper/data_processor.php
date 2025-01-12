<?php

namespace local_ml_dashboard2\helper;

defined('MOODLE_INTERNAL') || die();

class data_processor {
    /**
     * Procesa los datos de los estudiantes y los guarda en la base de datos.
     */
    public static function process_student_activity() {
        global $DB;

        $users = $DB->get_records('user', ['deleted' => 0], '', 'id, firstname, lastname');
        $courses = $DB->get_records('course', null, '', 'id, fullname');

        foreach ($users as $user) {
            foreach ($courses as $course) {
                $data = [
                    'userid' => $user->id,
                    'courseid' => $course->id,
                    'hours_studied' => rand(0, 40),
                    'attendance' => rand(50, 100),
                    'inactive_time' => rand(0, 20),
                    'general_grade' => rand(0, 100),
                    'forum_participations' => rand(0, 10),
                    'prediction_score' => rand(0, 100), // Campo adicional.
                    'last_updated' => time(),
                ];

                // Inserta o actualiza en la tabla personalizada
                $existing = $DB->get_record('plugin_student_activity', [
                    'userid' => $data['userid'],
                    'courseid' => $data['courseid']
                ]);

                if ($existing) {
                    $data['id'] = $existing->id;
                    $DB->update_record('plugin_student_activity', $data);
                } else {
                    $DB->insert_record('plugin_student_activity', $data);
                }
            }
        }
    }
}
