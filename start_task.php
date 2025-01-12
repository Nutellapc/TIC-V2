<?php
if (php_sapi_name() === 'cli') {
    define('CLI_SCRIPT', true);
}

require_once(__DIR__ . '/../../config.php');
require_login();

// Verificar que el usuario sea administrador
if (!is_siteadmin()) {
    die('Acceso denegado. Solo los administradores pueden ejecutar esta tarea.');
}

header('Content-Type: application/json');

// Verificar que la solicitud sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    http_response_code(405);
    exit;
}

// Obtener datos de la solicitud
$data = json_decode(file_get_contents('php://input'), true);
$courseId = isset($data['courseid']) ? (int)$data['courseid'] : null;

// Validar que se haya proporcionado el ID del curso
if (!$courseId) {
    echo json_encode(['success' => false, 'message' => 'El ID del curso no fue proporcionado.']);
    http_response_code(400);
    exit;
}

// Verificar que el curso exista
global $DB;
if (!$DB->record_exists('course', ['id' => $courseId])) {
    echo json_encode(['success' => false, 'message' => 'El curso no existe.']);
    http_response_code(400);
    exit;
}

// Ruta completa al ejecutable PHP
$phpBinary = "C:/xampp/php/php.exe"; // Cambia si la ruta no es correcta

// Ruta completa al archivo process_background_tasks.php
$scriptPath = realpath(__DIR__ . "/process_background_tasks.php");
if (!$scriptPath) {
    echo json_encode(['success' => false, 'message' => 'No se encontró el archivo process_background_tasks.php.']);
    http_response_code(500);
    exit;
}

// Construir el comando para ejecutar la tarea en segundo plano
$command = escapeshellarg($phpBinary) . " " . escapeshellarg($scriptPath) . " --courseid=" . escapeshellarg($courseId);

// Registrar el comando en el log para verificar su ejecución
error_log("Ruta de PHP: $phpBinary");
error_log("Ruta del Script: $scriptPath");
error_log("Comando construido: $command");

// Ejecutar el comando sin redirección de salida para depuración
$output = shell_exec($command);
$status = ($output !== null) ? 0 : 1;

// Registrar el resultado de la ejecución
error_log("Estado de la ejecución: $status");
error_log("Salida del comando: $output");

// Verificar si la ejecución fue exitosa
if ($status === 0) {
    echo json_encode([
        'success' => true,
        'message' => 'La tarea en segundo plano ha comenzado.',
        'command' => $command,
        'output' => $output
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Hubo un problema al iniciar la tarea.',
        'command' => $command,
        'output' => $output
    ]);
    http_response_code(500);
}
exit;
