<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plugin</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php
// Verificar si se cargó Mustache
if (!isset($mustache)) {
    // Cargar la librería de Mustache
    require 'vendor/autoload.php';
    $mustache = new Mustache_Engine([
        'loader' => new Mustache_Loader_FilesystemLoader(dirname(__FILE__) . '/templates'),
    ]);
}

// Token de Moodle
$token = "143bfe295993ff7caa6e404efea7d245";
$apiUrl = "http://localhost/TIC/moodle/webservice/rest/server.php";

// Función para llamar a la API de Moodle
function callMoodleAPI($functionName, $params = []) {
    global $token, $apiUrl;
    $params['wstoken'] = $token;
    $params['wsfunction'] = $functionName;
    $params['moodlewsrestformat'] = 'json';

    $url = $apiUrl . '?' . http_build_query($params);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}

// Obtener usuarios activos
$users = callMoodleAPI("core_user_get_users", [
    "criteria[0][key]" => "",
    "criteria[0][value]" => ""
]);

$activeStudents = 0;
if (!empty($users['users'])) {
    foreach ($users['users'] as $user) {
        if (isset($user['lastaccess']) && $user['lastaccess'] > time() - 30 * 24 * 60 * 60) { // Activo en el último mes
            $activeStudents++;
        }
    }
}

// Obtener calificaciones promedio
$grades = callMoodleAPI("gradereport_user_get_grade_items", [
    "courseid" => 2 // Cambiar por el ID del curso requerido
]);
// Obtener asignaciones para un curso
$assignments = callMoodleAPI("mod_assign_get_assignments", [
    "courseids[0]" => 2 // Cambiar $courseId por el ID del curso
]);

$totalGrades = 0;
$gradesCount = 0;

if (!empty($grades['usergrades'])) {
    foreach ($grades['usergrades'] as $userGrade) {
        foreach ($userGrade['gradeitems'] as $gradeItem) {
            if (isset($gradeItem['gradeformatted']) && is_numeric($gradeItem['gradeformatted'])) {
                $totalGrades += $gradeItem['gradeformatted'];
                $gradesCount++;
            }
        }
    }
}

$averageGrades = $gradesCount > 0 ? round($totalGrades / $gradesCount, 2) : 0;


// Obtener tareas enviadas
$assignmentsSubmitted = 0;
// Verificar si hay asignaciones
if (!empty($assignments['courses'])) {
    foreach ($assignments['courses'] as $course) {
        foreach ($course['assignments'] as $assignment) {
            // Obtener envíos de cada asignación
            $submissions = callMoodleAPI("mod_assign_get_submissions", [
                "assignmentids[0]" => $assignment['id']
            ]);

            // Contar envíos con estado "submitted" o "draft"
            if (!empty($submissions['assignments'])) {
                foreach ($submissions['assignments'] as $submission) {
                    foreach ($submission['submissions'] as $userSubmission) {
                        if ($userSubmission['status'] === 'submitted' || $userSubmission['status'] === 'draft') {
                            $assignmentsSubmitted++;
                        }
                    }
                }
            }
        }
    }
}

// Preparar datos para la plantilla
$data = [
    'dashboard_title' => 'Mi Dashboard en Moodle',
    'active_students' => $activeStudents,
    'average_grades' => $averageGrades,
    'assignments_submitted' => $assignmentsSubmitted
];

// Renderizar la plantilla Mustache
if (isset($mustache)) {
    echo $mustache->render('index', $data);
} else {
    echo "Error: Mustache no está configurado.";
}
?>

<script src="dashboard.js" defer></script>
</body>
</html>
