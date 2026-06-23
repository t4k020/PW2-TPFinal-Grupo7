<?php
require_once __DIR__ . '/helpers/Autoloader.php';
require_once __DIR__ . '/vendor/autoload.php';
Session::start();
$config = new Configurator();
$router = $config->getRouter();

$router->dispatch(
    $_GET['controller'] ?? '',
        $_GET['method'] ?? '',
        $_GET['params'] ?? null
);

//validar admin y sesion en index
//añadir ruleta, elige aleatoriamente una categoria
// añadir editor, que labura preguntas,no vee estadisticas
//graficar las estadisticas, boton de imprimir cada uno