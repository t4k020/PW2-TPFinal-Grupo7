<?php

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
        return new LobbyController($this->getLobbyModel(), $this->getPartidaModel() ,$this->getRenderer(), new Request());
    }

    private function getLobbyModel()
    {
        return new LobbyModel($this->getDatabase());
    }

    public function getPanelAdminController()
    {
        return new PanelAdminController($this->getLobbyModel(),$this->getRenderer(), new Request(), $this->getPreguntaModel()
                                        , $this->getUsuarioModel(), $this->getPartidaModel());
    }

    public function getEditorController()
    {
        return new EditorController($this->getLobbyModel(),$this->getRenderer(), new Request(), $this->getPreguntaModel()
            , $this->getUsuarioModel(), $this->getPartidaModel(), $this->getCategoriaModel());
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
        return new RegisterController($this->getRegisterModel(), $this->getRenderer(),
            new Request());
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

    public function getPreguntaController() {
        return new PreguntaController(
            $this->getPreguntaModel(),
            $this->getPartidaModel(),
            $this->getRenderer(),
            new Request(),
            $this->getUsuarioModel()
        );
    }

    public function getPreguntaModel() {
        return new PreguntaModel($this->getDatabase());
    }

    public function getPartidaModel() {
        return new PartidaModel($this->getDatabase());
    }

    public function getUsuarioController()
    {
        return new UsuarioController($this->getUsuarioModel(), $this->getRenderer(), new Request(), $this->getPartidaModel());
    }

    public function getUsuarioModel() {
        return new UsuarioModel($this->getDatabase());
    }

    public function getCategoriaModel() {
        return new CategoriaModel($this->getDatabase());
    }




}