<?php

class UsuarioModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function getUsuarios()
    {
        $sql = "SELECT * FROM Usuario";
        Log::info("SQL: $sql");
        return $this->database->query($sql);
    }

    public function getUsuario($username)
    {
        $sql = "SELECT * FROM Usuario WHERE username = ?";
        Log::info("SQL: $sql [$username]");
        $filas = $this->database->query($sql, [$username]);
        return !empty($filas) ? $filas[0] : null;
    }

    public function alta($nombre, $fechaNac, $sexo, $pais, $ciudad, $mail, $username, $password, $foto_perfil)
    {
        $sql = "INSERT INTO Usuario (nombreCompleto, anioNacimiento, sexo, pais, ciudad, mail, username, password, fotoPerfil) VALUES (?, ?, ?, ?,?,?,?,?,?)";
        Log::info("SQL: $sql [$nombre, $fechaNac, $sexo, $pais, $ciudad, $mail, $username, $password, $foto_perfil]");
        return $this->database->execute($sql, [$nombre, $fechaNac, $sexo, $pais, $ciudad, $mail, $username, $password, $foto_perfil]);
    }


    public function eliminar($id)
    {
        $sql = "DELETE FROM Usuario WHERE id = ?";
        Log::info("SQL: $sql [$id]");
        $this->database->execute($sql, [$id]);
    }




// Maestria de usuario

    // 1. Sirve para saber cuántas respondió y cuántas acertó el usuario
    public function obtenerEstadisticasJugador($idUsuario) {
        // ATENCIÓN: Esta consulta depende de cómo guarden ustedes el historial.
        // Suponiendo que tienen una tabla donde guardan cada respuesta de la partida:
        $sql = "SELECT 
                    COUNT(*) as total_respondidas, 
                    SUM(acerto) as total_correctas 
                FROM Historial_respuestas 
                WHERE id_usuario = ?";

        $resultado = $this->database->query($sql, [$idUsuario]);
        return $resultado[0];
    }

    // 2. Sirve para poder actualizar el nivel del usuario
    public function actualizarNivelMaestria($idUsuario, $nuevaMaestria) {
        $sql = "UPDATE Usuario SET maestria = ? WHERE id = ?";
        $this->database->execute($sql, [$nuevaMaestria, $idUsuario]);
    }


// para estadisticas
    public function getTotalUsuarios()
    {
        $sql = "SELECT COUNT(*) as total FROM Usuario";
        $resultado = $this->database->query($sql);

        if (!empty($resultado)) {
            return intval($resultado[0]['total']);
        }

        return 0;
    }

}
