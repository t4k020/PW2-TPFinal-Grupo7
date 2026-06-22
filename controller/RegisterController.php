<?php

class RegisterController{
    private $model;
    private $renderer;
    private $request;
    private $config;

    public function __construct($model, $renderer, $request){
        $this->model = $model;
        $this->renderer = $renderer;
        $this->request = $request;
        $this->config = parse_ini_file("config/config.ini");
    }
    public function mostrar(){
        Log::info("registerController::mostrar");
        $this->renderer->render("registerView");
    }

    public function registrar(){
        $url_base = $this->config["url_base"];
        $mail_user = $this->config["mail_user"];
        $mail_password = $this->config["mail_password"];

        Log::info("registerController::registrar");
        $nombre = $this->request->post('nombreCompleto');
        $fechaNac = $this->request->post('anioNacimiento') . '-01-01' ;
        $sexo = $this->request->post('sexo');
        $pais = $this->request->post('pais');
        $ciudad = $this->request->post('ciudad');
        $mail = $this->request->post('email');
        $username = $this->request->post('username');
        $password = $this->request->post('password');
        $repetirPassword = $this->request->post('repetirPassword');
        $foto_perfil = $this->request->post('foto_perfil') ?? "default-user.png";
        $qr = QrGenerator::crearQrUsuario($username, $url_base);

        if ($password != $repetirPassword){
            Log::warning("RegisterController::procesarAlta - la contraseña no es igual");
            Redirect::toIndex();
            return;
        }

        $token = bin2hex(random_bytes(32));
        $password = password_hash($password, PASSWORD_DEFAULT);
        $link = "$url_base/register/validar?token=$token";

        if (isset($_FILES['fotoPerfil']) && $_FILES['fotoPerfil']['error'] === UPLOAD_ERR_OK) {

            $extension = pathinfo($_FILES['fotoPerfil']['name'], PATHINFO_EXTENSION);

            $foto_perfil = uniqid("perfil_") . "." . $extension;

            $rutaDestino = __DIR__ . "/../public/img/" . $foto_perfil;

            move_uploaded_file(
                $_FILES['fotoPerfil']['tmp_name'],
                $rutaDestino
            );
        }

        $enviado = Mailer::enviarValidacion ($mail, $link, $mail_user, $mail_password);
        !$enviado && Redirect::toIndex();

        Log::info("registerController::procesarAlta - nombre=$nombre");
        $this->model->registrarUsuario($nombre, $fechaNac, $sexo, $pais, $ciudad, $mail,
            $username, $password, $foto_perfil ?? "default-user.png",$token, $qr);

        Redirect::toIndex();
    }

    public function validar()
    {
        $token = $this->request->get("token");

        if (!$token) {
            Redirect::to("/login");
            return;
        }

        $usuario = $this->model->buscarPorToken($token);

        if (!$usuario) {
            Log::warning("Token inválido: $token");
            Redirect::to("/login");
            return;
        }

        $this->model->activarUsuario($usuario["id"]);

        Log::info("Usuario activado: " . $usuario["username"]);

        Redirect::to("/login");
    }
}

