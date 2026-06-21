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
    // Que se le permite Editar al usuario?
//    public function editar($id, $nombre, $apodo, $clan, $fuerza)
//    {
//        $sql = "UPDATE guerreros SET nombre = ?, apodo = ?, clan = ?, fuerza = ? WHERE id = ?";
//        Log::info("SQL: $sql [$nombre, $apodo, $clan, $fuerza, $id]");
//        $this->database->execute($sql, [$nombre, $apodo, $clan, $fuerza, $id]);
//    }

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

    public function actualizarNivelMaestria($usuarioId) {

        // 1. Contamos cuántas partidas jugó en total este usuario
        $sqlPartidas = "SELECT COUNT(id) as total_partidas FROM Partida WHERE usuario_id = ?";
        $resultadoPartidas = $this->database->query($sqlPartidas, [$usuarioId]);
        $totalPartidas = $resultadoPartidas[0]['total_partidas'] ?? 0;

        // REGLA DE NEGOCIO: Si no tiene 10 partidas de posicionamiento, cortamos la ejecución acá.
        if ($totalPartidas < 10) {
            return;
        }

        // 2. Sumamos todos los puntos históricos (respuestas correctas)
        $sqlPuntos = "SELECT SUM(puntaje) as total_puntos FROM Partida WHERE usuario_id = ?";
        $resultadoPuntos = $this->database->query($sqlPuntos, [$usuarioId]);
        $totalAciertos = $resultadoPuntos[0]['total_puntos'] ?? 0;

        // 3. Calculamos el porcentaje de efectividad
        // Total de preguntas vistas = Aciertos + Errores (1 error por partida perdida)
        $totalPreguntasVistas = $totalAciertos + $totalPartidas;

        if ($totalPreguntasVistas == 0) {
            return; // Previene la división por cero por seguridad
        }

        $porcentajeAciertos = ($totalAciertos / $totalPreguntasVistas) * 100;

        // 4. Definimos los rangos (podés ajustar estos porcentajes a gusto del equipo)
        $nuevaMaestria = 'Amateur'; // Por defecto

        if ($porcentajeAciertos >= 80) {
            $nuevaMaestria = 'Leyenda';
        } elseif ($porcentajeAciertos >= 60) {
            $nuevaMaestria = 'Avanzado';
        } elseif ($porcentajeAciertos >= 40) {
            $nuevaMaestria = 'Intermedio';
        }

        // 5. Actualizamos el rango en la base de datos
        $sqlUpdate = "UPDATE Usuario SET maestria = ? WHERE id = ?";
        $this->database->execute($sqlUpdate, [$nuevaMaestria, $usuarioId]);
    }



}
