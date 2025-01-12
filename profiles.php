<?php

$token = "143bfe295993ff7caa6e404efea7d245";
$apiUrl = "http://localhost/TIC/moodle/webservice/rest/server.php";

require_once(__DIR__ . '/api_client.php'); // Llamadas a la API externa
require_once(__DIR__ . '/vendor/autoload.php'); // Mustache para la plantilla

// Obtener usuario autenticado
$userInfo = get_authenticated_user();
if (!$userInfo) {
    error_log("Error al obtener información del usuario autenticado.");
    exit;
}

$studentId = $userInfo['id'];
$studentName = $userInfo['fullname'];

// Obtener cursos del usuario
$courses = get_user_courses($studentId);
$selectedCourseId = isset($_GET['courseid']) ? (int)$_GET['courseid'] : ($courses[0]['id'] ?? 0);

require_once(__DIR__ . '/../../config.php');
require_login();

$processedDataArray = []; // Inicializa un array vacío para los datos procesados

if ($selectedCourseId) {
    // Obtener los datos procesados desde la tabla
    $processedData = $DB->get_records('plugin_student_activity', ['courseid' => $selectedCourseId]);

    // Convertir los datos obtenidos en un array compatible con Mustache
    if (!empty($processedData)) {
        foreach ($processedData as $data) {

            // Obtener el nombre del estudiante desde la tabla 'user'
            $user = $DB->get_record('user', ['id' => $data->userid], 'firstname, lastname');
            $username = $user->firstname . ' ' . $user->lastname;

            $processedDataArray[] = [
                'userid' => $data->userid,
                'username' => $username,
                'hours_studied' => $data->hours_studied,
                'attendance' => $data->attendance,
                'inactive_time' => $data->inactive_time,
                'general_grade' => $data->general_grade,
                'forum_participations' => $data->forum_participations,
                'prediction_score' => $data->prediction_score,
                'last_updated' => date('Y-m-d H:i:s', $data->last_updated),
            ];
        }

// Determinar la fecha más reciente de 'last_updated'
        $lastUpdatedTimestamps = array_map(function ($data) {
            return $data->last_updated;
        }, $processedData);
        $lastUpdated = max($lastUpdatedTimestamps);
        $lastUpdated = date('Y-m-d H:i:s', $lastUpdated); // Convertir a formato legible

    } else {
        error_log("No hay datos procesados para el curso seleccionado con ID: $selectedCourseId.");
    }
}

// Conectar con Mustache para mostrar los datos
$m = new Mustache_Engine(array(
    'loader' => new Mustache_Loader_FilesystemLoader(__DIR__ . '/templates'),
));

// Renderizar la plantilla con datos
echo $m->render('profiles', [
    'courses' => $courses,
    'selected_course' => $selectedCourseId,
    'processed_data' => $processedDataArray,
    'last_updated' => $lastUpdated ?? 'No disponible',
]);

?>

<!-- Script para procesar datos del curso -->
<script>
    function processCourseData(courseId) {
        if (courseId) {
            fetch(`process_course_data.php?courseid=${courseId}`)
                .then(response => response.text())
                .then(data => {
                    alert('Procesamiento completado: ' + data);
                })
                .catch(error => {
                    console.error('Error en el procesamiento:', error);
                    alert('Ocurrió un error durante el procesamiento.');
                });
        }
    }

    document.getElementById('courseSelector').addEventListener('change', function () {
        const selectedCourseId = this.value;
        if (selectedCourseId) {
            // Cambiar el curso y lanzar el procesamiento
            window.location.href = `profiles.php?courseid=${selectedCourseId}`;
        }
    });
</script>
