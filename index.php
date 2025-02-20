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

// Obtener los datos del usuario autenticado
function getAuthenticatedUser($token, $apiUrl) {

    $function = 'core_webservice_get_site_info';
    $siteInfo = callMoodleAPI($function);

    if (isset($siteInfo['userid'])) {
//        echo "Usuario autenticado - ID: " . $siteInfo['userid'] . ", Nombre: " . $siteInfo['fullname'] . ", Username: " . $siteInfo['username'];
        return [
            'id' => $siteInfo['userid'],
            'fullname' => $siteInfo['fullname'],
            'username' => $siteInfo['username'],
        ];
    }

    // Registrar error si no se obtuvieron datos del usuario
    error_log("Error: No se pudo obtener la información del usuario autenticado.");
    return null;
}

// Llamar a la función para obtener los datos del usuario autenticado
$userData = getAuthenticatedUser($token, $apiUrl);
if (!$userData) {
    die("Error: No se pudo obtener la información del usuario.");
}



// Obtener el curso seleccionado de la URL (o usar un valor predeterminado si no está seleccionado)
$selectedCourseId = isset($_GET['courseid']) ? (int)$_GET['courseid'] : (isset($courses[0]['id']) ? $courses[0]['id'] : 0);
//echo $selectedCourseId;



// Verificar si el curso seleccionado es válido
if ($selectedCourseId > 0) {
    // Usar $selectedCourseId para todas las funciones que dependen de courseid
    $grades = callMoodleAPI("gradereport_user_get_grade_items", [
        "courseid" => $selectedCourseId
    ]);
    $assignments = callMoodleAPI("mod_assign_get_assignments", [
        "courseids[0]" => $selectedCourseId
    ]);
    // Otras funciones que dependen del courseid...
} else {
//    echo "Error: No se seleccionó un curso válido.";
}



// Verificar si se cargó Mustache

if (!isset($mustache)) {
    require 'vendor/autoload.php';
    $mustache = new Mustache_Engine([
        'loader' => new Mustache_Loader_FilesystemLoader(dirname(__FILE__) . '/templates'),
        'helpers' => [
            'equals' => function ($value, $expected) {
                return $value == $expected ? 'selected' : '';
            }
        ]
    ]);
}


// Obtener cursos para el usuario autenticado
$userId = $userData['id'];
$courses = get_user_courses($userId, $token, $apiUrl);


// Obtener la lista de cursos disponibles para el usuario
function get_user_courses($userId, $token, $apiUrl) {
    $function = 'core_enrol_get_users_courses';
    $params = ['userid' => $userId];

    $courses = callMoodleAPI($function, $params);

    // Verificar si se encontraron cursos y procesarlos
    if (!empty($courses)) {
        return array_map(function ($course) {
            return ['id' => $course['id'], 'fullname' => $course['fullname']];
        }, $courses);
    }

    return [];
}

// Obtener usuarios activos
$users = callMoodleAPI("core_user_get_users", [
    "criteria[0][key]" => "",
    "criteria[0][value]" => ""
]);

// Obtener los usuarios inscritos en el curso seleccionado
$courseUsers = callMoodleAPI("core_enrol_get_enrolled_users", [
    "courseid" => $selectedCourseId
]);


$activeStudents = 0;

if (!empty($courseUsers)) {
    foreach ($courseUsers as $user) {
        if (isset($user['lastaccess']) && $user['lastaccess'] > time() - 30 * 24 * 60 * 60) { // Activo en el último mes
            $activeStudents++;
        }
    }
}

// Obtener la lista de usuarios inscritos en el curso seleccionado a través de la API de Moodle
$enrolledUsers = callMoodleAPI("core_enrol_get_enrolled_users", [
    "courseid" => $selectedCourseId
]);

// Contar el número de estudiantes inscritos
$enrolledStudentsCount = !empty($enrolledUsers) ? count($enrolledUsers) : 0;

