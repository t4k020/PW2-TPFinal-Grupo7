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

    //Login
    public function getLoginController()
    {
        return new LoginController($this->getLoginModel(), $this->getRenderer(), new Request());
    }

    private function getLoginModel()
    {
        return new LoginModel($this->getDatabase());
    }

    //register
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

        $mailer->isSMTP();
        $mailer->Host = 'smtp.gmail.com';
        $mailer->SMTPAuth = true;
        $mailer->Username = 'pwtpg2712@gmail.com';
        $mailer->Password = 'ehrq zayo dsay wrlt';
        $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mailer->Port = 587;

        $mailer->setFrom(
            'pwtpg2712@gmail.com',
            'Preguntados'
        );

        return $mailer;
    }


}