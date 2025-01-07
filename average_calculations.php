<?php
function calculate_average_hours_studied($studentIds, $courseId, $token, $apiUrl) {
    $total_hours = 0;
    $student_count = count($studentIds);

    // Mostrar cuántos estudiantes se procesarán
    error_log("Número de estudiantes a procesar: $student_count");

    // Iterar sobre cada ID de estudiante y sumar las horas estudiadas
    foreach ($studentIds as $studentId) {
        // Llamar a la función para obtener las horas estudiadas por el estudiante actual
        $hours_studied = get_user_active_hours($studentId, $courseId, $token, $apiUrl);
        error_log("Horas estudiadas por el estudiante con ID $studentId: $hours_studied");

        // Ajustar el valor al rango permitido (0-44)
        $hours_studied = min(max($hours_studied, 0), 44);
        error_log("Horas ajustadas para el estudiante con ID $studentId: $hours_studied");

        // Sumar las horas ajustadas al total
        $total_hours += $hours_studied;
    }

    // Mostrar el total de horas acumuladas
    error_log("Total de horas acumuladas: $total_hours");

    // Calcular el promedio
    $average = $student_count > 0 ? $total_hours / $student_count : 0;

    // Mostrar el promedio calculado
    error_log("Promedio de horas estudiadas: $average");

    return $average;
}

function calculate_average_attendance($studentIds, $courseId, $token, $apiUrl) {
    $total_attendance = 0;
    $student_count = count($studentIds);

    // Mostrar cuántos estudiantes se procesarán
    error_log("Número de estudiantes a procesar para calcular el porcentaje de asistencia: $student_count");

    // Iterar sobre cada ID de estudiante y sumar los porcentajes de asistencia
    foreach ($studentIds as $studentId) {
        // Llamar a la función para obtener el porcentaje de asistencia del estudiante actual
        $attendance = calculate_attendance($studentId, $courseId, $token, $apiUrl);
        error_log("Porcentaje de asistencia para el estudiante con ID $studentId: $attendance");

        // Asegurarse de que el porcentaje está en el rango válido (0-100)
        $attendance = min(max($attendance, 0), 100);
        error_log("Porcentaje de asistencia ajustado para el estudiante con ID $studentId: $attendance");

        // Sumar el porcentaje ajustado al total
        $total_attendance += $attendance;
    }

    // Mostrar el total de asistencia acumulada
    error_log("Total de porcentaje de asistencia acumulado: $total_attendance");

    // Calcular el promedio
    $average_attendance = $student_count > 0 ? $total_attendance / $student_count : 0;

    // Mostrar el promedio calculado
//    echo "Promedio de porcentaje de asistencia: $average_attendance";

    return $average_attendance;
}

function calculate_average_inactivity_hours($studentIds, $courseId, $token, $apiUrl) {
    $total_inactivity_hours = 0;
    $student_count = count($studentIds);

    // Mostrar cuántos estudiantes se procesarán
    error_log("Número de estudiantes a procesar para calcular horas de inactividad: $student_count");

    // Iterar sobre cada ID de estudiante y sumar las horas de inactividad
    foreach ($studentIds as $studentId) {
        // Llamar a la función para obtener las horas de inactividad del estudiante actual
        $inactivity_hours = get_user_inactive_hours($studentId, $courseId, $token, $apiUrl);
        error_log("Horas de inactividad para el estudiante con ID $studentId: $inactivity_hours");

        // Asegurarse de que las horas están en el rango válido (0 o más)
        $inactivity_hours = max($inactivity_hours, 0);
        error_log("Horas de inactividad ajustadas para el estudiante con ID $studentId: $inactivity_hours");

        // Sumar las horas ajustadas al total
        $total_inactivity_hours += $inactivity_hours;
    }

    // Mostrar el total de horas de inactividad acumuladas
    error_log("Total de horas de inactividad acumuladas: $total_inactivity_hours");

    // Calcular el promedio
    $average_inactivity_hours = $student_count > 0 ? $total_inactivity_hours / $student_count : 0;

//    // Mostrar el promedio calculado
//    echo "Promedio de horas de inactividad: $average_inactivity_hours";

    return $average_inactivity_hours;
}

function calculate_average_general_grade($studentIds, $courseId, $token, $apiUrl) {
    $total_general_grade = 0;
    $student_count = count($studentIds);

    // Mostrar cuántos estudiantes se procesarán
    error_log("Número de estudiantes a procesar para calcular la calificación general: $student_count");

    // Iterar sobre cada ID de estudiante y sumar las calificaciones generales
    foreach ($studentIds as $studentId) {
        // Llamar a la función para obtener la calificación general del estudiante actual
        $general_grade = calculate_general_grade($studentId, $courseId, $token, $apiUrl);
        error_log("Calificación general para el estudiante con ID $studentId: $general_grade");

        // Asegurarse de que la calificación está en el rango válido (0-100)
        $general_grade = min(max($general_grade, 0), 100);
        error_log("Calificación general ajustada para el estudiante con ID $studentId: $general_grade");

        // Sumar la calificación ajustada al total
        $total_general_grade += $general_grade;
    }

    // Mostrar el total de calificaciones generales acumuladas
    error_log("Total de calificaciones generales acumuladas: $total_general_grade");

    // Calcular el promedio
    $average_general_grade = $student_count > 0 ? $total_general_grade / $student_count : 0;

    // Mostrar el promedio calculado
//    echo "Promedio de calificación general: $average_general_grade";

    return $average_general_grade;
}

function calculate_average_forum_participations($studentIds, $courseId, $token, $apiUrl) {
    $total_forum_participations = 0;
    $student_count = count($studentIds);

    // Mostrar cuántos estudiantes se procesarán
    error_log("Número de estudiantes a procesar para calcular participaciones en foros: $student_count");

    // Iterar sobre cada ID de estudiante y sumar las participaciones en foros
    foreach ($studentIds as $studentId) {
        // Llamar a la función para obtener las participaciones en foros del estudiante actual
        $forum_participations = count_forum_participations($studentId, $courseId, $token, $apiUrl);
        error_log("Participaciones en foros para el estudiante con ID $studentId: $forum_participations");

        // Asegurarse de que las participaciones sean un número válido (0 o más)
        $forum_participations = max($forum_participations, 0);
        error_log("Participaciones en foros ajustadas para el estudiante con ID $studentId: $forum_participations");

        // Sumar las participaciones ajustadas al total
        $total_forum_participations += $forum_participations;
    }

    // Mostrar el total de participaciones acumuladas
    error_log("Total de participaciones en foros acumuladas: $total_forum_participations");

    // Calcular el promedio
    $average_forum_participations = $student_count > 0 ? $total_forum_participations / $student_count : 0;

    // Mostrar el promedio calculado
    error_log("Promedio de participaciones en foros: $average_forum_participations");

    return $average_forum_participations;
}
