<?php

class LobbyController {
    private $model;
    private $partidaModel;
    private $renderer;
    private $request;

    public function __construct($model, $partidaModel, $renderer, $request)
    {
        $this->model = $model;
        $this->partidaModel = $partidaModel;
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
        $puntajeTotal = $this->partidaModel->getPuntajeTotalUsuario($_SESSION["id"]);
        $this->renderer->render("lobbyView", [
            "usuario" => $usuario["username"],
            "fotoPerfil" => $usuario["fotoPerfil"],
            "puntaje" => $puntajeTotal,
            "trampitas" => $usuario["trampitas"],
            "esAdmin"   => ($usuario["username"] === "Admin"),
            "maestria"  => $usuario["maestria"]
        ]);

    }

    public function ranking() {
        $listaRanking = $this->partidaModel->getRankingGeneral();

        foreach ($listaRanking as $index => &$usuario) {//el & es para que trabaje sobre el dato real y no sobre la copia
            $usuario['puesto'] = $index + 1;
        }

        $this->renderer->render("rankingView", ["usuarios" => $listaRanking]);
    }
}