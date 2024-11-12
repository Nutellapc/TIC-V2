<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plugin</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php
// Cargar la librería de Mustache
require 'vendor/autoload.php';

// Configurar Mustache
$mustache = new Mustache_Engine([
    'loader' => new Mustache_Loader_FilesystemLoader(dirname(__FILE__) . '/templates'),
]);

// Definir datos dinámicos que se pasarán a la plantilla
$data = [
    'dashboard_title' => 'Mi Dashboard en Moodle',
    'page_title' => 'Página Principal',
    'username' => 'UsuarioEjemplo',
    'total_sales' => '1000',  // Ejemplo de datos dinámicos
];

// Renderizar la plantilla Mustache (index.mustache)
echo $mustache->render('index', $data);
?>

</body>
</html>
