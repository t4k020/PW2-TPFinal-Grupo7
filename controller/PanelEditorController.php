<?php

class PanelEditorController
{
    private $preguntaModel;
    private $categoriaModel;
    private $usuarioModel;
    private $renderer;
    private $request;

    public function __construct($preguntaModel, $categoriaModel, $usuarioModel, $renderer, $request)
    {
        $this->preguntaModel = $preguntaModel;
        $this->usuarioModel = $usuarioModel;
        $this->renderer = $renderer;
        $this->request = $request;
        $this->categoriaModel = $categoriaModel;
    }

    public function mostrar()
    {
        Log::info("[PanelEditor] Mostrar Panel Editor");
        $usuario = $this->usuarioModel->getUsuario($_SESSION["username"]);//quiza seria mejor por id

        $this->renderer->render("panelEditorView", [
            "usuario" => $usuario["username"]
        ]);
    }

    public function verPreguntas()
    {
        Log::info("[Editor] Mostrar Preguntas");
        $preguntasSugeridas = $this->preguntaModel->getPreguntasAprobadas();

        $this->renderer->render("verTodasLasPreguntas", [
            "lista_preguntas" => $preguntasSugeridas,
            "hubo_preguntas" => !empty($preguntasSugeridas),
            "redirect" => "/verPreguntas"]);
    }

    public function verSugeridas()
    {
        Log::info("[Editor] Mostrar Preguntas Sugeridas");
        $preguntasSugeridas = $this->preguntaModel->getPreguntasSugeridas();

        $this->renderer->render("verPreguntasSugeridas", [
            "lista_sugeridas" => $preguntasSugeridas,
            "hubo_sugerencias" => !empty($preguntasSugeridas),
            "redirect" => "/verSugeridas"
        ]);
    }

    public function verReportes()
    {
        Log::info("[Editor] Mostrar Preguntas Reportadas");
        $preguntasReportadas = $this->preguntaModel->getPreguntasReportadas();

        $this->renderer->render("verPreguntasReportadas", [
            "lista_reportes" => $preguntasReportadas,
            "hubo_reportes" => !empty($preguntasReportadas),
            "redirect" => "/verReportes"
        ]);
    }

    public function crearPregunta()
    {
        Log::info("[Editor] Crear Pregunta");
        $redirect = $_POST["redirect"] ?? "/mostrar";
        $categorias = $this->categoriaModel->getCategorias();
        $this->renderer->render("crearPregunta",
            ["categorias" => $categorias,
                "opciones" => [1, 2, 3, 4],
                "redirect" => $redirect]);
    }

    public function aprobarPregunta()
    {
        Log::info("[Editor] Aprobar Pregunta");
        $idPregunta = isset($_POST['id_pregunta']) ? intval($_POST['id_pregunta']) : 0;
        $this->preguntaModel->aprobarPregunta($idPregunta);
        Redirect::to('/panelEditor/verSugeridas');
    }

    public function eliminarPregunta()
    {
        Log::info("[Editor] Eliminar Pregunta");
        $redirect = $_POST["redirect"] ?? "/mostrar";
        $idPregunta = isset($_POST['id_pregunta']) ? intval($_POST['id_pregunta']) : 0;
        $this->preguntaModel->borrarPregunta($idPregunta);
        Redirect::to("/panelEditor$redirect");
    }

    public function ignorarPregunta()
    {
        Log::info("[Editor] Ignorar Pregunta");
        $idPregunta = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $this->preguntaModel->ignorarPregunta($idPregunta);
        Redirect::to("/panelEditor/verReportes");
    }

    public function verEditarPregunta()
    {
        Log::info("[Editor] Editar Pregunta");
        $id = $_POST['id'] ?? null;
        $redirect = $_POST["redirect"] ?? "";
        $categorias = $this->categoriaModel->getCategorias();
        $pregunta = $this->preguntaModel->getPregunta($id);

        foreach ($categorias as &$categoria) {
            $categoria["seleccionada"] = ($categoria['id'] == $pregunta['categoria_id']);
        }
//        $data['url_procesar'] = "/PanelAdmin/procesarEdicion";
        $this->renderer->render("editarPregunta",
            ["pregunta" => $pregunta,
                "categorias" => $categorias,
                "redirect" => $redirect]);
    }

    public function procesarEdicion()
    {
        Log::info("[Editor] Procesando Edición");
        // Validamos que vengan los datos mínimos obligatorios
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id']))
            Redirect::to("/mostrar");

        $idPregunta = intval($_POST['id']);
        $redirect = $_POST["redirect"] ?? "";
        $categoriaId = intval($_POST["categoria_id"]) ?? 0;
        $nuevoTextoPregunta = $_POST['texto'] ?? '';
        $idRespuestaCorrecta = intval($_POST['respuesta_correcta_id'] ?? 0);
        $respuestasData = $_POST['respuestas'] ?? []; // Trae el array indexado [id => ['texto' => '...']]

        //Mandamos los datos al modelo
        $this->preguntaModel->updatePreguntaYRespuestas($idPregunta,
            $nuevoTextoPregunta, $categoriaId, $idRespuestaCorrecta, $respuestasData);

        Redirect::to("/panelEditor$redirect");
    }

    public function guardarCreacion()
    {
        Log::info("Guardando pregunta nueva...");
        $estado = "APROBADA";
        $categoriaId = $_POST['categoria_id'];
        $pregunta = $_POST['texto_pregunta'];
        $respuestaCorrecta = $_POST['respuesta_correcta'];
        $respuestas = $_POST['respuestas'];

        $this->preguntaModel->guardarPreguntaYRespuestas($categoriaId, $pregunta, $respuestaCorrecta, $respuestas, $estado);
            Redirect::to("/panelEditor/verPreguntas");
    }

    public function verCategorias()
    {
        $id = $_POST['id'] ?? null;
        $categoria = $this->categoriaModel->getCategoria($id);
        $categorias = $this->categoriaModel->getCategorias();
        $hubo_categorias = (isset($categorias) || $categorias > 0);

        $this->renderer->render("verCategorias",
            ["categorias" => $categorias,
                "categoria" => $categoria,
                "hubo_categorias" => $hubo_categorias]);
    }

    public function guardarCategoria()
    {
        $id = $_POST['id'] ?? null;
        $nombre = $_POST['nombre'];
        $color = $_POST['color'];

        if (!isset($id))
            $this->categoriaModel->guardarCategoria($nombre, $color);
        else {
            $id = intval($id);
            $this->categoriaModel->updateCategoria($id, $nombre, $color);
        }
        Redirect::to("/verCategorias");
    }

    public function eliminarCategoria()
    {
        $idCategoria = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $this->categoriaModel->borrarCategoria($idCategoria);
        Redirect::to("/verCategorias");
    }
}
