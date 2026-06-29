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
        $usuario = $this->model->obtenerDatosUsuario($_SESSION["id"]);
        $mejorPartida = $this->partidaModel->getPartidaConMejorPuntajePorUsuario($_SESSION["id"]);
        $mejorPuntaje = $mejorPartida["puntaje"] ?? 0;
        $mejorFecha = $mejorPartida["fecha_partida"] ?? '';
        $this->renderer->render("lobbyView", [
            "usuario" => $usuario["username"],
            "fotoPerfil" => $usuario["fotoPerfil"],
            "mejorPuntaje" => $mejorPuntaje,
            "mejorFecha" => $mejorFecha,
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