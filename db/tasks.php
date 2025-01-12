<?php
defined('MOODLE_INTERNAL') || die();

$tasks = [
    [
        'classname' => 'local_ml_dashboard2\task\process_student_data',
        'blocking' => 0, // No bloquea otras tareas.
        'minute' => '*/5', // Corre cada 5 minutos.
        'hour' => '*', // Todas las horas.
        'day' => '*', // Todos los días.
        'dayofweek' => '*', // Todos los días de la semana.
        'month' => '*', // Todos los meses.
    ],
];
