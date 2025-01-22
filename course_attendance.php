<?php
function calculate_attendance($userId, $courseId, $token, $apiUrl) {
    // Archivo para registrar los días de actividad
    $attendanceFile = __DIR__ . "/attendance_status.json";

    // Leer o inicializar el archivo de asistencia
    if (file_exists($attendanceFile)) {
        $attendanceData = json_decode(file_get_contents($attendanceFile), true);
    } else {
        $attendanceData = [];
    }

    // Registrar inicio del proceso en el log
    error_log("[INFO] Iniciando cálculo de asistencia para usuario {$userId} en el curso {$courseId}.");

    try {
        // Obtener la fecha de inicio del curso
        $function = 'core_course_get_courses';
        $url = $apiUrl . '?wstoken=' . $token . '&wsfunction=' . $function . '&moodlewsrestformat=json';

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

        $coursesData = json_decode($response, true);

        // Buscar el curso específico
        $courseStartDate = null;
        foreach ($coursesData as $course) {
            if ($course['id'] == $courseId) {
                $courseStartDate = date('Y-m-d', $course['startdate']);
                error_log("[INFO] Fecha de inicio del curso {$courseId}: {$courseStartDate}");
                break;
            }
        }

        if (!$courseStartDate) {
            throw new Exception("No se encontró la fecha de inicio para el curso {$courseId}.");
        }

        // Obtener actividad reciente del usuario
        $function = 'core_course_get_recent_courses';
        $url = $apiUrl . '?wstoken=' . $token . '&wsfunction=' . $function . '&moodlewsrestformat=json';

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

        if (isset($data['exception'])) {
            throw new Exception('Error al obtener datos: ' . $data['message']);
        }

        // Verificar la actividad del estudiante en el curso
        $currentDate = date('Y-m-d'); // Fecha actual
        foreach ($data as $course) {
            if ($course['id'] == $courseId) {
                $timeAccess = $course['timeaccess'] ?? 0;

                if ($timeAccess > 0) {
                    $lastAccessDate = date('Y-m-d', $timeAccess);

                    if (!isset($attendanceData["user_{$userId}_course_{$courseId}"])) {
                        $attendanceData["user_{$userId}_course_{$courseId}"] = [];
                    }

                    if (!in_array($lastAccessDate, $attendanceData["user_{$userId}_course_{$courseId}"])) {
                        $attendanceData["user_{$userId}_course_{$courseId}"][] = $lastAccessDate;
                        error_log("[INFO] Registrando actividad para el usuario {$userId} el día {$lastAccessDate} en el curso {$courseId}.");
                    }
                }
            }
        }

        // Guardar el estado actualizado en el archivo
        file_put_contents($attendanceFile, json_encode($attendanceData, JSON_PRETTY_PRINT));

        // Calcular el porcentaje de asistencia
        $daysActive = count($attendanceData["user_{$userId}_course_{$courseId}"] ?? []);
        $startDate = new DateTime($courseStartDate);
        $endDate = new DateTime();
        $totalDays = $startDate->diff($endDate)->days + 1;

        $attendancePercentage = $totalDays > 0 ? round(($daysActive / $totalDays) * 100) : 0;

        error_log("[INFO] Días activos para el usuario {$userId} en el curso {$courseId}: {$daysActive}");
        error_log("[INFO] Total de días desde el inicio del curso: {$totalDays}");
        error_log("[INFO] Porcentaje de asistencia calculado para el usuario {$userId} en el curso {$courseId}: {$attendancePercentage}%");

        return $attendancePercentage;

    } catch (Exception $e) {
        // Registrar errores en el archivo de error_log
        error_log("[ERROR] " . $e->getMessage());
        return 0;
    }
}
