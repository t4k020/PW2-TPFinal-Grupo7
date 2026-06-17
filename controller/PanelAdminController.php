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

}