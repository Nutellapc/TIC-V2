<?php

$token = "143bfe295993ff7caa6e404efea7d245";
$apiUrl = "http://localhost/TIC/moodle/webservice/rest/server.php";

/**
 * Llama a la API REST de Moodle con los parámetros especificados.
 *
 * @param string $functionName Nombre de la función de la API a llamar.
 * @param array $params Parámetros necesarios para la función de la API.
 * @return array|null Respuesta decodificada de la API o null en caso de error.
 */
function callMoodleAPI($functionName, $params = []) {
    global $token, $apiUrl;

    $params['wstoken'] = $token;
    $params['wsfunction'] = $functionName;
    $params['moodlewsrestformat'] = 'json';

    $url = $apiUrl . '?' . http_build_query($params);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Validar respuesta HTTP
    if ($httpCode !== 200) {
        error_log("Error HTTP al llamar a la API ($functionName): Código $httpCode");
        return null;
    }

    $decodedResponse = json_decode($response, true);

    // Validar respuesta de la API
    if (isset($decodedResponse['exception'])) {
        error_log("Error en la API ($functionName): {$decodedResponse['message']}");
        return null;
    }

    return $decodedResponse;
}

/**
 * Obtiene los cursos inscritos por un usuario.
 *
 * @param int $userId ID del usuario.
 * @return array Lista de cursos o un array vacío si no se encuentran cursos.
 */
function get_user_courses($userId) {
    $function = 'core_enrol_get_users_courses';
    $params = ['userid' => $userId];

    $courses = callMoodleAPI($function, $params);

    if (empty($courses)) {
        error_log("No se encontraron cursos para el usuario con ID $userId.");
        return [];
    }

    return $courses;
}

/**
 * Obtiene los detalles del usuario actualmente autenticado.
 *
 * @return array|null Detalles del usuario (ID y nombre completo) o null en caso de error.
 */
function get_authenticated_user() {
    $function = 'core_webservice_get_site_info';

    $siteInfo = callMoodleAPI($function);

    if (empty($siteInfo) || !isset($siteInfo['userid'], $siteInfo['fullname'])) {
        error_log("No se pudo obtener la información del usuario autenticado.");
        return null;
    }

    return [
        'id' => $siteInfo['userid'],
        'fullname' => $siteInfo['fullname'],
    ];
}

/**
 * Obtiene los IDs de los usuarios inscritos en un curso, a partir del tercer ID.
 *
 * @param int $courseId ID del curso.
 * @return array Lista de IDs de usuarios a partir del tercero, o un array vacío si no hay suficientes usuarios.
 */
function get_user_ids_from_course($courseId) {
    $function = 'core_enrol_get_enrolled_users';
    $params = ['courseid' => $courseId];

    $enrolledUsers = callMoodleAPI($function, $params);

    if (empty($enrolledUsers)) {
        error_log("No se encontraron usuarios inscritos en el curso con ID $courseId.");
        return [];
    }

    // Extraer los IDs de los usuarios y omitir los dos primeros
    $userIds = array_column($enrolledUsers, 'id');
    return array_slice($userIds, 2); // A partir del tercer ID
}

/**
 * Envía un mensaje a un usuario en Moodle utilizando la API REST.
 *
 * @param int $userId ID del usuario destinatario.
 * @param string $message Mensaje a enviar.
 * @return array|null Respuesta de la API o null en caso de error.
 */
function send_moodle_message($userId, $message) {
    $function = 'core_message_send_instant_messages';
    $params = [
        'messages' => [
            [
                'touserid' => $userId,
                'text' => $message,
                'textformat' => 1
            ]
        ]
    ];

    return callMoodleAPI($function, $params);
}


?>
