<?php

class LoginController
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

    public function mostrar()
    {
        $this->renderer->render("loginView");
    }

    public function procesar()
    {
        $username = $this->request->post("username");
        $password = $this->request->post("password");

        $usuario = $this->model->buscarUsuario($username);

        if (!$usuario) {
            Redirect::to("/login");
            return;
        }

        if (!password_verify($password, $usuario["password"])) {
            Redirect::to("/login");
            return;
        }

        if (!$usuario["validado"]) {
            Redirect::to("/login");
            return;
        }

        $_SESSION["id"] = $usuario["id"];
        $_SESSION["username"] = $usuario["username"];
        $_SESSION["rol"] = $usuario["rol"];

        Redirect::to("/lobby");
    }

    public function logout()
    {
        session_destroy();
        Redirect::to("/login");
    }
}