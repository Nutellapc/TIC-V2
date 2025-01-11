<?php
require_once(__DIR__ . '/../../config.php'); // Ruta al archivo de configuración de Moodle para inicializar el contexto.
require_login(); // Asegurarse de que el usuario esté autenticado.

// Verificar permisos (opcional, puedes ajustar según los requisitos).
if (!is_siteadmin()) {
    die('No tienes permisos para ejecutar este script.');
}

// Insertar datos de prueba en la tabla 'plugin_student_activity'.
$data = new stdClass();
$data->userid = 2; // ID de usuario (ajusta este valor según tu base de datos).
$data->courseid = 3; // ID del curso (ajusta este valor según tu base de datos).
$data->hours_studied = 5.5; // Horas estudiadas.
$data->attendance = 90.0; // Porcentaje de asistencia.
$data->inactive_time = 2.0; // Horas de inactividad.
$data->general_grade = 85.5; // Calificación general.
$data->forum_participations = 10; // Participaciones en foros.
$data->prediction_score = 75.3; // Puntaje de predicción.
$data->last_updated = time(); // Marca de tiempo actual.

try {
    // Insertar los datos en la tabla.
    $inserted_id = $DB->insert_record('plugin_student_activity', $data);
    echo "Datos insertados correctamente. ID del registro: $inserted_id<br>";
} catch (dml_exception $e) {
    echo "Error al insertar datos: " . $e->getMessage();
    die;
}

// Recuperar los datos recién insertados para verificar.
try {
    $record = $DB->get_record('plugin_student_activity', ['id' => $inserted_id], '*', MUST_EXIST);
    echo "<pre>";
    print_r($record);
    echo "</pre>";
} catch (dml_exception $e) {
    echo "Error al recuperar datos: " . $e->getMessage();
    die;
}
