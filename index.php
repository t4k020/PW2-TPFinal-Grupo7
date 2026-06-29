<?php
require_once __DIR__ . '/helpers/Autoloader.php';
require_once __DIR__ . '/vendor/autoload.php';
Session::start();
$config = new Configurator();
$router = $config->getRouter();

$controller = $_GET['controller'] ?? '';
$method = $_GET['method'] ?? '';

$publicControllers = ['login', 'register'];
$publicRoutes = ['usuario/ver'];

if (!(in_array($controller, $publicControllers) || in_array("$controller/$method", $publicRoutes))
    && !isset($_SESSION['id'])) {
    Redirect::to('/login');
}

$router->dispatch(
    $_GET['controller'] ?? '',
        $_GET['method'] ?? '',
        $_GET['params'] ?? null
);

if (in_array($controller, $publicControllers) && isset($_SESSION['id'])) {
    Redirect::to('/lobby');
}
//validar admin y sesion en index
//añadir ruleta, elige aleatoriamente una categoria
// añadir editor, que labura preguntas,no vee estadisticas
//graficar las estadisticas, boton de imprimir cada uno