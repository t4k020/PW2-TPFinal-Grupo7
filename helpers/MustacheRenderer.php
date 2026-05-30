<?php

require_once(__DIR__ . '/../vendor/autoload.php');

class MustacheRenderer {
    private $mustache;

    public function __construct($folder) {
        // Inicializamos directamente el motor de plantillas
        $this->mustache = new Mustache_Engine([
            'loader' => new Mustache_Loader_FilesystemLoader($folder)
        ]);
    }

    public function render($template, $data = []) {
        echo $this->mustache->render($template, $data);
    }
}

