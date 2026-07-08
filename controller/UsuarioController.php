<?php

class UsuarioController
{
    private $model;
    private $renderer;
    private $request;
    private $partidaModel;


    public function __construct($model, $renderer, $request, $partidaModel)
    {
        $this->model = $model;
        $this->renderer = $renderer;
        $this->request = $request;
        $this->partidaModel = $partidaModel;
    }

    public function ver($username)
    {
        Log::info("UsuarioController::ver $username");

        $usuario = $this->model->getUsuario($username);
        if (!$usuario)
            throw new Exception("Usuario no encontrado");

        $partidas = $this->partidaModel->getPartidasPorUsuario($usuario['id']);
        $puntajeTotal = $this->partidaModel->getPuntajeTotalUsuario($usuario['id']);

        $this->renderer->render("verUsuarioView",
            ['Usuario' => $usuario,
                'puntaje' => $puntajeTotal,
                'Partidas' => $partidas]);
    }
    //Editar y eliminar(hay que ver cuando tenga relaciones asociadas)
}
