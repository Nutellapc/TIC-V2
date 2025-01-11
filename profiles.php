<?php

$token = "143bfe295993ff7caa6e404efea7d245";
$apiUrl = "http://localhost/TIC/moodle/webservice/rest/server.php";

require_once(__DIR__ . '/api_client.php'); // Llamadas a la API externa
require_once(__DIR__ . '/forum_participations.php');
require_once(__DIR__ . '/calculate_general_grade.php');
require_once(__DIR__ . '/get_student_hours.php');
require_once(__DIR__ . '/course_attendance.php');
require_once(__DIR__ . '/course_inactivity.php');
require_once(__DIR__ . '/vendor/autoload.php');
require_once(__DIR__ . '/ml_predictor.php'); // El predictor
require_once(__DIR__ . '/average_calculations.php');// promedios del curso


// Obtener el usuario autenticado utilizando la API
$userInfo = get_authenticated_user();
if (!$userInfo) {
//    echo "<h3>Error: No se pudo obtener información del usuario autenticado.</h3>";
    error_log("Error al obtener información del usuario autenticado.");
    exit;
}

// ID y nombre del usuario autenticado
$studentId = $userInfo['id'];
$studentName = $userInfo['fullname'];
//echo "<h3>ID del usuario autenticado: $studentId</h3>";
//echo "<h3>Nombre del usuario autenticado: $studentName</h3>";

// Obtener la lista de cursos del usuario desde `api_client.php`
$courses = get_user_courses($studentId);

// Seleccionar curso actual
$selectedCourseId = isset($_GET['courseid']) ? (int)$_GET['courseid'] : ($courses[0]['id'] ?? 0);
//echo "<h3>Curso seleccionado: $selectedCourseId</h3>";


// Llamar a la función para obtener los IDs de los estudiantes inscritos en el curso seleccionado
$studentIds = get_user_ids_from_course($selectedCourseId);

// Mostrar los IDs obtenidos en pantalla si existen
//if (!empty($studentIds)) {
//    echo "IDs de estudiantes inscritos en el curso $selectedCourseId: " . implode(', ', $studentIds);
//} else {
//    echo "No se encontraron IDs de estudiantes inscritos en el curso $selectedCourseId.";
//}*******************



require_once(__DIR__ . '/../../config.php');

require_login(); // Asegura que el usuario esté autenticado


if (empty($courses)) {
//    echo "<h3>No se encontraron cursos para el usuario con ID: $studentId.</h3>";
    error_log("No se encontraron cursos para el usuario con ID: $studentId.");
} else {
//    echo "<h3>Cursos recuperados para el usuario con ID: $studentId:</h3><pre>";
//    print_r($courses);
//    echo "</pre>";
}


if (!$selectedCourseId) {
//    echo "<h3>Error: No se seleccionó un curso válido.</h3>";
    error_log("Error: No se seleccionó un curso válido.");
}





// Obtener datos relacionados con el curso
try {
    $courseId = $selectedCourseId;

    // Llamar a las funciones para calcular datos
    $hours_studied = get_user_active_hours($studentId, $courseId, $token, $apiUrl);

//    echo "<h3>Horas estudiadas: $hours_studied</h3>";
    $hours_studied = min(max($hours_studied, 0), 44); // Ajustar al rango válido (1-44)

    $attendance_percentage = calculate_attendance($studentId, $courseId, $token, $apiUrl);
//    echo "<h3>Porcentaje de asistencia: $attendance_percentage</h3>";

    $inactivity_hours = get_user_inactive_hours($studentId, $courseId, $token, $apiUrl);
//    echo "<h3>Horas de inactividad: $inactivity_hours</h3>";

    $general_grade = calculate_general_grade($studentId, $courseId, $token, $apiUrl);
//    echo "<h3>Calificación general: $general_grade</h3>";

    $forum_participations = count_forum_participations($studentId, $courseId, $token, $apiUrl);
//    echo "<h3>Participaciones en foros: $forum_participations</h3>";
} catch (Exception $e) {
    error_log("Error al obtener datos del curso: " . $e->getMessage());
//    echo "<h3>Error al obtener datos del curso: " . $e->getMessage() . "</h3>";

    $hours_studied = 0;
    $attendance_percentage = 0;
    $inactivity_hours = 0;
    $general_grade = 0;
    $forum_participations = 0;
}

// Configurar datos del estudiante
$student_data = [
    'hours_studied' => $hours_studied,
    'attendance' => $attendance_percentage,
    'inactivity_hours' => $inactivity_hours,
    'previous_scores' => $general_grade,
    'tutoring_sessions' => $forum_participations,
    'physical_activity' => 1
];

// Predicción basada en ML
$ml_enabled = get_config('local_ml_dashboard2', 'enabled');
$predicted_score = $ml_enabled ? predict_student_score($student_data) : null;

// Normalizar la predicción
$normalized_prediction = array_sum($student_data) === 0 ? 0 : min(max($predicted_score, 0), 100);
//echo "<h3>Predicción normalizada: $normalized_prediction</h3>";

// Preparar datos para la plantilla Mustache
$m = new Mustache_Engine(array(
    'loader' => new Mustache_Loader_FilesystemLoader(__DIR__ . '/templates'),
));

echo $m->render('profiles', [
    'ml_enabled' => $ml_enabled,
    'predicted_score' => round($normalized_prediction / 10, 2) ?? 'No disponible',
    'hours_studied' => round($student_data['hours_studied'], 2),
    'attendance' => $student_data['attendance'],
    'inactivity_hours' => round($student_data['inactivity_hours'], 2),
    'previous_scores' => $student_data['previous_scores'] / 10,
    'tutoring_sessions' => $student_data['tutoring_sessions'],
    'physical_activity' => $student_data['physical_activity'],
    'courses' => $courses,
    'selected_course' => $selectedCourseId,
]);

?>

<script>
    document.getElementById('courseSelector').addEventListener('change', function () {
        const selectedCourseId = this.value;
        if (selectedCourseId) {
            window.location.href = `profiles.php?courseid=${selectedCourseId}`;
        }
    });
</script>
