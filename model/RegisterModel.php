<?php
class RegisterModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function registrarUsuario($nombre, $fechaNac, $sexo, $pais, $ciudad, $mail, $username, $password, $foto_perfil, $token, $qr)
    {

        $sql = "INSERT INTO Usuario (nombreCompleto, anioNacimiento, sexo, pais, ciudad, mail, username, password, fotoPerfil, token, qr) VALUES (?,?,?,?,?,?,?,?,?,?,?)";
        Log::info("SQL: $sql [$nombre, $fechaNac, $sexo, $pais, $ciudad, $mail, $username, $password, $foto_perfil, $token, $qr]");
        return $this->database->execute($sql, [$nombre, $fechaNac, $sexo, $pais, $ciudad, $mail, $username, $password, $foto_perfil, $token, $qr]);

    }

    public function buscarPorToken($token)
    {
        $sql = "SELECT * FROM Usuario WHERE token = ?";

        $filas = $this->database->query($sql, [$token]);
        $sql = "INSERT INTO Usuario (nombreCompleto, anioNacimiento, sexo, pais, ciudad, mail, username, password, fotoPerfil) VALUES (?, ?, ?, ?,?,?,?,?,?)";

        Log::info("SQL: $sql");

        return !empty($filas) ? $filas[0] : null;
    }

    public function activarUsuario($id)
    {
        Log::info("activando usuario $id..");
        $sql = "UPDATE Usuario
        SET validado = 1,
            token = NULL
        WHERE id = ?  ";

        $this->database->execute($sql, [$id]);
    }

}
?>