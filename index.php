<?php
require_once __DIR__ . '/helpers/Autoloader.php';
Session::start();
$config = new Configurator();
$router = $config->getRouter();

$router->dispatch(
    $_GET['controller'] ?? '',
        $_GET['method'] ?? ''
);