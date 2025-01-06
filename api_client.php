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

