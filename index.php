<?php
require_once __DIR__ . '/helpers/Autoloader.php';
require_once __DIR__ . '/vendor/autoload.php';
Session::start();
$config = new Configurator();
$router = $config->getRouter();

$controller = $_GET['controller'] ?? '';
$method = $_GET['method'] ?? '';

switch ($controller) {
    case 'panelAdmin':
        if (!isset($_SESSION['id']) || $_SESSION['rol'] != 'ADMIN')
            Redirect::to('/lobby');
        break;
    case 'panelEditor':
        if (!isset($_SESSION['id']) || !in_array($_SESSION['rol'], ['ADMIN', 'EDITOR']))
            Redirect::to('/lobby');
        break;
    case 'register':
        if (isset($_SESSION['id']))
            Redirect::to('/lobby');
        break;
    case 'login':
        if (isset($_SESSION['id']) && $method != 'logout')
            Redirect::to('/lobby');
        break;
    case 'usuario':
        if (!isset($_SESSION['id']) && $method != 'ver')
            Redirect::to('/login');
        break;
    default:
        if (!isset($_SESSION['id']))
            Redirect::to('/login');
        break;
}

$router->dispatch($controller, $method, $_GET['params'] ?? null);
