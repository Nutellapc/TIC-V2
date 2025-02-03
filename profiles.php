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

// Obtener el nombre del curso seleccionado
$selectedCourseName = "Curso no seleccionado"; // Valor predeterminado

foreach ($courses as $course) {
    if ($course['id'] == $selectedCourseId) {
        $selectedCourseName = $course['fullname']; // Nombre del curso seleccionado
        break;
    }
}


require_once(__DIR__ . '/../../config.php');
require_login();

$processedDataArray = []; // Inicializa un array vacío para los datos procesados






if ($selectedCourseId) {
    // Obtener los datos procesados desde la tabla
    $processedData = $DB->get_records('plugin_student_activity', ['courseid' => $selectedCourseId]);

    $PAGE->set_context(context_system::instance());
    $PAGE->set_url(new moodle_url('/local/ml_dashboard2/profiles.php'));
    $logoUrl = $OUTPUT->image_url('logoUcsg', 'local_ml_dashboard2');


// Definir imágenes por categorías
    $redImages = [
        $OUTPUT->image_url('red1', 'local_ml_dashboard2'),
        $OUTPUT->image_url('red2', 'local_ml_dashboard2'),
        $OUTPUT->image_url('red3', 'local_ml_dashboard2'),
        $OUTPUT->image_url('red4', 'local_ml_dashboard2'),
        $OUTPUT->image_url('red5', 'local_ml_dashboard2'),
        $OUTPUT->image_url('red6', 'local_ml_dashboard2'),
    ];

    $yellowImages = [
        $OUTPUT->image_url('yellow1', 'local_ml_dashboard2'),
        $OUTPUT->image_url('yellow2', 'local_ml_dashboard2'),
        $OUTPUT->image_url('yellow3', 'local_ml_dashboard2'),
        $OUTPUT->image_url('yellow4', 'local_ml_dashboard2'),
        $OUTPUT->image_url('yellow5', 'local_ml_dashboard2'),
        $OUTPUT->image_url('yellow6', 'local_ml_dashboard2'),
    ];

    $greenImages = [
        $OUTPUT->image_url('green1', 'local_ml_dashboard2'),
        $OUTPUT->image_url('green2', 'local_ml_dashboard2'),
        $OUTPUT->image_url('green3', 'local_ml_dashboard2'),
        $OUTPUT->image_url('green4', 'local_ml_dashboard2'),
        $OUTPUT->image_url('green5', 'local_ml_dashboard2'),
        $OUTPUT->image_url('green6', 'local_ml_dashboard2'),
        $OUTPUT->image_url('green7', 'local_ml_dashboard2'),
        $OUTPUT->image_url('green8', 'local_ml_dashboard2'),
    ];

// Función para seleccionar una imagen aleatoria
    function get_random_image($imageArray) {
        return $imageArray[array_rand($imageArray)];
    }




    // Convertir los datos obtenidos en un array compatible con Mustache
    if (!empty($processedData)) {
        foreach ($processedData as $data) {

            // Obtener el nombre del estudiante desde la tabla 'user'
            $user = $DB->get_record('user', ['id' => $data->userid], 'firstname, lastname');
            $username = $user->firstname . ' ' . $user->lastname;

            // Determinar nivel de desempeño
            $performanceLevel = 0;
            $performanceImage = "";
            if ($data->prediction_score >= 5 && $data->prediction_score < 7) {
                $performanceLevel = 1; // Rojo
                $performanceImage = get_random_image($redImages);
            } elseif ($data->prediction_score >= 7 && $data->prediction_score < 8) {
                $performanceLevel = 2; // Amarillo
                $performanceImage = get_random_image($yellowImages);
            } elseif ($data->prediction_score >= 8 && $data->prediction_score <= 10) {
                $performanceLevel = 3; // Verde
                $performanceImage = get_random_image($greenImages);
            } else{
                $performanceImage = $OUTPUT->image_url('camoodle_mid', 'local_ml_dashboard2');
            }


            $processedDataArray[] = [
                'userid' => $data->userid,
                'username' => $username,
                'hours_studied' => $data->hours_studied,
                'attendance' => $data->attendance,
                'inactive_time' => $data->inactive_time,
                'general_grade' => $data->general_grade,
                'forum_participations' => $data->forum_participations,
                'prediction_score' => $data->prediction_score,
                'recommendations' => $data->recommendations,
                'recommendations_teacher' => $data->recommendations_teacher,
                'last_updated' => date('Y-m-d H:i:s', $data->last_updated),
                'prediction_score_equals_negative_one' => $data->prediction_score == -1, // Verificar si es -1
                'is_red' => $data->prediction_score >= 5 && $data->prediction_score < 7, // Rojo entre 5 y 7
                'is_yellow' => $data->prediction_score >= 7 && $data->prediction_score < 8, // Amarillo entre 7 y 8
                'is_green' => $data->prediction_score >= 8 && $data->prediction_score <= 10, // Verde entre 8 y 10
                'performance_level' => $performanceLevel,
                'performance_image' => $performanceImage,

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



// Ruta de las imágenes del sidebar
$logoCamoodle = $OUTPUT->image_url('camoodle_logo1', 'local_ml_dashboard2');
$logoUcsg = $OUTPUT->image_url('ucsg_logo', 'local_ml_dashboard2');
$camoodles = $OUTPUT->image_url('camoodles', 'local_ml_dashboard2');
$randomRedImage = get_random_image($redImages);
$randomYellowImage = get_random_image($yellowImages);
$randomGreenImage = get_random_image($greenImages);

// Renderizar la plantilla con datos
echo $m->render('profiles', [
    'courses' => $courses,
    'selected_course' => $selectedCourseId,
    'selected_course_name' => $selectedCourseName,
    'processed_data' => $processedDataArray,
    'last_updated' => $lastUpdated ?? 'No disponible',
    'logo_url' => $logoUrl,
    'logo_camoodle' => $logoCamoodle,
    'logo_ucsg' => $logoUcsg,
    'camoodles' => $camoodles,
    'random_red_image' => $randomRedImage,
    'random_yellow_image' => $randomYellowImage,
    'random_green_image' => $randomGreenImage
]);

?>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const menuButton = document.getElementById('menuButton');
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');

        if (menuButton && sidebar && mainContent) {
            menuButton.addEventListener('click', (event) => {
                event.stopPropagation();

                // Alternar las clases del sidebar y del contenido principal
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

            // Cerrar el sidebar al hacer clic en un enlace del menú
            const menuLinks = sidebar.querySelectorAll('a');
            menuLinks.forEach(link => {
                link.addEventListener('click', () => {
                    sidebar.classList.add('-translate-x-full');
                    mainContent.classList.remove('ml-64');
                });
            });
        } else {
            console.error('El botón, el sidebar o el contenido principal no se encontraron.');
        }
    });


</script>

<!-- Script para procesar datos del curso -->
<script>
    function processCourseData(courseId) {
        if (courseId) {
            fetch('http://localhost/TIC/moodle/local/ml_dashboard2/start_task.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ courseid: courseId }),
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Tarea iniciada correctamente.');
                    } else {
                        alert('Error: ' + (data.message || 'No se pudo iniciar la tarea.'));
                    }
                })
                .catch(error => {
                    console.error('Error en la solicitud:', error);
                    alert('Error en la red o el servidor: ' + error.message);
                });
        } else {
            alert('Por favor, selecciona un curso antes de iniciar la tarea.');
        }
    }

    function startBackgroundTask(courseId) {
        if (!courseId) {
            alert('Por favor, selecciona un curso antes de iniciar la tarea.');
            return;
        }

        const button = document.getElementById('backgroundTaskButton');
        button.innerText = 'Procesando...';
        button.disabled = true;

        // Enviar la solicitud al backend para procesar la tarea
        fetch('http://localhost/TIC/moodle/local/ml_dashboard2/start_task.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ courseid: courseId }),
        })
            .then(response => {
                if (!response.ok) {
                    // Si la respuesta no es OK, devolver el texto completo para diagnóstico
                    return response.text().then(text => {
                        throw new Error(
                            `Error del servidor (HTTP ${response.status}): ${text}`
                        );
                    });
                }
                // Intentar parsear la respuesta como JSON
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    alert(`Tarea en segundo plano para el curso con ID ${courseId} iniciada exitosamente.`);
                } else {
                    alert('Error al iniciar la tarea: ' + (data.message || 'No se pudo iniciar.'));
                }
            })
            .catch(error => {
                console.error('Error en la solicitud o servidor:', error);
                alert('Hubo un problema: ' + error.message);
            })
            .finally(() => {
                button.innerText = 'Ejecutar Tarea en Segundo Plano';
                button.disabled = false;
            });
    }



    // Cambiar el curso cuando se seleccione en el selector
    document.getElementById('courseSelector').addEventListener('change', function () {
        const selectedCourseId = this.value;
        if (selectedCourseId) {
            window.location.href = `profiles.php?courseid=${selectedCourseId}`;
        }
    });
</script>
