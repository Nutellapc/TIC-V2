<?php
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/vendor/autoload.php');
require_once(__DIR__ . '/ml_predictor.php'); // el predictor

// Verificar si el modelo de ML está habilitado
$ml_enabled = get_config('local_ml_dashboard2', 'enabled');

// Simular datos del estudiante (estos datos deberían venir del sistema o base de datos)
$student_data = [
    'study_hours' => 12,
    'attendance' => 85,
    'assignments_completed' => 10,
    'feature4' => 0,
    'feature5' => 0,
    'feature6' => 0
];


$predicted_score = null;
if ($ml_enabled) {
    // Realizar predicción
    $predicted_score = predict_student_score($student_data);
}

// Preparar datos para la plantilla Mustache
$m = new Mustache_Engine(array(
    'loader' => new Mustache_Loader_FilesystemLoader(__DIR__ . '/templates'),
));

echo $m->render('profiles', [
    'ml_enabled' => $ml_enabled,
    'predicted_score' => $predicted_score[0] ?? 'No disponible', // Tomar el primer valor
    'study_hours' => $student_data['study_hours'],
    'attendance' => $student_data['attendance'],
    'assignments_completed' => $student_data['assignments_completed'],
    'feature4' => $student_data['feature4'],
    'feature5' => $student_data['feature5'],
    'feature6' => $student_data['feature6']
]);
