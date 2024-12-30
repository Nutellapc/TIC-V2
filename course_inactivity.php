<?php
// Agregar una variable global para almacenar el máximo valor de tiempo inactivo
function get_user_inactive_hours($userId, $courseId, $token, $apiUrl) {
// Ruta del archivo para registrar el tiempo de inactividad
$inactiveStatusFile = __DIR__ . "/inactive_status.json";
$maxValueFile = __DIR__ . "/max_inactive_status.json"; // Nuevo archivo para almacenar el máximo

// Leer o inicializar el archivo de estado para tiempo de inactividad
if (file_exists($inactiveStatusFile)) {
$inactiveStatus = json_decode(file_get_contents($inactiveStatusFile), true);
} else {
$inactiveStatus = [];
}

// Leer o inicializar el archivo para almacenar el valor máximo de tiempo inactivo
if (file_exists($maxValueFile)) {
$maxInactiveStatus = json_decode(file_get_contents($maxValueFile), true);
} else {
$maxInactiveStatus = [];
}

// Recuperar tiempo inactivo acumulado previo
$inactiveTimeInSeconds = $inactiveStatus["user_{$userId}_course_{$courseId}"]['inactiveTimeInSeconds'] ?? 0;

// Recuperar el valor máximo registrado previamente
$maxInactiveTimeInSeconds = $maxInactiveStatus["user_{$userId}_course_{$courseId}"]['maxInactiveTimeInSeconds'] ?? 0;

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

// Calcular el tiempo de actividad desde la última revisión
$lastChecked = $inactiveStatus["user_{$userId}_course_{$courseId}"]['lastChecked'] ?? $currentTime;
$timeActive = $currentTime - $lastChecked;

// Asegurar que no se reste más allá del último valor acumulado
if ($timeActive < $inactiveTimeInSeconds) {
$inactiveTimeInSeconds -= $timeActive;
}

// Actualizar estado en el archivo de inactividad
$inactiveStatus["user_{$userId}_course_{$courseId}"] = [
'inactiveTimeInSeconds' => $inactiveTimeInSeconds,
'lastChecked' => $currentTime,
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

// Actualizar estado de inactividad
$inactiveStatus["user_{$userId}_course_{$courseId}"] = [
'inactiveTimeInSeconds' => $inactiveTimeInSeconds,
'lastChecked' => $currentTime,
];
}

// Guardar el estado actualizado en el archivo de inactividad
file_put_contents($inactiveStatusFile, json_encode($inactiveStatus, JSON_PRETTY_PRINT));

// Actualizar el valor máximo registrado si el nuevo valor es mayor
if ($inactiveTimeInSeconds > $maxInactiveTimeInSeconds) {
$maxInactiveTimeInSeconds = $inactiveTimeInSeconds;
$maxInactiveStatus["user_{$userId}_course_{$courseId}"] = [
'maxInactiveTimeInSeconds' => $maxInactiveTimeInSeconds,
];

// Guardar el nuevo valor máximo en el archivo
file_put_contents($maxValueFile, json_encode($maxInactiveStatus, JSON_PRETTY_PRINT));
}

// Convertir tiempo inactivo a horas basado en el máximo
$inactiveTimeInHours = round($maxInactiveTimeInSeconds / 3600, 1);

// Depuración
//echo "Tiempo inactivo acumulado (basado en el máximo): {$inactiveTimeInHours} horas.<br>";

return $inactiveTimeInHours;
}
