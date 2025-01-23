<?php
function get_user_inactive_hours($userId, $courseId, $token, $apiUrl) {
    // Rutas de los archivos
    $inactiveStatusFile = __DIR__ . "/inactive_status.json";
    $attendanceFile = __DIR__ . "/attendance_status.json";

    // Leer o inicializar el archivo de estado para tiempo de inactividad
    if (file_exists($inactiveStatusFile)) {
        $inactiveStatus = json_decode(file_get_contents($inactiveStatusFile), true);
    } else {
        $inactiveStatus = [];
    }

    // Recuperar tiempo inactivo acumulado previo y último tiempo chequeado
    $inactiveTimeInSeconds = $inactiveStatus["user_{$userId}_course_{$courseId}"]['inactiveTimeInSeconds'] ?? 0;
    $lastChecked = $inactiveStatus["user_{$userId}_course_{$courseId}"]['lastChecked'] ?? time();

    // Endpoint de Moodle
    $function = 'core_course_get_recent_courses';
    $url = $apiUrl . '?wstoken=' . $token . '&wsfunction=' . $function . '&moodlewsrestformat=json';

    // Hacer la solicitud
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        curl_close($ch);
        throw new Exception("Error en la solicitud cURL: " . $error_msg);
    }

    curl_close($ch);

    $data = json_decode($response, true);

    // Verificar si la respuesta contiene errores
    if (isset($data['exception'])) {
        throw new Exception('Error al obtener datos: ' . $data['message']);
    }

    $currentTime = time();
    $lastAccessTime = 0;

    // Procesar los datos de los cursos
    foreach ($data as $course) {
        if ($course['id'] == $courseId) {
            $lastAccessTime = $course['timeaccess'] ?? 0;

            // Registrar la última conexión en el log
            if ($lastAccessTime > 0) {
                $lastAccessFormatted = date('Y-m-d H:i:s', $lastAccessTime);
                error_log("[INFO] Última conexión del usuario {$userId} al curso {$courseId}: {$lastAccessFormatted}");
            } else {
                error_log("[INFO] No se encontró información de conexión previa para el usuario {$userId} en el curso {$courseId}.");
            }
            break;
        }
    }

    // Validar que se obtuvo el último acceso al curso
    if ($lastAccessTime > 0) {
        // Calcular el tiempo transcurrido desde el último acceso
        $timeSinceLastAccess = $currentTime - $lastAccessTime;

        // Verificar si han pasado más de dos horas desde el último acceso
        if ($timeSinceLastAccess > 7200) {
            // Acumular tiempo inactivo
            $inactiveTimeInSeconds += $timeSinceLastAccess;
            error_log("[INFO] Tiempo inactivo acumulado para el usuario {$userId} en el curso {$courseId}: {$timeSinceLastAccess} segundos.");
        }

        // Actualizar el estado de inactividad
        $inactiveStatus["user_{$userId}_course_{$courseId}"] = [
            'inactiveTimeInSeconds' => $inactiveTimeInSeconds,
            'lastChecked' => $currentTime
        ];
    }

    // Guardar el estado actualizado en el archivo de inactividad
    file_put_contents($inactiveStatusFile, json_encode($inactiveStatus, JSON_PRETTY_PRINT));

    // Leer número de días desde el archivo de asistencia
    if (file_exists($attendanceFile)) {
        $attendanceData = json_decode(file_get_contents($attendanceFile), true);
        $days = count($attendanceData["user_{$userId}_course_{$courseId}"] ?? []);
    } else {
        $days = 1; // Valor predeterminado en caso de error
    }

    // Evitar división por 0
    if ($days == 0) {
        $days = 1;
    }

    // Convertir tiempo inactivo a horas
    $inactiveTimeInHours = round($inactiveTimeInSeconds / 3600, 1);

    // Depuración adicional
    error_log("[INFO] Total de días registrados para el usuario {$userId} en el curso {$courseId}: {$days}");
    error_log("[INFO] Tiempo inactivo total para el usuario {$userId} en el curso {$courseId}: {$inactiveTimeInHours} horas.");

    // Devolver promedio de horas inactivas por día
    return $inactiveTimeInHours / $days;
}


