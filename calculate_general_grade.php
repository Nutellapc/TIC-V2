<?php
function calculate_general_grade($userId, $courseId, $token, $apiUrl)
{
    $function = 'gradereport_user_get_grade_items';
    $url = $apiUrl . '?wstoken=' . $token . '&wsfunction=' . $function . '&moodlewsrestformat=json&userid=' . $userId . '&courseid=' . $courseId;

    try {
        // Solicitar datos de la API
        $response = file_get_contents($url);
        $data = json_decode($response, true);

        // Depuración: registrar datos completos devueltos por la API
//        error_log("Datos completos devueltos por la API:\n" . print_r($data, true));

        // Validar si hubo un error en la API
        if (isset($data['exception'])) {
            throw new Exception('Error al obtener las calificaciones: ' . $data['message']);
        }

        $gradesCollected = []; // Calificaciones de actividades
        $quizzesCollected = []; // Calificaciones de quizzes

        // Procesar ítems de calificaciones
        foreach ($data['usergrades'][0]['gradeitems'] as $gradeItem) {
            // Excluir explícitamente el Course Total
            if ($gradeItem['itemmodule'] === null) {
                continue;
            }

            // Manejar calificaciones numéricas válidas
            if (is_numeric($gradeItem['gradeformatted']) || is_numeric($gradeItem['graderaw'])) {
                $grade = is_numeric($gradeItem['graderaw']) ? $gradeItem['graderaw'] : $gradeItem['gradeformatted'];

                if (strtolower($gradeItem['itemmodule']) === 'quiz') {
                    // Registrar como quiz
                    $quizzesCollected[] = [
                        'quiz' => $gradeItem['itemname'] ?? 'Quiz sin nombre',
                        'grade' => $grade
                    ];
                } elseif (strtolower($gradeItem['itemmodule']) === 'assign') {
                    // Registrar como actividad regular
                    $gradesCollected[] = [
                        'activity' => $gradeItem['itemname'] ?? 'Sin nombre',
                        'grade' => $grade
                    ];
                }
            }
        }

        // Validar que se recolectaron calificaciones
        if (empty($gradesCollected) && empty($quizzesCollected)) {
            throw new Exception('No se encontraron calificaciones para el usuario.');
        }

        // Contar actividades y quizzes
        $activityCount = count($gradesCollected);
        $quizCount = count($quizzesCollected);

        // Sumar las calificaciones de actividades y quizzes
        $totalGrade = 0;
        foreach ($gradesCollected as $grade) {
            $totalGrade += $grade['grade'];
        }
        foreach ($quizzesCollected as $quiz) {
            $totalGrade += $quiz['grade'];
        }

        // Calcular el promedio general
        $totalDivisor = $activityCount + $quizCount;
        $generalGrade = ($totalDivisor > 0) ? round($totalGrade / $totalDivisor, 2) : 0;

        // Registrar en el error_log las actividades, quizzes, el promedio y el total divisor
        error_log("Calificaciones recolectadas para el usuario {$userId} en el curso {$courseId}:\n");
        foreach ($gradesCollected as $grade) {
            error_log("- Actividad: {$grade['activity']} | Calificación: {$grade['grade']}");
        }
        foreach ($quizzesCollected as $quiz) {
            error_log("- Quiz: {$quiz['quiz']} | Calificación: {$quiz['grade']}");
        }
        error_log("Total Divisor (actividades + quizzes) para el usuario {$userId} en el curso {$courseId}: {$totalDivisor}");
        error_log("Promedio general calculado (sin incluir Course Total) para el usuario {$userId} en el curso {$courseId}: {$generalGrade}");

        return $generalGrade * 10;

    } catch (Exception $e) {
        // Registrar errores en el archivo de error_log
        error_log("Error al procesar las calificaciones para el usuario {$userId} en el curso {$courseId}: " . $e->getMessage());
        return 0;
    }
}
