<?php
function get_user_courses($userId, $token, $apiUrl) {
    // Nombre de la función de Moodle para obtener cursos
    $function = 'core_enrol_get_users_courses';

    // URL de la API con los parámetros necesarios
    $url = $apiUrl . '?wstoken=' . $token . '&wsfunction=' . $function . '&moodlewsrestformat=json&userid=' . $userId;

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

    // Decodificar la respuesta JSON
    $courses = json_decode($response, true);

    // Verificar si hay errores en la respuesta
    if (isset($courses['exception'])) {
        throw new Exception('Error al obtener los cursos: ' . $courses['message']);
    }

    return $courses;
}

