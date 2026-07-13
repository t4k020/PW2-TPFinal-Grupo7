<?php

class LobbyController {
    private $usuarioModel;
    private $preguntaModel;
    private $categoriaModel;
    private $partidaModel;
    private $renderer;
    private $request;

    public function __construct($usuarioModel, $preguntaModel, $categoriaModel, $partidaModel, $renderer, $request)
    {
        $this->usuarioModel = $usuarioModel;
        $this->partidaModel = $partidaModel;
        $this->renderer = $renderer;
        $this->request = $request;
        $this->preguntaModel = $preguntaModel;
        $this->categoriaModel = $categoriaModel;
    }

    public function mostrar()
    {
        $usuario = $this->usuarioModel->obtenerDatosUsuario($_SESSION["id"]);
        $mejorPartida = $this->partidaModel->getPartidaConMejorPuntajePorUsuario($_SESSION["id"]);
        $mejorPuntaje = $mejorPartida["puntaje"] ?? 0;
        $mejorFecha = $mejorPartida["fecha_partida"] ?? '';
        $this->renderer->render("lobbyView", [
            "usuario" => $usuario["username"],
            "fotoPerfil" => $usuario["fotoPerfil"],
            "mejorPuntaje" => $mejorPuntaje,
            "mejorFecha" => $mejorFecha,
            "trampitas" => $usuario["trampitas"],
            "maestria"  => $usuario["maestria"],
            // Validacion por rol
            "esAdmin"   => ($usuario["rol"] === "ADMIN"),
            "esEditor"  => ($usuario["rol"] === "EDITOR")
        ]);

    }

    public function ranking() {
        $listaRanking = $this->partidaModel->getRankingGeneral();

        foreach ($listaRanking as $index => &$usuario) {//el & es para que trabaje sobre el dato real y no sobre la copia
            $usuario['puesto'] = $index + 1;
        }

        $this->renderer->render("rankingView", ["usuarios" => $listaRanking]);
    }

    public function sugerirPregunta() {
        Log::info("[Lobby] Sugerir Pregunta");
        $categorias = $this->categoriaModel->getCategorias();
        $this->renderer->render("crearPregunta",
            ["categorias" => $categorias,
                "opciones" => [1,2,3,4]]);
    }

    public function guardarSugerencia()
    {

        if (!isset($_SESSION['username'])) {
            Log::error("[Lobby] Sugerencia no guardada - Usuario no logueado");
            Redirect::to("/login");
            return;
        }

        Log::info("[Lobby] Guardar Sugerencia");
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || isset($_POST['id'])) {
            Log::error("[Lobby] Sugerencia no guardada");
            Redirect::to("/lobby");
        }
        $estado = "PENDIENTE";
        $creadoPor = $_SESSION['username'];

        $categoriaId = $_POST['categoria_id'];
        $pregunta = $_POST['texto_pregunta'];
        $respuestaCorrecta = $_POST['respuesta_correcta'];
        $respuestas = $_POST['respuestas'];

        $exito = $this->preguntaModel->guardarPreguntaYRespuestas(
            $categoriaId, $pregunta, $respuestaCorrecta, $respuestas, $estado, $creadoPor);
        if ($exito)
            Log::info("[Lobby] Sugerencia guardada");
        else
            Log::error("[Lobby] Sugerencia no guardada");

        Redirect::to("/lobby");
    }
}