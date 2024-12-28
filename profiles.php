<?php

// Configuración para registrar errores en un archivo
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt'); // Archivo de log en la misma carpeta
error_reporting(E_ALL);

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/vendor/autoload.php');
require_once(__DIR__ . '/ml_predictor.php'); // el predictor

// Configuración para registrar errores en un archivo
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt'); // Archivo de log
error_reporting(E_ALL);

// Verificar si el modelo de ML está habilitado
$ml_enabled = get_config('local_ml_dashboard2', 'enabled');

// Simular datos del estudiante (estos datos deberían venir del sistema o base de datos)
$student_data = [
    'study_hours' => 0.1,             // ¿Equivale a `Hours_Studied`?
    'attendance' => 0.1,             // ¿Equivale a `Attendance`?
    'assignments_completed' => 0.1,  // ¿Esto debería ser `Previous_Scores`?
    'feature4' => 0.1,               // ¿Debería ser `Sleep_Hours`?
    'feature5' => 0.1,               // ¿Debería ser `Tutoring_Sessions`?
    'feature6' => 0.1                // ¿Debería ser `Physical_Activity`?
];



$predicted_score = null;
if ($ml_enabled) {
    // Realizar predicción
    $predicted_score = predict_student_score($student_data);
    error_log("Predicción obtenida: " . print_r($predicted_score, true)); // Log
    //var_dump($predicted_score); die(); // Descomenta para prueba en la página
}


// Preparar datos para la plantilla Mustache
$m = new Mustache_Engine(array(
    'loader' => new Mustache_Loader_FilesystemLoader(__DIR__ . '/templates'),
));

echo $m->render('profiles', [
    'ml_enabled' => $ml_enabled,
    'predicted_score' => $predicted_score ?? 'No disponible', // Tomar el primer valor
    'study_hours' => $student_data['study_hours'],
    'attendance' => $student_data['attendance'],
    'assignments_completed' => $student_data['assignments_completed'],
    'feature4' => $student_data['feature4'],
    'feature5' => $student_data['feature5'],
    'feature6' => $student_data['feature6']
]);


