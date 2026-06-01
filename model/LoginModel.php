<?php

class LoginModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function buscarUsuario($username)
    {
        $sql = "SELECT * FROM usuario WHERE username = ?";

        Log::info("SQL: $sql [$username]");

        $filas = $this->database->query($sql, [$username]);

        return !empty($filas) ? $filas[0] : null;
    }
}