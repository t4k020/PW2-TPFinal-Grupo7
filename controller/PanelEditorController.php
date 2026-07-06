<?php

class PanelEditorController {
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

        $this->renderer->render("panelEditorView", [
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
            "hubo_sugerencias" => !empty($preguntasSugeridas),
            "base_url" => "/PanelEditor",
            "nombre_panel" => "Panel Editor"
        ]);
    }

    public function verReportes() {
        $this->verificarPermisos();
        $preguntasReportadas = $this->preguntaModel->getPreguntasReportadas();

        $this->renderer->render("verPreguntasReportadas", [
            "lista_reportes" => $preguntasReportadas,
            "hubo_reportes" => !empty($preguntasReportadas),
            "base_url" => "/PanelEditor",
            "nombre_panel" => "Panel Editor"
        ]);
    }

    public function aprobarPregunta() {
        $this->verificarPermisos();
        $idPregunta = isset($_POST['id_pregunta']) ? intval($_POST['id_pregunta']) : 0;
        $this->preguntaModel->aprobarPregunta($idPregunta);
        header("Location: /PanelEditor/verSugeridas");
        exit();
    }

    public function eliminarPregunta() {
        $this->verificarPermisos();
        $idPregunta = isset($_POST['id_pregunta']) ? intval($_POST['id_pregunta']) : 0;
        $this->preguntaModel->borrarPregunta($idPregunta);
        // Capturamos la URL anterior (si por algún motivo falla, lo mandamos al panel principal)
        $urlOrigen = $_SERVER['HTTP_REFERER'] ?? '/PanelEditor/mostrar';

        // Redirigimos
        header("Location: " . $urlOrigen);
        exit();
    }

    public function ignorarPregunta() {
        $this->verificarPermisos();
        $idPregunta = isset($_POST['id_pregunta']) ? intval($_POST['id_pregunta']) : 0;
        $this->preguntaModel->ignorarPregunta($idPregunta);
        header("Location: /PanelEditor/verReportes");
        exit();
    }

    public function verEditarPregunta() {
        $this->verificarPermisos();

        // Primero busca en GET (URL), si no está, busca en POST (Formulario).
        $id = $_GET['id'] ?? $_POST['id'] ?? null;
        $data['pregunta'] = $this->preguntaModel->getPregunta($id);

        $data['url_procesar'] = "/PanelEditor/procesarEdicion";
        $this->renderer->render("editarPregunta", $data);
    }

    public function procesarEdicion() {
        $this->verificarPermisos();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
            header("Location: /PanelEditor/mostrar");
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
            Log::info("Se modificaron exitosamente los datos de la pregunta.");

            // Se atrapa el estado que mandó el formulario oculto
            $estadoPregunta = $_POST['estado_pregunta'] ?? '';

            // Se define la base de la URL según el rol
            $baseUrl = ($_SESSION['rol'] === 'ADMIN') ? '/PanelAdmin' : '/PanelEditor';

            // Se redirige como corresponde
            if ($estadoPregunta === 'PENDIENTE') {
                header("Location: " . $baseUrl . "/verSugeridas");
            } else {
                header("Location: " . $baseUrl . "/verReportes");
            }

        } else {
            Log::error("No se modificaron exitosamente.");
            $baseUrl = ($_SESSION['rol'] === 'ADMIN') ? '/PanelAdmin' : '/PanelEditor';
            header("Location: " . $baseUrl . "/mostrar");
        }
        exit();
    }
}
