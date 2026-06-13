<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Configurator {

    private $config;

    public function __construct()
    {
        $this->config = parse_ini_file("config/config.ini");
    }

    private function getDatabase()
    {
        return new MyDatabase(
            $this->config['hostname'],
            $this->config['username'],
            $this->config['password'],
            $this->config['database']
        );
    }

    private function getRenderer()
    {
        return new MustacheRenderer(__DIR__ . '/../view');
    }

    //Lobby
    public function getLobbyController()
    {
        return new LobbyController($this->getLobbyModel(), $this->getRenderer(), new Request());
    }

    private function getLobbyModel()
    {
        return new LobbyModel($this->getDatabase());
    }

    public function getPanelAdminController()
    {
        return new PanelAdminController($this->getLobbyModel(),$this->getRenderer(), new Request());
    }

    //Login
    public function getLoginController()
    {
        return new LoginController($this->getLoginModel(), $this->getRenderer(), new Request());
    }

    private function getLoginModel()
    {
        return new LoginModel($this->getDatabase());
    }

    //Register
    public function getRegisterController()
    {
        return new RegisterController($this->getRegisterModel(), $this->getRenderer(), new Request(), $this->getMailer());
    }

    private function getRegisterModel()
    {
        return new RegisterModel($this->getDatabase());
    }

    public function getRouter()
    {
        return new Router($this, 'lobby', 'mostrar');
    }

    public function getOrDefault($controllerName, $defaultControllerName)
    {
        $getter = 'get' . ucfirst($controllerName) . 'Controller';
        if (method_exists($this, $getter)) {
            return $this->{$getter}();
        }
        $defaultGetter = 'get' . ucfirst($defaultControllerName) . 'Controller';
        return $this->{$defaultGetter}();
    }

    function getMailer(): PHPMailer
    {
        $mailer = new PHPMailer(true);

        //el mailer usara simple mail transfer protocol
        $mailer->isSMTP();
        $mailer->Host = 'smtp.gmail.com';

        $mailer->SMTPAuth = true;
        // email que enviara los correos
        $mailer->Username = 'pwtpg2712@gmail.com';
        //contraseña para que se pueda usar el email como un mailer, normalmente se tendria que ocultar esto
        $mailer->Password = 'ehrq zayo dsay wrlt';
        // encripta el mensaje para no ser interceptado por terceros
        $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mailer->Port = 587;

        $mailer->setFrom(
            'pwtpg2712@gmail.com',
            'Preguntados'
        );

        return $mailer;
    }

    public function getPreguntaModel() {
        return new PreguntaModel($this->getDatabase());
    }

    public function getPreguntaController() {
        return new PreguntaController(
            $this->getPreguntaModel(),
            $this->getRenderer(),
            new Request()
        );
    }


    public function getUsuarioModel() {
        return new UsuarioModel($this->getDatabase());
    }

    // Se agrega el servicio de Maestría inyectándole el modelo
    public function getMaestriaService() {
        return new MaestriaService($this->getUsuarioModel());
    }




}