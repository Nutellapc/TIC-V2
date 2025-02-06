<?php

defined('MOODLE_INTERNAL') || die();

/**
 * Realiza una solicitud a la API de OpenAI usando la clave configurada en el plugin.
 *
 * @param string $prompt El texto de entrada para enviar a OpenAI.
 * @return string La respuesta generada por OpenAI.
 * @throws moodle_exception En caso de errores al conectar con la API.
 */
function local_ml_dashboard2_call_openai($prompt) {
    global $CFG;

    // Leer la clave de API desde la configuración del plugin
    $apikey = get_config('local_ml_dashboard2', 'openaiapikey');
    if (!$apikey) {
        throw new moodle_exception('No se configuró la clave de API de OpenAI en los ajustes del plugin.');
    }

    // URL de la API de OpenAI
    $apiUrl = 'https://api.openai.com/v1/chat/completions';

    // Crear los datos para la solicitud
    $postData = [
        'model' => 'gpt-3.5-turbo', // Modelo a usar
        'messages' => [
            [
                'role' => 'system',
                'content' => 'Eres un asistente educativo integrado en Moodle. Responde siempre de manera breve y concisa en un solo párrafo.'
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ],
        'max_tokens' => 100, // Limitar la longitud de la respuesta a unas pocas frases
        'temperature' => 0.7 // Configuración de creatividad
    ];

    // Inicializar cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apikey
    ]);

    // Ejecutar la solicitud y capturar la respuesta
    $response = curl_exec($ch);

    // Verificar errores de cURL
    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        curl_close($ch);
        throw new moodle_exception('Error al conectar con OpenAI: ' . $error_msg);
    }

    curl_close($ch);

    // Decodificar la respuesta
    $data = json_decode($response, true);
    if (isset($data['error'])) {
        throw new moodle_exception('Error en la respuesta de OpenAI: ' . $data['error']['message']);
    }

    // Verificar y devolver el texto generado
    if (isset($data['choices'][0]['message']['content'])) {
        return trim($data['choices'][0]['message']['content']); // Devuelve el contenido recortando espacios adicionales
    } else {
        throw new moodle_exception('No se recibió una respuesta válida de OpenAI.');
    }
}

