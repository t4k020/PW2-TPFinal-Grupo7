<?php
class RegisterModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function registrarUsuario($nombre, $fechaNac, $sexo, $pais, $ciudad, $mail, $username, $password, $foto_perfil)
    {



        $sql = "INSERT INTO usuario (nombreCompleto, anioNacimiento, sexo, pais, ciudad, mail, username, password, fotoPerfil) VALUES (?, ?, ?, ?,?,?,?,?,?)";
        //se encripta la password por seguridad
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        Log::info("SQL: $sql [$nombre, $fechaNac, $sexo, $pais, $ciudad, $mail, $username, $passwordHash, $foto_perfil]");


        return $this->database->execute($sql, [$nombre, $fechaNac, $sexo, $pais, $ciudad, $mail, $username, $passwordHash, $foto_perfil]);

    }

}
?>