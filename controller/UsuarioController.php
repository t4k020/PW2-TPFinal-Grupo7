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

    public function ver()
    {
        Log::info("UsuarioController::ver");

        $this->renderer->render("verUsuarioView", ['Usuarios' => $this->model->getUsuarios()]);
    }

    public function alta()
    {
        Log::info("UsuarioController::alta (form)");
        $this->renderer->render("formAltaUsuarioView");
    }

    public function procesarAlta()
    {
        $nombre = $this->request->post('nombre');
        $fechaNac = $this->request->post('fechaNac');
        $sexo = $this->request->post('sexo');
        $pais = $this->request->post('pais');
        $ciudad = $this->request->post('ciudad');
        $mail = $this->request->post('mail');
        $username = $this->request->post('username');
        $password = $this->request->post('password');
        $foto_perfil = $this->request->post('foto_perfil');




        Log::info("UsuarioController::procesarAlta - nombre=$nombre");
        $this->model->alta($nombre, $fechaNac, $sexo, $pais, $ciudad, $mail, $username, $password, $foto_perfil);
        Redirect::toIndex();
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
