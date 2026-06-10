<?php
class PanelAdminController {
    private $model;
    private $renderer;
    private $request;

    public function __construct($model, $renderer, $request)
    {
    $this->model = $model;
    $this->renderer = $renderer;
    $this->request = $request;
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
}