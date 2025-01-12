<?php
define('CLI_SCRIPT', true); // Esto asegura que el script pueda ejecutarse desde CLI

require_once(__DIR__ . '/api_client.php'); // Incluir funciones personalizadas
require_once(__DIR__ . '/get_student_hours.php');
require_once(__DIR__ . '/calculate_general_grade.php');
require_once(__DIR__ . '/course_attendance.php');
require_once(__DIR__ . '/course_inactivity.php');
require_once(__DIR__ . '/forum_participations.php');
require_once(__DIR__ . '/ml_predictor.php'); // Predictor

// Obtener parámetros desde la línea de comandos
$options = getopt('', ['courseid:']);
if (!isset($options['courseid'])) {
    die("Debes proporcionar un courseid como parámetro. Ejemplo: php process_background_tasks.php --courseid=2\n");
}

$courseId = (int)$options['courseid'];

// Obtener usuarios inscritos en el curso
$users = get_user_ids_from_course($courseId); // Asegúrate de que esta función esté definida

if (empty($users)) {
    echo "No se encontraron usuarios inscritos en el curso con ID $courseId.\n";
    exit;
}


require(__DIR__ . '/../../config.php'); // Configuración principal de Moodle
global $DB;

// Verificar que el curso exista
if (!$DB->record_exists('course', ['id' => $courseId])) {
    die("El curso con ID $courseId no existe.\n");
}



foreach ($users as $userId) {

    // Obtener información del usuario
    $user = $DB->get_record('user', ['id' => $userId], 'id, firstname, lastname');
    $username = $user->firstname . ' ' . $user->lastname;

    // Realizar cálculos
    $hours_studied = min(max(get_user_active_hours($userId, $courseId, $token, $apiUrl), 0), 44);
    $attendance_percentage = calculate_attendance($userId, $courseId, $token, $apiUrl);
    $inactivity_hours = max(get_user_inactive_hours($userId, $courseId, $token, $apiUrl), 0);
    $general_grade = min(max(calculate_general_grade($userId, $courseId, $token, $apiUrl), 0), 100);
    $forum_participations = max(count_forum_participations($userId, $courseId, $token, $apiUrl), 0);

    // Preparar datos del estudiante para la predicción
    $student_data = [
        'hours_studied' => $hours_studied,
        'attendance' => $attendance_percentage,
        'inactivity_hours' => $inactivity_hours,
        'previous_scores' => $general_grade,
        'tutoring_sessions' => $forum_participations,
        'physical_activity' => 1
    ];

    // Calcular predicción
    $ml_enabled = get_config('local_ml_dashboard2', 'enabled');
    $predicted_score = $ml_enabled ? predict_student_score($student_data) : null;

    // Normalizar la predicción
    $normalized_prediction = array_sum($student_data) === 0 ? 0 : min(max($predicted_score, 0), 100);
//    echo "Normalized prediction: $normalized_prediction\n";

    // Preparar datos para la tabla
    $record = [
        'userid' => $userId,
        'username' => $username, // Agregar el nombre del usuario
        'courseid' => $courseId,
        'hours_studied' => $hours_studied,
        'attendance' => $attendance_percentage,
        'inactive_time' => $inactivity_hours,
        'general_grade' => $general_grade,
        'forum_participations' => $forum_participations,
        'prediction_score' => $normalized_prediction,
        'last_updated' => time()
    ];

    // Insertar o actualizar en la tabla
    $existing_record = $DB->get_record('plugin_student_activity', ['userid' => $userId, 'courseid' => $courseId]);
    if ($existing_record) {
        $record['id'] = $existing_record->id; // Incluye el ID para la actualización
        $DB->update_record('plugin_student_activity', $record);
        echo "Actualizado registro para usuario ID $userId.\n";
    } else {
        $DB->insert_record('plugin_student_activity', $record);
        echo "Insertado nuevo registro para usuario ID $userId.\n";
    }
}

echo "Procesamiento completado para el curso ID: $courseId\n";
