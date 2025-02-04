<?php
require_once('api_client.php');

header('Content-Type: application/json');
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['userid']) || !isset($data['message'])) {
    echo json_encode(['success' => false, 'error' => 'Datos insuficientes']);
    exit;
}

// Enviar el mensaje al usuario
$response = callMoodleAPI("core_message_send_instant_messages", [
    "messages[0][touserid]" => $data['userid'],
    "messages[0][text]" => $data['message'],
    "messages[0][textformat]" => 1
]);

if ($response) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Error al enviar el mensaje']);
}
?>
