<?php

class LobbyController {
    private $model;
    private $renderer;
    private $request;

    public function __construct($model, $renderer, $request)
    {
        $this->model = $model;
        $this->renderer = $renderer;
        $this->request = $request;
    }

    public function mostrar()
    {
        if (!isset($_SESSION["id"])) {
            Redirect::to("/login");
            return;
        }
        $usuario = $this->model->obtenerDatosUsuario($_SESSION["id"]);
        $this->renderer->render("lobbyView", [
            "usuario" => $usuario["username"],
            "puntaje" => $usuario["puntaje"],
            "trampitas" => $usuario["trampitas"]]);
    }
}