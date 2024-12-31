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

    // Recuperar tiempo inactivo acumulado previo y máximo valor registrado
    $inactiveTimeInSeconds = $inactiveStatus["user_{$userId}_course_{$courseId}"]['inactiveTimeInSeconds'] ?? 0;
    $maxInactiveTimeInSeconds = $inactiveStatus["user_{$userId}_course_{$courseId}"]['maxInactiveTime'] ?? 0;

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

    $isOnline = false;

    foreach ($data as $course) {
        if ($course['id'] == $courseId) {
            $timeAccess = $course['timeaccess'] ?? 0;

            // Validar que timeAccess tenga un valor válido
            if ($timeAccess > 0) {
                $currentTime = time();

                // Verificar si el último acceso fue hace menos de 1 hora
                if (($currentTime - $timeAccess) <= 3600) {
                    $isOnline = true;

                    // Calcular el tiempo de actividad desde la última revisión
                    $lastChecked = $inactiveStatus["user_{$userId}_course_{$courseId}"]['lastChecked'] ?? $currentTime;
                    $timeActive = $currentTime - $lastChecked;

                    // Si el usuario vuelve a estar activo, actualizar maxInactiveTimeInSeconds
                    if ($inactiveTimeInSeconds < $maxInactiveTimeInSeconds) {
                        $inactiveTimeInSeconds = $maxInactiveTimeInSeconds;
                    }

                    // Actualizar estado en el archivo de inactividad
                    $inactiveStatus["user_{$userId}_course_{$courseId}"] = [
                        'inactiveTimeInSeconds' => $inactiveTimeInSeconds,
                        'lastChecked' => $currentTime,
                        'maxInactiveTime' => $maxInactiveTimeInSeconds
                    ];
                }
            }
        }
    }

    // Si el usuario no está en línea, acumular tiempo inactivo
    if (!$isOnline) {
        $currentTime = time();
        $lastChecked = $inactiveStatus["user_{$userId}_course_{$courseId}"]['lastChecked'] ?? $currentTime;
        $timeInactive = $currentTime - $lastChecked;

        // Acumular tiempo inactivo
        $inactiveTimeInSeconds += $timeInactive;

        // Actualizar el valor máximo registrado si el nuevo valor es mayor
        $maxInactiveTimeInSeconds = max($maxInactiveTimeInSeconds, $inactiveTimeInSeconds);

        // Actualizar estado de inactividad
        $inactiveStatus["user_{$userId}_course_{$courseId}"] = [
            'inactiveTimeInSeconds' => $inactiveTimeInSeconds,
            'lastChecked' => $currentTime,
            'maxInactiveTime' => $maxInactiveTimeInSeconds
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

    // Convertir tiempo inactivo a horas basado en el máximo
    $inactiveTimeInHours = round($maxInactiveTimeInSeconds / 3600, 1);

    // Depuración
//    echo "Tiempo inactivo acumulado (basado en el máximo): {$inactiveTimeInHours} horas.<br>";
//    echo "Número de días registrados: {$days}.<br>";

    // Devolver promedio de horas inactivas por día
    return $inactiveTimeInHours / $days;
}
