<?php
function get_user_active_hours($userId, $courseId, $token, $apiUrl) {
    // Ruta del archivo para registrar el estado y tiempo de los usuarios en línea
    $onlineStatusFile = __DIR__ . "/online_status.json";

    // Leer o inicializar el archivo de estado
    if (file_exists($onlineStatusFile)) {
        $onlineStatus = json_decode(file_get_contents($onlineStatusFile), true);
    } else {
        $onlineStatus = [];
    }

    // Recuperar tiempo activo acumulado previo y máximo valor registrado
    $activeTimeInSeconds = $onlineStatus["user_{$userId}_course_{$courseId}"]['activeTimeInSeconds'] ?? 0;
    $maxActiveTime = $onlineStatus["user_{$userId}_course_{$courseId}"]['maxActiveTime'] ?? 0;

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

                // Verificar si el último acceso fue hace menos de 5 minutos (300 segundos)
                if (($currentTime - $timeAccess) <= 300) {
                    $isOnline = true;

                    // Si activeTimeInSeconds es menor o igual a 0, asignar maxActiveTime
                    if ($activeTimeInSeconds <= 0 || $activeTimeInSeconds < $maxActiveTime) {
                        $activeTimeInSeconds = $maxActiveTime;
                    }

                    // Calcular tiempo activo
                    $lastChecked = $onlineStatus["user_{$userId}_course_{$courseId}"]['lastChecked'] ?? $currentTime;
                    $timeSpent = $currentTime - $lastChecked;

                    // Acumular tiempo activo
                    $activeTimeInSeconds += $timeSpent;

                    // Actualizar el valor máximo registrado
                    $maxActiveTime = max($maxActiveTime, $activeTimeInSeconds);

                    // Actualizar el estado en el archivo
                    $onlineStatus["user_{$userId}_course_{$courseId}"] = [
                        'isOnline' => true,
                        'lastChecked' => $currentTime,
                        'activeTimeInSeconds' => $activeTimeInSeconds,
                        'maxActiveTime' => $maxActiveTime,
                    ];
                }
            }
        }
    }

    // Si el usuario no está activo, mantener el tiempo acumulado y restar tiempo
    if (!$isOnline) {
        $currentTime = time();
        $lastChecked = $onlineStatus["user_{$userId}_course_{$courseId}"]['lastChecked'] ?? $currentTime;

        // Calcular tiempo de inactividad
        $timeInactive = $currentTime - $lastChecked;

        // Reducir tiempo acumulado pero no más allá de 0
        $activeTimeInSeconds = max(0, $activeTimeInSeconds - $timeInactive);

        // Actualizar el estado en el archivo
        $onlineStatus["user_{$userId}_course_{$courseId}"] = [
            'isOnline' => false,
            'lastChecked' => $currentTime,
            'activeTimeInSeconds' => $activeTimeInSeconds,
            'maxActiveTime' => $maxActiveTime,
        ];
    }

    // Guardar el estado actualizado en el archivo
    file_put_contents($onlineStatusFile, json_encode($onlineStatus, JSON_PRETTY_PRINT));

    // Convertir tiempo activo a horas y devolver el valor máximo registrado
    $activeTimeInHours = round($maxActiveTime / 3600, 2);

    // Mostrar tiempo activo
    echo $isOnline
        ? "El estudiante está actualmente en línea en el curso {$courseId}.<br>"
        : "El estudiante no está en línea. Tiempo acumulado: {$activeTimeInHours} horas.<br>";

    return $activeTimeInHours;
}