// Obtener calificaciones promedio
$grades = callMoodleAPI("gradereport_user_get_grade_items", [
    "courseid" => $selectedCourseId
]);
// Obtener asignaciones para un curso
$assignments = callMoodleAPI("mod_assign_get_assignments", [
    "courseids[0]" => $selectedCourseId
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
$courseId = $selectedCourseId;
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

// Obtener el curso seleccionado de la URL (o usar un valor predeterminado si no está seleccionado)
$selectedCourseId = isset($_GET['courseid']) ? (int)$_GET['courseid'] : (isset($courses[0]['id']) ? $courses[0]['id'] : 0);

require_once(__DIR__ . '/../../config.php'); // Incluye la configuración de Moodle
require_login(); // Asegúrate de que el usuario esté autenticado


// Asegúrate de que Moodle está cargado y que la variable global $USER está disponible
global $USER;

// Obtener los datos del usuario autenticado
$userData = [
    'id' => $USER->id,  // ID del usuario autenticado
    'fullname' => fullname($USER),  // Nombre completo del usuario
    'username' => $USER->username,  // Nombre de usuario
];

// Mostrar la información del usuario autenticado
//echo "Usuario autenticado - ID: " . $userData['id'] . ", Nombre: " . $userData['fullname'] . ", Username: " . $userData['username'];


// Verificar si el usuario está inscrito en cursos
// Obtener los cursos a los que está inscrito el usuario
$coursesUser = enrol_get_all_users_courses($USER->id, true); // El segundo parámetro se usa para incluir cursos activos/inactivos

// Crear un array vacío para almacenar los cursos
$userCoursesArray = [];

// Verificar si el usuario está inscrito en cursos
if (!empty($coursesUser)) {
    // Recorremos los cursos y los almacenamos en el array
    foreach ($coursesUser as $course) {
        // Almacenar solo el nombre completo del curso
        $userCoursesArray[] = [
            'id' => $course->id,             // ID del curso
            'fullname' => $course->fullname  // Nombre completo del curso
        ];
    }

    // Opcional: Mostrar los cursos almacenados en el array
//    echo "<ul>";
//    foreach ($userCoursesArray as $course) {
//        echo "<li>" . $course['fullname'] . "</li>";
//    }
//    echo "</ul>";
}

// Filtrar el array $courses para que solo queden los cursos que están en el array $userCoursesArray
$courses = array_filter($courses, function($course) use ($userCoursesArray) {
    // Compara el ID de cada curso con los IDs del usuario, y devuelve el curso con 'id' y 'fullname'
    foreach ($userCoursesArray as $userCourse) {
        if ($course['id'] == $userCourse['id']) {
            return true;
        }
    }
    return false;
});

// Reindexar el array filtrado para evitar índices no consecutivos
$courses = array_values($courses);

//// Mostrar los cursos filtrados
//if (!empty($courses)) {
//    echo "<ul>";
//    foreach ($courses as $course) {
//        echo "<li>" . $course['fullname'] . " (ID: " . $course['id'] . ")</li>";  // Mostrar nombre completo y ID
//    }
//    echo "</ul>";
//} else {
////    echo "No hay cursos disponibles para este usuario.";
//}

// Establece el contexto y la URL de la página
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/ml_dashboard2/index.php'));

// Obtener el nombre del curso seleccionado
$selectedCourseName = '';
foreach ($courses as $course) {
    if ($course['id'] == $selectedCourseId) {
        $selectedCourseName = $course['fullname'];
        break;
    }
}


// Generar URLs para las imágenes
$logoCamoodle1 = $OUTPUT->image_url('camoodle_logo1', 'local_ml_dashboard2');
$logoUcsg = $OUTPUT->image_url('ucsg_logo', 'local_ml_dashboard2');
$logoUcsg1 = $OUTPUT->image_url('logoUcsg', 'local_ml_dashboard2');
$camoodles = $OUTPUT->image_url('camoodles', 'local_ml_dashboard2');




// Preparar datos para la plantilla
$data = [
    'logo_camoodle' => $logoCamoodle1,
    'logo_ucsg' => $logoUcsg,
    'logo_ucsg1' => $logoUcsg1,
    'camoodles' => $camoodles,
    'dashboard_title' => 'Mi Dashboard en Moodle',
    'active_students' => $activeStudents,
    'average_grades' => $averageGrades,
    'assignments_submitted' => $assignmentsSubmitted,
    'average_connected_time' => $averageConnectedTime,
    'student_grades' => $studentGradesData,
    'activity_views' => $activityViewsData,
    'courses' => $courses,
    'selected_course' => $selectedCourseId,
    'selected_course_name' => $selectedCourseName,
    'username' => $userData['fullname'], // Nombre completo del usuario
    'user_id' => $userData['id'], // ID del usuario
    'enrolled_students_count' => $enrolledStudentsCount,
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

    // Manejar el cambio de curso desde el selector
    document.getElementById('courseSelector').addEventListener('change', function () {
        const selectedCourseId = this.value; // Obtener el valor seleccionado
        if (selectedCourseId) {
            window.location.href = `index.php?courseid=${selectedCourseId}`; // Redirigir con el ID del curso
        }
    });

</script>
<script src="dashboard.js" defer></script>
<script>
    const dashboardData = <?php echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT); ?>;

    // Manejar el cambio de curso desde el selector
    document.getElementById('courseSelector').addEventListener('change', function () {
        const selectedCourseId = this.value; // Obtener el valor seleccionado
        if (selectedCourseId) {
            window.location.href = `index.php?courseid=${selectedCourseId}`; // Redirigir con el ID del curso
        }
    });

</script>
<script src="dashboard.js" defer></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const menuButton = document.getElementById('menuButton');
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');

        if (menuButton && sidebar && mainContent) {
            menuButton.addEventListener('click', (event) => {
                event.stopPropagation();

                // Alternar el estado del sidebar
                const isHidden = sidebar.classList.contains('-translate-x-full');
                if (isHidden) {
                    sidebar.classList.remove('-translate-x-full');
                    mainContent.classList.add('ml-64');
                } else {
                    sidebar.classList.add('-translate-x-full');
                    mainContent.classList.remove('ml-64');
                }
            });

            // Cerrar el sidebar al hacer clic fuera de él
            document.addEventListener('click', (event) => {
                if (!sidebar.contains(event.target) && !menuButton.contains(event.target)) {
                    sidebar.classList.add('-translate-x-full');
                    mainContent.classList.remove('ml-64');
                }
            });

            // Cerrar el sidebar al hacer clic en un enlace dentro de él
            const menuLinks = sidebar.querySelectorAll('a');
            menuLinks.forEach(link => {
                link.addEventListener('click', () => {
                    sidebar.classList.add('-translate-x-full');
                    mainContent.classList.remove('ml-64');
                });
            });

            // Cerrar el sidebar al presionar la tecla "Esc"
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    sidebar.classList.add('-translate-x-full');
                    mainContent.classList.remove('ml-64');
                }
            });
        } else {
            console.error('El botón, el sidebar o el contenido principal no se encontraron.');
        }
    });




</script>
</body>
</html>
