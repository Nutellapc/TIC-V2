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
require_once(__DIR__ . '/lib.php'); // Asegúrate de que esta ruta sea correcta
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
    $hours_studied = min(max(get_user_active_hours($userId, $courseId, $token, $apiUrl), 0), 44); // Límite entre 0 y 44 horas de estudio
    $attendance_percentage = min(max(calculate_attendance($userId, $courseId, $token, $apiUrl), 0), 100); // Límite entre 50% (mínimo aceptable) y 100% de asistencia.
    $inactivity_hours = min(max(get_user_inactive_hours($userId, $courseId, $token, $apiUrl), 0), 12); // Límite entre 6 y 12 horas de inactividad
    $general_grade = min(max(calculate_general_grade($userId, $courseId, $token, $apiUrl), 0), 100); // Límite entre 50 y 100 en las calificaciones generales.
    $forum_participations = min(max(count_forum_participations($userId, $courseId, $token, $apiUrl), 0), 8); // Límite entre 0 y 8 participaciones en foros



    // Preparar datos del estudiante para la predicción
    $student_data = [
        'hours_studied' => $hours_studied,
        'attendance' => $attendance_percentage,
        'inactivity_hours' => $inactivity_hours,
        'previous_scores' => $general_grade,
        'tutoring_sessions' => $forum_participations,
        'physical_activity' => 1
    ];


    if (
        $hours_studied >= 0 && $hours_studied <= 44 && // Límite para horas de estudio
        $attendance_percentage >= 50 && $attendance_percentage <= 100 && // Límite para asistencia
        $inactivity_hours >= 6 && $inactivity_hours <= 12 && // Límite para horas de inactividad
        $general_grade >= 50 && $general_grade <= 100 && // Límite para calificación general
        $forum_participations >= 0 && $forum_participations <= 8 // Límite para participaciones en foros
    ) {

    // Calcular predicción
    $ml_enabled = get_config('local_ml_dashboard2', 'enabled');
    $predicted_score = $ml_enabled ? predict_student_score($student_data) : null;

    // Normalizar la predicción

        $normalized_prediction = array_sum($student_data) === 0
            ? 0
            : round(min(max($predicted_score, 0), 100) / 10, 2);

//    echo "Normalized prediction: $normalized_prediction\n";
    } else {
        // Usar un valor especial para indicar que la predicción no está disponible
        $normalized_prediction = -1; // Indica "predicción no disponible"
    }

// Preparar datos para enviar a ChatGPT
    $prompt = "Basado en los siguientes datos de un estudiante, genera recomendaciones personalizadas y directas para mejorar su desempeño académico. Las recomendaciones deben ser cortas, específicas y en un solo párrafo:\n";
    $prompt .= "Horas de estudio: {$hours_studied}\n";
    $prompt .= "Porcentaje de asistencia: {$attendance_percentage}%\n";
    $prompt .= "Horas de inactividad: {$inactivity_hours}\n";
    $prompt .= "Calificación general: {$general_grade}/10\n";
    $prompt .= "Participaciones en foros: {$forum_participations}\n";
    $prompt .= "Predicción del desempeño: {$normalized_prediction}/10\n";
    $prompt .= "Por favor, evita cualquier tipo de contenido adicional o explicaciones extensas.";



// Llamar a la API de ChatGPT
    try {
        $recommendations_text = local_ml_dashboard2_call_openai($prompt);
        error_log("[INFO] Recomendaciones generadas por ChatGPT para el usuario {$userId} en el curso {$courseId}: {$recommendations_text}");
    } catch (Exception $e) {
        // En caso de error con la API, establecer un mensaje predeterminado
        $recommendations_text = "No se pudieron generar recomendaciones debido a un error con la API de ChatGPT.";
        error_log("[ERROR] Error al generar recomendaciones con ChatGPT: " . $e->getMessage());
    }

    // Eliminar saltos de línea innecesarios de las recomendaciones
    $recommendations_text = str_replace(["\r\n", "\r", "\n"], " ", $recommendations_text);
    $recommendations_text = trim($recommendations_text); // Eliminar espacios adicionales al inicio y final

    // Preparar datos para enviar a ChatGPT para generar un consejo psicopedagógico
    $prompt_teacher = "Basado en las siguientes características de un estudiante, genera un consejo psicopedagógico corto y directo para el profesor. El consejo debe ser claro, específico y en un solo párrafo:\n";
    $prompt_teacher .= "Horas de estudio: {$hours_studied}\n";
    $prompt_teacher .= "Porcentaje de asistencia: {$attendance_percentage}%\n";
    $prompt_teacher .= "Horas de inactividad: {$inactivity_hours}\n";
    $prompt_teacher .= "Calificación general: {$general_grade}/10\n";
    $prompt_teacher .= "Participaciones en foros: {$forum_participations}\n";
    $prompt_teacher .= "Predicción del desempeño: {$normalized_prediction}/10\n";
    $prompt_teacher .= "Por favor, ofrece sugerencias específicas para que el profesor pueda apoyar de manera efectiva al estudiante.";

// Llamar a la API de ChatGPT
    try {
        $recommendations_teacher_text = local_ml_dashboard2_call_openai($prompt_teacher);
        error_log("[INFO] Consejo psicopedagógico generado por ChatGPT para el usuario {$userId} en el curso {$courseId}: {$recommendations_teacher_text}");
    } catch (Exception $e) {
        // En caso de error con la API, establecer un mensaje predeterminado
        $recommendations_teacher_text = "No se pudo generar el consejo psicopedagógico debido a un error con la API de ChatGPT.";
        error_log("[ERROR] Error al generar consejo psicopedagógico con ChatGPT: " . $e->getMessage());
    }

// Eliminar saltos de línea innecesarios del consejo
    $recommendations_teacher_text = str_replace(["\r\n", "\r", "\n"], " ", $recommendations_teacher_text);
    $recommendations_teacher_text = trim($recommendations_teacher_text); // Eliminar espacios adicionales al inicio y final



    // Preparar datos para la tabla
    $record = [
        'userid' => $userId,
        'username' => $username, // Agregar el nombre del usuario
        'courseid' => $courseId,
        'hours_studied' => $hours_studied,
        'attendance' => $attendance_percentage,
        'inactive_time' => $inactivity_hours,
        'general_grade' => $general_grade/10,
        'forum_participations' => $forum_participations,
        'prediction_score' => $normalized_prediction,
        'recommendations' => $recommendations_text,
        'recommendations_teacher' => $recommendations_teacher_text,
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
