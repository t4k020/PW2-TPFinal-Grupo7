<?php

class PartidaModel{

    private $database;

    public function __construct($database){
        $this->database = $database;
    }

    public function guardarPartida($usuarioId, $puntaje) {

        // el signo de pregunta '?' como placeholder para proteger la base de datos
        // el NOW() para que MySQL guarde la fecha y hora exacta de este milisegundo.
        $sql = "INSERT INTO Partida (usuario_id, puntaje, fecha_partida) VALUES (?, ?, NOW())";

        // se guardan los parametros en un array
        $params = [$usuarioId, $puntaje];


        // usamos el metodo execute() para asegurar la bdd.
        return $this->database->execute($sql, $params);
    }

    public function getPuntajeTotalUsuario($usuarioId) {
        // consulta para sumar todos los puntajes de las partidas de este usuario
        $sql = "SELECT SUM(puntaje) as puntaje_total FROM Partida WHERE usuario_id = ?";

        $params = [$usuarioId];

        // Como es un SELECT que devuelve datos, usamos el metodo query()
        $resultado = $this->database->query($sql, $params);

        // Si la base de datos nos devolvió algo y el valor no es nulo, mandamos el número.
        // Si el usuario es nuevo y nunca jugó, SUM(puntaje) da NULL, entonces devolvemos 0.
        if (!empty($resultado) && isset($resultado[0]['puntaje_total'])) {
            return intval($resultado[0]['puntaje_total']);
        }

        return 0;
    }

    public function getRankingGeneral() {
        $sql = "SELECT U.username, U.fotoPerfil, SUM(P.puntaje) as puntaje_total 
            FROM Partida P
            INNER JOIN Usuario U ON P.usuario_id = U.id
            GROUP BY U.id
            ORDER BY puntaje_total DESC";

        return $this->database->query($sql);
    }

    // estadisticas
    public function getTotalPartidas($fechaDesde= null)
    {
        $sql = "SELECT COUNT(*) as total FROM Partida ";
        if ($fechaDesde) {
            $sql .= " where fechaCreacion >= '" . $fechaDesde . "'";
        }
        $resultado = $this->database->query($sql);

        if (!empty($resultado)) {
            return intval($resultado[0]['total']);
        }

        return 0;
    }

    public function getPorcentajeAciertoGlobal($fechaDesde= null)
    {
        $sql = "SELECT 
                SUM(puntaje) as total_correctas,
                SUM(puntaje + 1) as total_preguntas
            FROM Partida ";
        if ($fechaDesde) {
            $sql .= " where fechaCreacion >= '" . $fechaDesde . "'";
        }

        $resultado = $this->database->query($sql);

        if (!empty($resultado) && $resultado[0]['total_preguntas'] > 0) {
            $correctas = $resultado[0]['total_correctas'];
            $totales = $resultado[0]['total_preguntas'];

            // Calculamos el porcentaje general redondeado
            return ROUND(($correctas / $totales) * 100, 2);
        }

        return 0; // Si no hay partidas jugadas, da 0%
    }

    public function getPartidaConMejorPuntajePorUsuario($usuarioId) {

        $sql = "SELECT puntaje, fecha_partida
            FROM Partida
            WHERE usuario_id = ?
            ORDER BY puntaje DESC
            LIMIT 1";

        $params = [$usuarioId];

        $resultado = $this->database->query($sql, $params);

        if (!empty($resultado)) {
            return $resultado[0];
        }

        return null;
    }

    public function getPartidasPorUsuario($usuarioId)
    {
        $sql = "SELECT 
                id,
                usuario_id,
                puntaje,
                fecha_partida
            FROM Partida
            WHERE usuario_id = ?
            ORDER BY puntaje DESC";

        $params = [$usuarioId];

        $resultado = $this->database->query($sql, $params);

        if (!empty($resultado)) {
            return $resultado;
        }

        return [];
    }
}