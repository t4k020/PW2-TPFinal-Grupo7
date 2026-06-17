<?php

class LobbyModel {
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function obtenerDatosUsuario($id)
    {
        $sql = "SELECT username, puntaje, trampitas, fotoPerfil FROM Usuario WHERE id = ?";
        $filas = $this->database->query($sql, [$id]);
        return !empty($filas) ? $filas[0] : null;
    }
}