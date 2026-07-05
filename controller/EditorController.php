<?php

class EditorController {
    private $preguntaModel;
    private $usuarioModel;
    private $renderer;
    private $request;

    public function __construct($preguntaModel, $usuarioModel, $renderer, $request) {
        $this->preguntaModel = $preguntaModel;
        $this->usuarioModel = $usuarioModel;
        $this->renderer = $renderer;
        $this->request = $request;
    }

    // Méto do centralizado de seguridad
    private function verificarPermisos() {
        if (!isset($_SESSION['rol']) || ($_SESSION['rol'] !== 'EDITOR' && $_SESSION['rol'] !== 'ADMIN')) {
            header("Location: /lobby");
            exit();
        }
    }

    public function mostrar() {
        $this->verificarPermisos();

        // Buscamos los datos del usuario logueado usando su sesión
        $usuario = $this->usuarioModel->getUsuario($_SESSION["username"]);

        $this->renderer->render("editorView", [
            "usuario" => $usuario["username"],
            "esEditor" => true
        ]);
    }

    public function verSugeridas() {
        $this->verificarPermisos();
        Log::info("Mostrando Preguntas Sugeridas al Editor");

        $preguntasSugeridas = $this->preguntaModel->getPreguntasSugeridas();
        $this->renderer->render("preguntasSugeridas", [
            "lista_sugeridas" => $preguntasSugeridas,
            "hubo_sugerencias" => !empty($preguntasSugeridas)
        ]);
    }

    public function verReportes() {
        $this->verificarPermisos();
        $preguntasReportadas = $this->preguntaModel->getPreguntasReportadas();

        $this->renderer->render("verPreguntasReportadas", [
            "lista_reportes" => $preguntasReportadas,
            "hubo_reportes" => !empty($preguntasReportadas)
        ]);
    }

    public function aprobarPregunta() {
        $this->verificarPermisos();
        $idPregunta = isset($_POST['id_pregunta']) ? intval($_POST['id_pregunta']) : 0;
        $this->preguntaModel->aprobarPregunta($idPregunta);
        header("Location: /Editor/verSugeridas");
        exit();
    }

    public function eliminarPregunta() {
        $this->verificarPermisos();
        $idPregunta = isset($_POST['id_pregunta']) ? intval($_POST['id_pregunta']) : 0;
        $this->preguntaModel->borrarPregunta($idPregunta);
        header("Location: /Editor/verReportes");
        exit();
    }

    public function ignorarPregunta() {
        $this->verificarPermisos();
        $idPregunta = isset($_POST['id_pregunta']) ? intval($_POST['id_pregunta']) : 0;
        $this->preguntaModel->ignorarPregunta($idPregunta);
        header("Location: /Editor/verReportes");
        exit();
    }

    public function verEditarPregunta() {
        $this->verificarPermisos();
        $id = $_POST['id'] ?? null;
        $data['pregunta'] = $this->preguntaModel->getPregunta($id);

        $data['url_procesar'] = "/Editor/procesarEdicion";
        $this->renderer->render("editarPregunta", $data);
    }

    public function procesarEdicion() {
        $this->verificarPermisos();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
            header("Location: /Editor/mostrar");
            exit();
        }

        $idPregunta = intval($_POST['id']);
        $nuevoTextoPregunta = $_POST['texto'] ?? '';
        $idRespuestaCorrecta = intval($_POST['respuesta_correcta_id'] ?? 0);
        $respuestasData = $_POST['respuestas'] ?? [];

        $exito = $this->preguntaModel->updatePreguntaYRespuestas(
            $idPregunta, $nuevoTextoPregunta, $idRespuestaCorrecta, $respuestasData
        );

        if ($exito) {
            header("Location: /Editor/verReportes");
        } else {
            header("Location: /Editor/mostrar");
        }
        exit();
    }
}
