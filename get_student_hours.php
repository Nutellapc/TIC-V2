<?php
function get_user_active_hours($userId, $courseId, $token, $apiUrl) {
    // Ruta del archivo para registrar el estado y tiempo de los usuarios en línea
    $onlineStatusFile = __DIR__ . "/online_status.json";
    $attendanceFile = __DIR__ . "/attendance_status.json"; // Archivo de asistencia

    // Leer o inicializar el archivo de estado
    if (file_exists($onlineStatusFile)) {
        $onlineStatus = json_decode(file_get_contents($onlineStatusFile), true);
    } else {
        $onlineStatus = [];
    }

    // Recuperar tiempo activo acumulado previo
    $activeTimeInSeconds = $onlineStatus["user_{$userId}_course_{$courseId}"]['activeTimeInSeconds'] ?? 0;
    $lastCheckedDate = $onlineStatus["user_{$userId}_course_{$courseId}"]['lastCheckedDate'] ?? null;

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

    $currentDate = date('Y-m-d'); // Fecha actual
    $timeAddedToday = 0; // Tiempo sumado hoy

    foreach ($data as $course) {
        if ($course['id'] == $courseId) {
            $timeAccess = $course['timeaccess'] ?? 0;

            // Validar que timeAccess tenga un valor válido
            if ($timeAccess > 0) {
                $accessDate = date('Y-m-d', $timeAccess); // Fecha del último acceso

                // Registrar información en el log
                error_log("[INFO] Último acceso registrado para usuario {$userId} en curso {$courseId}: {$accessDate}.");

                if ($accessDate === $currentDate) {
                    $timeElapsed = time() - $timeAccess; // Tiempo transcurrido desde el último acceso

                    // Limitar el tiempo sumado a un máximo de 2 horas (7200 segundos)
                    $timeToAdd = min($timeElapsed, 7200 - $timeAddedToday);
                    $timeAddedToday += $timeToAdd;

                    // Actualizar tiempo activo acumulado
                    $activeTimeInSeconds += $timeToAdd;

                    // Registrar el tiempo agregado en el log
                    error_log("[INFO] Tiempo agregado hoy para usuario {$userId} en curso {$courseId}: {$timeToAdd} segundos (Total acumulado: {$activeTimeInSeconds} segundos).");
                }
            }
        }
    }

    // Actualizar el estado en el archivo
    $onlineStatus["user_{$userId}_course_{$courseId}"] = [
        'lastCheckedDate' => $currentDate,
        'activeTimeInSeconds' => $activeTimeInSeconds
    ];

    file_put_contents($onlineStatusFile, json_encode($onlineStatus, JSON_PRETTY_PRINT));

    // Convertir tiempo activo a horas
    $activeTimeInHours = round($activeTimeInSeconds / 3600, 2);

    // Calcular número de semanas basado en el archivo de asistencia
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

    $weeks = ceil($days / 7); // Redondear hacia arriba para incluir semanas incompletas

    // Evitar división por 0 en semanas
    if ($weeks == 0) {
        $weeks = 1;
    }

    // Registrar en el log el tiempo activo total y el número de semanas
    error_log("[INFO] Tiempo activo total para usuario {$userId} en curso {$courseId}: {$activeTimeInHours} horas.");
    error_log("[INFO] Número de semanas registradas: {$weeks}.");

    // Devolver promedio de horas activas por semana
    return $activeTimeInHours / $weeks;
}
