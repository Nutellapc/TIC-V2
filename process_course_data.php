<?php

require_once(__DIR__ . '/../../config.php');

// Verificar si el script se ejecuta desde CLI o navegador
if (php_sapi_name() === 'cli') {
    // Lógica para CLI
    echo "El script se está ejecutando desde la CLI.\n";

    // Asegúrate de incluir cualquier lógica específica para CLI aquí.
    // Ejemplo: manejar tareas de fondo.

} else {
    // Asegurar que el usuario esté autenticado
    require_login();

    // Capturar el parámetro del curso desde la URL
    $courseId = optional_param('courseid', 0, PARAM_INT); // Obtiene 'courseid' de la URL

    // Verificar si se pasó un courseid válido
    if (!$courseId) {
        echo "<h3>Error: No se pasó un ID de curso válido.</h3>";
        exit;
    }

    echo "<h3>Procesando datos para el curso con ID: $courseId</h3>";

    try {
        // Obtener los datos procesados de la tabla 'plugin_student_activity'
        global $DB;
        $processedData = $DB->get_records('plugin_student_activity', ['courseid' => $courseId]);

        // Mostrar resultados
        if ($processedData) {
            echo "<h3>Datos encontrados:</h3><ul>";
            foreach ($processedData as $data) {
                echo "<li>Usuario ID: {$data->userid}, Horas estudiadas: {$data->hours_studied}, Asistencia: {$data->attendance}%</li>";
            }
            echo "</ul>";
        } else {
            echo "<h3>No se encontraron datos para el curso con ID: $courseId</h3>";
        }
    } catch (Exception $e) {
        echo "<h3>Error al procesar los datos: " . $e->getMessage() . "</h3>";
    }
}
?>
