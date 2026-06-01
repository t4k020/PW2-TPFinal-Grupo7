<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

$mail = new PHPMailer(true);
class RegisterController{
    private $model;
    private $renderer;
    private $request;

    public function __construct($model, $renderer, $request){
        $this->model = $model;
        $this->renderer = $renderer;
        $this->request = $request;

    }
    public function mostrar(){
        Log::info("registerController::mostrar");
        $this->renderer->render("registerView");
    }

    public function registrar(){
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



        if ($password != $repetirPassword){
            Log::warning("RegisterController::procesarAlta - la contraseña no es igual: $password");
            Redirect::toIndex();
            return;
        }

        if (isset($_FILES['fotoPerfil']) && $_FILES['fotoPerfil']['error'] === UPLOAD_ERR_OK) {

            $extension = pathinfo($_FILES['fotoPerfil']['name'], PATHINFO_EXTENSION);

            $foto_perfil = uniqid("perfil_") . "." . $extension;

            $rutaDestino = __DIR__ . "/../public/img/" . $foto_perfil;

            move_uploaded_file(
                $_FILES['fotoPerfil']['tmp_name'],
                $rutaDestino
            );
        }

        Log::info("registerController::procesarAlta - nombre=$nombre");
        $this->model->registrarUsuario($nombre, $fechaNac, $sexo, $pais, $ciudad, $mail, $username, $password, $foto_perfil ?? "default-user.png");
        Redirect::toIndex();
    }
}

?>