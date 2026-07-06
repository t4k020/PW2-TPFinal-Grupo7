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

    public function actualizarNivelMaestria($usuarioId) {

        // 1. Contamos cuántas partidas jugó (esto lo podemos dejar igual para la regla de las 10 partidas)
        $sqlPartidas = "SELECT COUNT(id) as total_partidas FROM Partida WHERE usuario_id = ?";
        $resultadoPartidas = $this->database->query($sqlPartidas, [$usuarioId]);
        $totalPartidas = $resultadoPartidas[0]['total_partidas'] ?? 0;

        if ($totalPartidas < 10) {
            return; // No llegó a las 10 partidas de posicionamiento
        }

        // AGREGADO -> Buscamos el total de preguntas respondidas en la tabla intermedia
        $sqlTotal = "SELECT COUNT(*) as total FROM usuario_pregunta WHERE usuario_id = ?";
        $resTotal = $this->database->query($sqlTotal, [$usuarioId]);
        $totalPreguntasVistas = $resTotal[0]['total'] ?? 0;

        if ($totalPreguntasVistas == 0) return;

        // AGREGADO ->  Buscamos cuántas fueron correctas (fue_correcta = 1)
        $sqlCorrectas = "SELECT COUNT(*) as correctas FROM usuario_pregunta WHERE usuario_id = ? AND fue_correcta = 1";
        $resCorrectas = $this->database->query($sqlCorrectas, [$usuarioId]);
        $totalAciertos = $resCorrectas[0]['correctas'] ?? 0;

        // Se calcula el porcentaje real
        $porcentajeAciertos = ($totalAciertos / $totalPreguntasVistas) * 100;

        // 5. Asignamos los rangos del Usuario (Maestro, Amateur, Aprendiz)
        if ($porcentajeAciertos > 70) {
            $nuevaMaestria = 'Maestro';
        } elseif ($porcentajeAciertos < 30) {
            $nuevaMaestria = 'Aprendiz';
        } else {
            $nuevaMaestria = 'Amateur';
        }

        // Se actualiza el rango del usuario
        $sqlUpdate = "UPDATE Usuario SET maestria = ? WHERE id = ?";
        $this->database->execute($sqlUpdate, [$nuevaMaestria, $usuarioId]);
    }

// para estadisticas
    public function getTotalUsuarios($fechaDesde = null)
    {
        $sql = "SELECT COUNT(*) as total FROM Usuario ";
        if ($fechaDesde) {
            $sql .= " where fechaCreacion >= '" . $fechaDesde . "'";
        }
        $resultado = $this->database->query($sql);

        if (!empty($resultado)) {
            return intval($resultado[0]['total']);
        }

        return 0;
    }
    public function getUsuariosGrafico()
    {
        $hoy    = date('Y-m-d 00:00:00');
        $semana = date('Y-m-d H:i:s', strtotime('-7 days'));
        $mes    = date('Y-m-d H:i:s', strtotime('-30 days'));
        $anio   = date('Y-m-d H:i:s', strtotime('-1 year'));


        $sql = "SELECT 
                SUM(CASE WHEN fechaCreacion >= '{$hoy}' THEN 1 ELSE 0 END) as hoy,
                SUM(CASE WHEN fechaCreacion >= '{$semana}' THEN 1 ELSE 0 END) as semana,
                SUM(CASE WHEN fechaCreacion >= '{$mes}' THEN 1 ELSE 0 END) as mes,
                SUM(CASE WHEN fechaCreacion >= '{$anio}' THEN 1 ELSE 0 END) as anio,
                COUNT(*) as todo
            FROM Usuario";

        $resultado = $this->database->query($sql);

        if (!empty($resultado)) {
            $row = $resultado[0];

            // Retornamos la estructura para la gráfica
            return [
                ["periodo" => "Histórico", "cantidad" => intval($row['todo'])],
                ["periodo" => "Últ. Año", "cantidad" => intval($row['anio'])],
                ["periodo" => "Últ. Mes", "cantidad" => intval($row['mes'])],
                ["periodo" => "Últ. Semana", "cantidad" => intval($row['semana'])],
                ["periodo" => "Hoy", "cantidad" => intval($row['hoy'])]
            ];
        }

        return [];
    }

    public function getUsuariosPorPais($fechaDesde = null)
    {
        // base de la consulta y las condiciones iniciales
        $sql = "SELECT pais, COUNT(*) as cantidad 
            FROM Usuario 
            WHERE pais IS NOT NULL AND pais != ''";

        // Si hay fecha, la acoplamos inmediatamente al WHERE mediante un AND
        if ($fechaDesde) {
            $sql .= " AND fechaCreacion >= '" . $fechaDesde . "'";
        }

        //  agrupamos y ordenamos los resultados
        $sql .= " GROUP BY pais ORDER BY cantidad DESC";

        return $this->database->query($sql);
    }

    public function getUsuariosPorEdad($fechaDesde = null)
    {
        //  Iniciamos la consulta
        $sql = "SELECT 
                CASE 
                    WHEN TIMESTAMPDIFF(YEAR, anioNacimiento, CURDATE()) < 18 THEN 'Menores de 18'
                    WHEN TIMESTAMPDIFF(YEAR, anioNacimiento, CURDATE()) BETWEEN 18 AND 50 THEN 'Entre 18 y 50'
                    ELSE 'Mayores de 50 (Jubilados)'
                END as rango_edad,
                COUNT(*) as cantidad
            FROM Usuario
            WHERE anioNacimiento IS NOT NULL";

        //  Si viene el filtro de fecha, lo sumamos al WHERE
        if ($fechaDesde) {
            $sql .= " AND fechaCreacion >= '" . $fechaDesde . "'";
        }

        // 3. Cerramos la consulta
        $sql .= " GROUP BY rango_edad
              ORDER BY FIELD(rango_edad, 'Menores de 18', 'Entre 18 y 50', 'Mayores de 50 (Jubilados)')";

        return $this->database->query($sql);
    }

    public function getUsuariosPorSexo($fechaDesde = null)
    {
        // Iniciamos la consulta
        $sql = "SELECT sexo, COUNT(*) as cantidad 
            FROM Usuario ";

        //  Si hay filtro de fecha, lo sumamos al WHERE
        if ($fechaDesde) {
            $sql .= " where fechaCreacion >= '" . $fechaDesde . "'";
        }


        $sql .= " GROUP BY sexo 
              ORDER BY cantidad DESC";

        return $this->database->query($sql);
    }



}
