<?php

class EditorController
{
    private $model;
    private $renderer;
    private $request;

    private $preguntaModel;
    private $usuarioModel;
    private $partidaModel;

    public function __construct($model, $renderer, $request, $preguntaModel, $usuarioModel, $partidaModel)
    {
        $this->model = $model;
        $this->renderer = $renderer;
        $this->request = $request;
        $this->preguntaModel = $preguntaModel;
        $this->usuarioModel = $usuarioModel;
        $this->partidaModel = $partidaModel;
    }

    //aca hay que mover solo las funciones correspondientes al editor
    public function mostrar() {

    }
}