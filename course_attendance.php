<?php
function calculate_attendance($userId, $courseId, $token, $apiUrl) {
// "Calculando la asistencia del estudiante...<br>";
flush();

// Ruta al archivo para registrar los días de actividad
$attendanceFile = __DIR__ . "/attendance_status.json";

// Leer o inicializar el archivo de asistencia
if (file_exists($attendanceFile)) {
$attendanceData = json_decode(file_get_contents($attendanceFile), true);
} else {
$attendanceData = [];
}

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

// Depuración: Ver la respuesta completa
//echo "Respuesta de la API: <pre>" . print_r($data, true) . "</pre>";

// Verificar la actividad del estudiante en el curso
$currentDate = date('Y-m-d'); // Fecha actual
$hasLoggedToday = false;

foreach ($data as $course) {
if ($course['id'] == $courseId) {
$timeAccess = $course['timeaccess'] ?? 0;

// Validar que timeAccess tenga un valor válido
if ($timeAccess > 0) {
$lastAccessDate = date('Y-m-d', $timeAccess);

// Verificar si el último acceso fue hoy
if ($lastAccessDate === $currentDate) {
$hasLoggedToday = true;
}

// Registrar la fecha en la lista de actividad si no está ya registrada
if (!isset($attendanceData["user_{$userId}_course_{$courseId}"])) {
$attendanceData["user_{$userId}_course_{$courseId}"] = [];
}

if (!in_array($lastAccessDate, $attendanceData["user_{$userId}_course_{$courseId}"])) {
$attendanceData["user_{$userId}_course_{$courseId}"][] = $lastAccessDate;
}
}
}
}

// Guardar el estado actualizado en el archivo
file_put_contents($attendanceFile, json_encode($attendanceData, JSON_PRETTY_PRINT));

// Calcular el porcentaje de asistencia
$daysActive = count($attendanceData["user_{$userId}_course_{$courseId}"] ?? []);
$startDate = new DateTime('2024-12-28'); // Cambiar a la fecha inicial del curso
$endDate = new DateTime(); // Fecha actual
$totalDays = $startDate->diff($endDate)->days + 1; // Total de días desde el inicio

$attendancePercentage = $totalDays > 0 ? round(($daysActive / $totalDays) * 100) : 0;

// Mostrar asistencia
//echo "Asistencia acumulada: {$attendancePercentage}%<br>";

return $attendancePercentage;
}
