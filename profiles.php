<?php

$token = "143bfe295993ff7caa6e404efea7d245";
$apiUrl = "http://localhost/TIC/moodle/webservice/rest/server.php";

// Configuración para registrar errores en un archivo
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt'); // Archivo de log en la misma carpeta
error_reporting(E_ALL);

require_once(__DIR__ . '/forum_participations.php');
require_once(__DIR__ . '/calculate_general_grade.php');
require_once(__DIR__ . '/get_student_hours.php');
require_once(__DIR__ . '/course_attendance.php');
require_once(__DIR__ . '/course_inactivity.php');
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/vendor/autoload.php');
require_once(__DIR__ . '/ml_predictor.php'); // el predictor

// Configuración para registrar errores en un archivo
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt'); // Archivo de log
error_reporting(E_ALL);

// Verificar si el modelo de ML está habilitado
$ml_enabled = get_config('local_ml_dashboard2', 'enabled');


// Obtener horas estudiadas dinámicamente usando la función get_useractive_hour()
try {
    $studentId = 2; // ID del estudiante (ajusta según el contexto real)
    $courseId = 2;  // ID del curso

    // Llamar a las funciones para calcular datos
    $hours_studied = get_user_active_hours($studentId, $courseId, $token, $apiUrl);
    $hours_studied = min(max($hours_studied, 0), 44); // Ajustar al rango válido (1-44)
    $attendance_percentage = calculate_attendance($studentId, $courseId, $token, $apiUrl);
    $inactivity_hours = get_user_inactive_hours($studentId, $courseId, $token, $apiUrl);
    $general_grade = calculate_general_grade($studentId, $courseId, $token, $apiUrl);
    $forum_participations = count_forum_participations($studentId, $courseId, $token, $apiUrl);



} catch (Exception $e) {
    error_log("Error al obtener las horas activas: " . $e->getMessage());
    $hours_studied = 0; // Valor predeterminado en caso de error
    $attendance_percentage = 0; // Valor predeterminado en caso de error
    $inactivity_hours = 0; // Valor predeterminado en caso de error
    $general_grade = 0; // Valor predeterminado en caso de error
    $forum_participations = 0; // Valor predeterminado en caso de error
}

// Configurar datos del estudiante
$student_data = [
    'hours_studied' => $hours_studied,               // Ajustar al rango válido (1-44)
    'attendance' => $attendance_percentage,          // Attendance 60-100 (puedes calcularlo dinámicamente después)
    'inactivity_hours' => $inactivity_hours,         // inactivity_hours 4-16
    'previous_scores' => $general_grade,             // Previous_Scores 50-100
    'tutoring_sessions' => $forum_participations,    // Tutoring_Sessions 0-8
    'physical_activity' => 1                        // Physical_Activity
];


$predicted_score = null;
if ($ml_enabled) {
    // Realizar predicción
    $predicted_score = predict_student_score($student_data);
    error_log("Predicción obtenida: " . print_r($predicted_score, true)); // Log
    //var_dump($predicted_score); die(); // Descomenta para prueba en la página
}

// Normalizar la predicción al rango esperado (0-100), pero verificar si todos los datos son cero
if (array_sum($student_data) === 0) {
    $normalized_prediction = 0;  // Valor predeterminado si todos los valores son cero
} else {
    $normalized_prediction = min(max($predicted_score, 0), 100);
}

// Preparar datos para la plantilla Mustache
$m = new Mustache_Engine(array(
    'loader' => new Mustache_Loader_FilesystemLoader(__DIR__ . '/templates'),
));

echo $m->render('profiles', [
    'ml_enabled' => $ml_enabled,
    'predicted_score' => round($normalized_prediction/10, 2) ?? 'No disponible', // Redondear el valor de predicción a 2 decimales
    'hours_studied' => round($student_data['hours_studied'] , 2), // Cambiar a horas estudiadas
    'attendance' => $student_data['attendance'], // Asistencia
    'inactivity_hours' => round($student_data['inactivity_hours'],2), // Horas de inactividad
    'previous_scores' => $student_data['previous_scores']/10, // Puntajes previos
    'tutoring_sessions' => $student_data['tutoring_sessions'], // Sesiones de tutoría
    'physical_activity' => $student_data['physical_activity'] // Actividad física
]);


