<?php

class CategoriaModel {
    private $database;

    public function __construct($database)
    {$this->database = $database;}

    public function getCategoria($id) {
        $sql = "SELECT id, nombre, color FROM Categoria WHERE id = ?";
        $resultado = $this->database->query($sql,[$id]);
        if (empty($resultado))
            return null;

        return $resultado[0];
    }

    public function guardarCategoria($nombre, $color) {
        $sql = "INSERT INTO Categoria (nombre, color) VALUES (?,?)";
        return $this->database->query($sql, [$nombre, $color]);
    }

    public function updateCategoria($id, $nombre, $color) {
        $sql = "UPDATE Categoria SET nombre = ?, color = ? WHERE id = ?";
        return $this->database->query($sql, [$nombre, $color, $id]);
    }

    public function borrarCategoria($id) {
        $sql = "DELETE FROM Categoria WHERE id = ?";
        return $this->database->query($sql, [$id]);
    }
}