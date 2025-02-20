<?php
// Cargar el modelo de Machine Learning
function load_model() {
    // Ruta al modelo .keras guardado
    $model_path = __DIR__ . '/stu_model.keras';
    if (!file_exists($model_path)) {
        throw new Exception('Modelo no encontrado en: ' . $model_path);
    }
    return $model_path;
}

// Realizar predicción con los datos del estudiante
function predict_student_score($student_data) {
    // Verificar que los datos del estudiante están completos
    $required_keys = ['hours_studied', 'attendance', 'inactivity_hours', 'previous_scores', 'tutoring_sessions', 'physical_activity'];

    foreach ($required_keys as $key) {
        if (!array_key_exists($key, $student_data)) {
            throw new Exception("Falta el dato requerido: $key");
        }
    }

    // Cargar modelo
    $model_path = load_model();

    // Crear un script Python para hacer predicciones
    $script = <<<EOT
    import sys
    import tensorflow as tf
    import numpy as np
    import pandas as pd
    import json
    import os
    from sklearn.preprocessing import StandardScaler

    # Suprimir logs de TensorFlow
    os.environ['TF_CPP_MIN_LOG_LEVEL'] = '3'

    try:
        # Ruta al modelo y datos de entrada
        model_path = sys.argv[1]
        study_hours = float(sys.argv[2])
        attendance = float(sys.argv[3])
        sleep_hours = float(sys.argv[4])
        previous_scores = float(sys.argv[5])
        tutoring_sessions = float(sys.argv[6])
        physical_activity = float(sys.argv[7])

        # Cargar el modelo
        model = tf.keras.models.load_model(model_path)

        # Leer los datos de entrenamiento para ajustar el scaler
        data = pd.read_csv("StudentPerformanceFactors.csv")  # Asegúrate de tener este archivo disponible
        features = ['Hours_Studied', 'Attendance', 'Sleep_Hours', 'Previous_Scores', 'Tutoring_Sessions', 'Physical_Activity']
        X_train = data[features]

        # Ajustar el scaler
        scaler = StandardScaler()
        scaler.fit(X_train)

        # Escalar los datos de entrada
        input_data = np.array([[study_hours, attendance, sleep_hours, previous_scores, tutoring_sessions, physical_activity]])
        scaled_input = scaler.transform(input_data)

        # Realizar predicción
        prediction = model.predict(scaled_input)

        # Devolver resultado como JSON
        result = {"prediction": float(prediction[0][0])}
        print(json.dumps(result))

    except Exception as e:
        # Devolver error como JSON válido
        error_result = {"error": str(e)}
        print(json.dumps(error_result))

    EOT;



    // Guardar el script Python temporalmente
    $temp_script_path = sys_get_temp_dir() . '/ml_predictor_script.py';
    if (!file_put_contents($temp_script_path, $script)) {
        throw new Exception("No se pudo crear el archivo de script temporal en $temp_script_path");
    }

    // Ejecutar el script Python y capturar la salida
    $command = escapeshellcmd("python $temp_script_path " . escapeshellarg($model_path) . " " .
        escapeshellarg($student_data['hours_studied']) . " " .
        escapeshellarg($student_data['attendance']) . " " .
        escapeshellarg($student_data['inactivity_hours']) . " " .
        escapeshellarg($student_data['previous_scores']) . " " .
        escapeshellarg($student_data['tutoring_sessions']) . " " .
        escapeshellarg($student_data['physical_activity'])
    );

    $output = shell_exec("$command 2>&1");
    error_log("Salida del script Python: $output");


    // Filtrar la salida para extraer solo el JSON
    preg_match('/\{.*\}/s', $output, $matches);
    if (isset($matches[0])) {
        $output = $matches[0];
    }

    // Eliminar el script temporal
    if (file_exists($temp_script_path)) {
        unlink($temp_script_path);
    }

    // Procesar salida
    if ($output === null) {
        throw new Exception("Error al ejecutar el script de predicción.");
    }

    // Decodificar JSON
    $result = json_decode($output, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Error al decodificar la salida JSON: " . json_last_error_msg() . "\nSalida: $output");
    }

    // Log para verificar la salida decodificada
//    error_log("Predicción decodificada: " . print_r($result, true));


    if (isset($result['error'])) {
        throw new Exception("Error en el script Python: " . $result['error']);
    }


    // Retornar solo la predicción
    return $result['prediction'];
    //return ["prediction" => $result['prediction'], "raw_output" => $output];

}
