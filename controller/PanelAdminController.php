<?php
class PanelAdminController {
    private $model;
    private $renderer;
    private $request;

    private $preguntaModel;

    public function __construct($model, $renderer, $request, $preguntaModel)
    {
    $this->model = $model;
    $this->renderer = $renderer;
    $this->request = $request;
    $this->preguntaModel = $preguntaModel;
    }

    public function mostrar(){
        Log::info("panelAdminController::mostrar");

        if (!isset($_SESSION["id"])) {
            Redirect::to("/login");

        }
        $usuario = $this->model->obtenerDatosUsuario($_SESSION["id"]);

        $this->renderer->render("panelAdminView", [
            "usuario" => $usuario["username"],
            "puntaje" => $usuario["puntaje"],
            "trampitas" => $usuario["trampitas"],
            "esAdmin"   => ($usuario["username"] === "Admin")

        ]);

    }
    public  function verReportes()
    {
        $preguntasReportadas = $this->preguntaModel->getPreguntasReportadas();
        $datosVista = [
            "lista_reportes" => $preguntasReportadas,
            "hubo_reportes" => !empty($preguntasReportadas)
        ];
        $this->renderer->render("verPreguntasReportadas", $datosVista);
    }

    public function eliminarPregunta(){
        $idPregunta = isset($_POST['id_pregunta']) ? intval($_POST['id_pregunta']) : 0;
        $this->preguntaModel->borrarPregunta($idPregunta);
        $this->verReportes();
    }

    public function ignorarPregunta(){
        $idPregunta = isset($_POST['id_pregunta']) ? intval($_POST['id_pregunta']) : 0;
        $this->preguntaModel->ignorarPregunta($idPregunta);
        $this->verReportes();
    }

    public  function verEditarPregunta()
    {
        $id = $_GET['id'] ?? null;
        $data['pregunta'] = $this->preguntaModel->getPregunta($id);

        $this->renderer->render("editarPregunta", $data);
    }

    public function procesarEdicion()
    {
        // Validamos que vengan los datos mínimos obligatorios
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
            header("Location: /PanelAdmin/mostrar");
            exit();
        }

        $idPregunta = intval($_POST['id']);
        $nuevoTextoPregunta = $_POST['texto'] ?? '';
        $idRespuestaCorrecta = intval($_POST['respuesta_correcta_id'] ?? 0);
        $respuestasData = $_POST['respuestas'] ?? []; // Trae el array indexado [id => ['texto' => '...']]

        //Mandamos los datos al modelo
        $exito = $this->preguntaModel->updatePreguntaYRespuestas(
            $idPregunta,
            $nuevoTextoPregunta,
            $idRespuestaCorrecta,
            $respuestasData
        );

        // 3. Redirigimos según el resultado
        if ($exito) {
            // Podés agregar un mensaje de éxito si tenés variables de sesión
            log::info("se modificaron exitosamente");
            header("Location: /PanelAdmin/verReportes");
        } else {
            log::error("No se modificaron exitosamente ");
       }
        exit();
    }


}