<?php
function calculate_general_grade($userId, $courseId, $token, $apiUrl)
{
    // Definir la función de la API
    $function = 'gradereport_user_get_grade_items';
    $url = $apiUrl . '?wstoken=' . $token . '&wsfunction=' . $function . '&moodlewsrestformat=json&userid=' . $userId . '&courseid=' . $courseId;

    // Hacer la solicitud a la API
    $response = file_get_contents($url);
    $data = json_decode($response, true);

    // Validar la respuesta
    if (isset($data['exception'])) {
        throw new Exception('Error al obtener las calificaciones: ' . $data['message']);
    }

    // Calcular el promedio general
    $totalGrade = 0;
    $activityCount = 0;

    foreach ($data['usergrades'][0]['gradeitems'] as $gradeItem) {
        if (isset($gradeItem['gradeformatted']) && is_numeric($gradeItem['gradeformatted'])) {
            $totalGrade += $gradeItem['gradeformatted'];
            $activityCount++;
        }
    }

    // Calcular el promedio general
    $generalGrade = ($activityCount > 0) ? round($totalGrade / $activityCount, 2) : 0;

    return $generalGrade*10;
}
