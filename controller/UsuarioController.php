<?php

class UsuarioController
{
    private $model;
    private $renderer;
    private $request;

    public function __construct($model, $renderer, $request)
    {
        $this->model = $model;
        $this->renderer = $renderer;
        $this->request = $request;
    }

    public function ver($username)
    {
        Log::info("UsuarioController::ver $username");

        $usuario = $this->model->getUsuario($username);
        if (!$usuario)
            throw new Exception("Usuario no encontrado");

        $this->renderer->render("verUsuarioView", ['Usuario' => $usuario]);
    }

    public function alta()
    {
        Log::info("UsuarioController::alta (form)");
        $this->renderer->render("formAltaUsuarioView");
    }

    // que se le permite editar al usuario?
//    public function editar()
//    {
//        $id = $this->request->get('id');
//
//        if (!is_numeric($id)) {
//            Log::warning("UsuarioController::editar - id invalido: $id");
//            Redirect::toIndex();
//            return;
//        }
//
//        $id = (int)$id;
//        Log::info("UsuarioController::editar - id=$id");
//        $this->renderer->render("formEditarUsuarioView", $this->model->getUsuario($id));
//    }
//
//    public function procesarEditar()
//    {
//        $id = $this->request->post('id');
//        $fuerza = $this->request->post('fuerza');
//
//        if (!is_numeric($id) || !is_numeric($fuerza)) {
//            Log::warning("VikingoController::procesarEditar - parametros invalidos id=$id fuerza=$fuerza");
//            Redirect::toIndex();
//            return;
//        }
//
//        $id = (int)$id;
//        $fuerza = (int)$fuerza;
//        $nombre = $this->request->post('nombre');
//        Log::info("VikingoController::procesarEditar - id=$id nombre=$nombre");
//        $this->model->editar($id, $nombre, $this->request->post('apodo'), $this->request->post('clan'), $fuerza);
//        Redirect::toIndex();
//    }

    public function eliminar()
    {
        $id = $this->request->get('id');

        if (!is_numeric($id)) {
            Log::warning("UsuarioController::eliminar -id invalido: $id");
            Redirect::toIndex();
            return;
        }

        $id = (int)$id;
        Log::info("UsuarioController::eliminar - id=$id");
        $this->model->eliminar($id);
        Redirect::toIndex();
    }
}
