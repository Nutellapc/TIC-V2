<?php
function count_forum_participations($userId, $courseId, $token, $apiUrl) {
    $participations = 0;

    // Paso 1: Obtener los foros del curso
    $function = 'mod_forum_get_forums_by_courses';
    $forumsUrl = $apiUrl . '?wstoken=' . $token . '&wsfunction=' . $function . '&moodlewsrestformat=json&courseids[0]=' . $courseId;

    //echo "Solicitando foros del curso con URL: $forumsUrl\n";
    $forumsResponse = file_get_contents($forumsUrl);

    // Verificar si la respuesta es válida
    if ($forumsResponse === false) {
        throw new Exception("Error al obtener los foros del curso.");
    }

    //echo "Respuesta de foros obtenida:\n";
    //print_r($forumsResponse);

    $forumsData = json_decode($forumsResponse, true);

    // Verificar si hubo un error en la respuesta
    if (isset($forumsData['exception'])) {
        throw new Exception("Error al obtener los foros: " . $forumsData['message']);
    }

    // Verificar si se encontraron foros
    if (empty($forumsData)) {
        //echo "No se encontraron foros en el curso con ID $courseId.\n";
        return $participations;
    }

    //echo "Foros encontrados:\n";
    //print_r($forumsData);

    // Paso 2: Obtener las discusiones de cada foro
    foreach ($forumsData as $forum) {
        $forumId = $forum['id'];

        $function = 'mod_forum_get_forum_discussions';
        $discussionsUrl = $apiUrl . '?wstoken=' . $token . '&wsfunction=' . $function . '&moodlewsrestformat=json&forumid=' . $forumId;

        //echo "Solicitando discusiones del foro ID $forumId con URL: $discussionsUrl\n";
        $discussionsResponse = file_get_contents($discussionsUrl);

        // Verificar si la respuesta es válida
        if ($discussionsResponse === false) {
            //echo "Error al obtener discusiones del foro ID $forumId.\n";
            continue;
        }

        //echo  "Respuesta de discusiones obtenida para foro ID $forumId:\n";
        //print_r($discussionsResponse);

        $discussionsData = json_decode($discussionsResponse, true);

        // Verificar si hubo un error en la respuesta
        if (isset($discussionsData['exception'])) {
            //echo "Error al obtener discusiones en el foro ID $forumId: " . $discussionsData['message'] . "\n";
            continue;
        }

        // Verificar si hay discusiones
        if (empty($discussionsData['discussions'])) {
            //echo "No se encontraron discusiones en el foro ID $forumId.\n";
            continue;
        }

        //echo "Discusiones encontradas para foro ID $forumId:\n";
        //print_r($discussionsData['discussions']);

        // Paso 3: Contar una sola participación del usuario
        foreach ($discussionsData['discussions'] as $discussion) {
            if ($discussion['userid'] == $userId) {
                $participations++; // Contar solo una participación por foro
                //echo "Usuario $userId participó en el foro ID $forumId, discusión ID {$discussion['id']}.\n";
                break; // Salir del bucle de discusiones al encontrar una participación
            }
        }
    }

//    echo "Total de participaciones únicas del usuario $userId en el curso $courseId: $participations\n";

    return $participations;
}
