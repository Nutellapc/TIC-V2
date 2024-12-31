<?php
function calculate_general_grade($userId, $courseId, $token, $apiUrl)
{
    $totalGrade = 0;
    $activityCount = 0;

// Obtener las tareas (assignments)
    $function = 'mod_assign_get_assignments';
    $url = $apiUrl . '?wstoken=' . $token . '&wsfunction=' . $function . '&moodlewsrestformat=json';

    $response = file_get_contents($url);
    $data = json_decode($response, true);

    foreach ($data['courses'] as $course) {
        if ($course['id'] == $courseId) {
            foreach ($course['assignments'] as $assignment) {
                $function = 'mod_assign_get_submission_status';
                $assignmentUrl = $apiUrl . '?wstoken=' . $token . '&wsfunction=' . $function . '&moodlewsrestformat=json&assignid=' . $assignment['id'] . '&userid=' . $userId;

                $assignmentResponse = file_get_contents($assignmentUrl);
                $assignmentData = json_decode($assignmentResponse, true);

                if (isset($assignmentData['feedback']['grade']['grade'])) {
                    $totalGrade += $assignmentData['feedback']['grade']['grade'];
                    $activityCount++;
                }
            }
        }
    }

// Obtener los quizz
    $function = 'mod_quiz_get_quizzes_by_courses';
    $quizUrl = $apiUrl . '?wstoken=' . $token . '&wsfunction=' . $function . '&moodlewsrestformat=json&courseids[0]=' . $courseId;

    $quizResponse = file_get_contents($quizUrl);
    $quizData = json_decode($quizResponse, true);

    foreach ($quizData['quizzes'] as $quiz) {
        $function = 'mod_quiz_get_user_attempts';
        $attemptUrl = $apiUrl . '?wstoken=' . $token . '&wsfunction=' . $function . '&moodlewsrestformat=json&quizid=' . $quiz['id'] . '&userid=' . $userId;

        $attemptResponse = file_get_contents($attemptUrl);
        $attemptData = json_decode($attemptResponse, true);

        foreach ($attemptData['attempts'] as $attempt) {
            if (isset($attempt['grade'])) {
                $totalGrade += $attempt['grade'];
                $activityCount++;
            }
        }
    }

// Calcular la calificación general
    $generalGrade = ($activityCount > 0) ? round($totalGrade / $activityCount, 2) : 0;

    return $generalGrade*10 ;
}
