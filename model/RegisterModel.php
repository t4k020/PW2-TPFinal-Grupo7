<?php
class RegisterModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function registrarUsuario($nombre, $fechaNac, $sexo, $pais, $ciudad, $mail, $username, $password, $foto_perfil, $token)
    {

        $sql = "INSERT INTO Usuario (nombreCompleto, anioNacimiento, sexo, pais, ciudad, mail, username, password, fotoPerfil, token) VALUES (?,?,?,?,?,?,?,?,?,?)";
        Log::info("SQL: $sql [$nombre, $fechaNac, $sexo, $pais, $ciudad, $mail, $username, $password, $foto_perfil, $token]");

        return $this->database->execute($sql, [$nombre, $fechaNac, $sexo, $pais, $ciudad, $mail, $username, $password, $foto_perfil, $token]);

    }

    public function buscarPorToken($token)
    {
        $sql = "SELECT * FROM Usuario WHERE token = ?";

        $filas = $this->database->query($sql, [$token]);
        $sql = "INSERT INTO usuario (nombreCompleto, anioNacimiento, sexo, pais, ciudad, mail, username, password, fotoPerfil) VALUES (?, ?, ?, ?,?,?,?,?,?)";
        //se encripta la password por seguridad
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        Log::info("SQL: $sql [$nombre, $fechaNac, $sexo, $pais, $ciudad, $mail, $username, $passwordHash, $foto_perfil]");

        return !empty($filas) ? $filas[0] : null;
    }

    public function activarUsuario($id)
    {
        $sql = "UPDATE Usuario
        SET validado = 1,
            token = NULL
        WHERE id = ?
    ";
        return $this->database->execute($sql, [$nombre, $fechaNac, $sexo, $pais, $ciudad, $mail, $username, $passwordHash, $foto_perfil]);

        $this->database->execute($sql, [$id]);
    }

}
?>