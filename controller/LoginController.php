<?php

class LoginController
{
    private $usuarioModel;
    private $renderer;
    private $request;

    public function __construct($usuarioModel, $renderer, $request)
    {
        $this->usuarioModel = $usuarioModel;
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

        $usuario = $this->usuarioModel->getUsuario($username);

        if (!$usuario) {
            Log::error("no existe usuario");
            Redirect::to("/login");

            return;
        }

        if (!password_verify($password, $usuario["password"])) {
            Log::error("contraseña invalido");
            Redirect::to("/login");

            return;
        }

        if (!$usuario["validado"]) {
            Log::error("no esta validado");
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