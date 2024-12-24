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

// Obtener tiempo conectado
$totalConnectedTime = 0;
$activeUsersCount = 0;

if (!empty($users['users'])) {
    foreach ($users['users'] as $user) {
        if (isset($user['lastaccess']) && $user['lastaccess'] > time() - 30 * 24 * 60 * 60) { // Activo en los últimos 30 días
            $activeUsersCount++;
            $totalConnectedTime += (time() - $user['lastaccess']); // Diferencia en segundos desde el último acceso
        }
    }
}

// Convertir el tiempo total conectado a horas y calcular el promedio
$averageConnectedTime = $activeUsersCount > 0 ? round($totalConnectedTime / $activeUsersCount / 3600, 2) : 0;

//*****
// Obtener calificaciones por estudiante
$studentGradesData = [];
$users = callMoodleAPI("core_user_get_users", [
    "criteria[0][key]" => "",
    "criteria[0][value]" => ""
]);

if (!empty($grades['usergrades'])) {
    foreach ($grades['usergrades'] as $userGrade) {
        $studentName = '';
        foreach ($users['users'] as $user) {
            if ($user['id'] === $userGrade['userid']) {
                $studentName = $user['fullname']; // Usar el nombre completo
                break;
            }
        }
        $totalGrade = 0;
        $itemsCount = 0;

        foreach ($userGrade['gradeitems'] as $gradeItem) {
            if (isset($gradeItem['gradeformatted']) && is_numeric($gradeItem['gradeformatted'])) {
                $totalGrade += $gradeItem['gradeformatted'];
                $itemsCount++;
            }
        }

        $averageGrade = $itemsCount > 0 ? round($totalGrade / $itemsCount, 2) : 0;
        $studentGradesData[] = ['student' => $studentName, 'grade' => $averageGrade];
    }
}



// Obtener vistas de actividades del curso
$activityViewsData = [];
$courseId = 2;
$sections = callMoodleAPI("core_course_get_contents", ["courseid" => $courseId]);

if (!empty($sections)) {
    foreach ($sections as $section) {
        if (!empty($section['modules'])) {
            foreach ($section['modules'] as $module) {
                $activityName = $module['name'] ?? 'Desconocido';
                $modName = $module['modname'] ?? 'Desconocido';

                // Sumar las vistas de cada módulo (si está disponible)
                if (!empty($module['modname'])) {
                    if (!isset($activityViewsData[$modName])) {
                        $activityViewsData[$modName] = 1;
                    } else {
                        $activityViewsData[$modName]++;
                    }
                }
            }
        }
    }
}

// Convertir los datos procesados a un formato adecuado para el gráfico
$activityViewsData = array_map(
    fn($modName, $views) => ['name' => $modName, 'views' => $views],
    array_keys($activityViewsData),
    array_values($activityViewsData)
);

// Validar que haya datos
if (empty($activityViewsData)) {
    $activityViewsData = [['name' => 'Sin datos', 'views' => 0]];
}


// Preparar datos para la plantilla
$data = [
    'dashboard_title' => 'Mi Dashboard en Moodle',
    'active_students' => $activeStudents,
    'average_grades' => $averageGrades,
    'assignments_submitted' => $assignmentsSubmitted,
    'average_connected_time' => $averageConnectedTime,
    'student_grades' => $studentGradesData,
    'activity_views' => $activityViewsData
];

// Renderizar la plantilla Mustache
if (isset($mustache)) {
    echo $mustache->render('index', $data);
} else {
    echo "Error: Mustache no está configurado.";
}
?>

<script>
    const dashboardData = <?php echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT); ?>;
</script>
<script src="dashboard.js" defer></script>
</body>
</html>
