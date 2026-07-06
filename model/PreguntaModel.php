<?php

class PreguntaModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }


    // Metodo para traer una pregunta específica con sus 4 respuestas
    public function getPregunta($id)
    {
        //Traigo los datos de la pregunta y su categoría
        $sqlPregunta = "SELECT p.id, p.texto, p.dificultad, p.estado, p.reportado, 
                           c.nombre AS categoria_nombre, 
                           c.color AS categoria_color
                        FROM Pregunta p 
                        JOIN Categoria c ON p.categoria_id = c.id 
                        WHERE p.id = " . intval($id);

        $resultadoPregunta = $this->database->query($sqlPregunta);

        // Si no existe la pregunta, salgo
        if (empty($resultadoPregunta)) {
            return null;
        }

        // Tomo el primer registro de la pregunta
        $pregunta = $resultadoPregunta[0];

        // Traigo las 4 respuestas asociadas a esa pregunta
        $sqlRespuestas = "SELECT id, texto, es_correcta FROM Respuesta WHERE pregunta_id = " . intval($id);
        $respuestas = $this->database->query($sqlRespuestas);

        //Meto el array de respuestas adentro del array de la pregunta
        $pregunta['respuestas'] = $respuestas;

        return $pregunta;
    }

    public function verificarRespuesta($idRespuesta)
    {
        // Consulta para traer el campo es_correcta
        $sql = "SELECT es_correcta FROM Respuesta WHERE id = " . intval($idRespuesta);

        $resultado = $this->database->query($sql);

        if (empty($resultado)) {
            return false;
        }
        // verifico qué contiene el campo es_correcta de la fila
        $fila = $resultado[0];

        // Si es 1 devuelve true, si es 0 devuelve false
        return $fila['es_correcta'] == 1;
    }

    public function getRandomPregunta($listaIgnorar = [], $maestriaJugador = 'Amateur', $idCategoria = null)
    {

        $stringIdsIgnorados = !empty($listaIgnorar) ? implode(',', array_map('intval', $listaIgnorar)) : '0';

        // Armo las dificultades permitidas según la maestría que calculó el controlador
        // Formateo el string para que SQL lo entienda adentro del IN: 'FACIL', 'MEDIO' o 'DIFICIL'
        if ($maestriaJugador === 'Aprendiz') {
            $dificultadesPermitidas = "('FACIL')";
        } elseif ($maestriaJugador === 'Maestro') {
            $dificultadesPermitidas = "('DIFICIL')";
        } else {
            // Si es 'Amateur' o si es un usuario nuevo (que por defecto le pusimos 'MEDIO' o 'Amateur')
            $dificultadesPermitidas = "('MEDIO')";
        }

        // Se arma el filtro dinámico de categoría
        $filtroCategoria = "";
        if ($idCategoria !== null) {
            $filtroCategoria = " AND categoria_id = " . intval($idCategoria);
        }

        $sql = "SELECT * FROM Pregunta 
            WHERE estado = 'APROBADA' 
            $filtroCategoria
            AND dificultad IN $dificultadesPermitidas 
            AND id NOT IN ($stringIdsIgnorados) 
            ORDER BY RAND() LIMIT 1";

        $resultado = $this->database->query($sql);

        if (empty($resultado)) {

            return null;
//            // Si no hay más preguntas que correspondan a su maestría abrimos el abanico a cualquier dificultad, pero manteniendo los ignorados
//            $sqlFallback = "SELECT * FROM Pregunta
//            WHERE estado = 'APROBADA'
//            $filtroCategoria
//            AND id NOT IN ($stringIdsIgnorados)
//            ORDER BY RAND() LIMIT 1";
//
//            $resultado = $this->database->query($sqlFallback);
//
//            // Si el jugador respondió tódo lo que le apareció
//            // Para no trabarlo, ignoramos la lista de respondidas y le dejamos repetir preguntas
//            if (empty($resultado)) {
//                Log::info("El usuario ya respondió todo. Reseteando lista de ignorados para que pueda seguir jugando.");
//
//                // Buscamos cualquier pregunta aprobada de su nivel, sin usar el NOT IN
//                $sqlVueltaAEmpezar = "SELECT * FROM Pregunta
//             WHERE estado = 'APROBADA'
//             $filtroCategoria
//             AND dificultad IN $dificultadesPermitidas
//             ORDER BY RAND() LIMIT 1";
//
//                $resultado = $this->database->query($sqlVueltaAEmpezar);
//
//                // Si aun así da vacío (cosa rarísima), tiramos la última opción sin importar dificultad ni categoría
//                if (empty($resultado)) {
//                    $sqlDesesperado = "SELECT * FROM Pregunta WHERE estado = 'APROBADA' ORDER BY RAND() LIMIT 1";
//                    $resultado = $this->database->query($sqlDesesperado);
//                }
//            }
        }

        $idAzar = $resultado[0]['id'];
        return $this->getPregunta($idAzar);
    }

    public function guardarReporte($idPregunta, $motivoReporte)
    {
        //  asegura que sea un numero
        $id = intval($idPregunta);

        // se arma el query
        $sql = "UPDATE Pregunta 
            SET reportado = '" . $motivoReporte . "' 
            WHERE id = " . $id;


        return $this->database->query($sql);
    }

    public function getPreguntasReportadas()
    {
        log::info("trayendo preguntas reportadas..");

        $sqlReporte = "SELECT p.id, 
                          p.texto, 
                          p.reportado, 
                          c.nombre, 
                          c.color
                        FROM Pregunta p
                        JOIN Categoria c ON p.categoria_id = c.id 
                        WHERE p.reportado IN ('Pregunta mal escrita', 'Respuesta equivocada') ";


        $resultadoReporte = $this->database->query($sqlReporte);

        return $resultadoReporte;
    }

    public function aprobarPregunta($id)
    {
        $id = intval($id);
        Log::info("Aprobando pregunta $id");
        $sql = "UPDATE Pregunta 
                 SET estado = 'APROBADA'
                 WHERE id = " . $id;
        $this->database->query($sql);
    }

    public function borrarPregunta($id)
    {

        log::info("borrando pregunta $id");
        $id = intval($id);
        $sql = "DELETE FROM Pregunta WHERE id = " . $id;
        $this->database->query($sql);
    }

    public function ignorarPregunta($id)
    {
        $id = intval($id);
        log::info("Ignorando pregunta $id");
        $sql = "update Pregunta 
                 set reportado = 'no reportado'
                 where id = " . $id;
        $this->database->query($sql);
    }

    public function updatePreguntaYRespuestas($idPregunta, $textoPregunta, $idRespuestaCorrecta, $respuestasData)
    {
        // Actualizar el texto de la Pregunta usando placeholders
        $sqlPregunta = "UPDATE Pregunta SET texto = ? WHERE id = ?";
        $paramsPregunta = [$textoPregunta, intval($idPregunta)];

        // Ejecutamos el cambio en la tabla Pregunta
        $this->database->execute($sqlPregunta, $paramsPregunta);

        // Recorremos las 4 respuestas con foreach para actualizarlas una por una
        foreach ($respuestasData as $idRespuesta => $datos) {
            $idRespuesta = intval($idRespuesta);
            $nuevoTextoRespuesta = $datos['texto'];

            // Evaluamos si el ID de esta respuesta coincide con el radio button seleccionado como correcto
            $esCorrecta = ($idRespuesta === intval($idRespuestaCorrecta)) ? 1 : 0;

            // Consulta preparada para la tabla Respuesta
            $sqlRespuesta = "UPDATE Respuesta SET texto = ?, es_correcta = ? WHERE id = ? AND pregunta_id = ?";
            $paramsRespuesta = [$nuevoTextoRespuesta, $esCorrecta, $idRespuesta, intval($idPregunta)];

            // Ejecutamos el update de esta respuesta de forma segura
            $this->database->execute($sqlRespuesta, $paramsRespuesta);
        }
        $this->ignorarPregunta($idPregunta);

        return true;
    }

    //estadisticas

    public function getTotalPreguntas($fechaDesde = null)
    {
        $sql = "SELECT COUNT(*) as total FROM Pregunta ";

        if ($fechaDesde) {
            $sql .= " where fechaCreacion >= '" . $fechaDesde . "'";
        }
        $resultado = $this->database->query($sql);

        if (!empty($resultado)) {
            return intval($resultado[0]['total']);
        }

        return 0;
    }

    public function getPreguntasGrafico()
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
            FROM Pregunta";

        $resultado = $this->database->query($sql);

        if (!empty($resultado)) {
            $row = $resultado[0];

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

    public function getCategorias()
    {
        $sql = "SELECT id, nombre, color FROM Categoria";
        $resultado = $this->database->query($sql);
        if (empty($resultado))
            return null;

        return $resultado;
    }

    public function guardarPreguntaYRespuestas($categoriaId, $pregunta, $respuestaCorrecta, $respuestas)
    {
        $sqlPregunta = "INSERT INTO Pregunta (categoria_id, texto) VALUES (?,?)";
        $this->database->execute($sqlPregunta, [$categoriaId, $pregunta]);

        $preguntaId = $this->database->lastInsertId();

        $sqlRespuesta = "INSERT INTO Respuesta (pregunta_id, texto, es_correcta) VALUES (?,?,?)";
        foreach ($respuestas as $indice => $textoRespuesta) {

            $esCorrecta = ($indice == $respuestaCorrecta) ? 1 : 0;

            $this->database->execute(
                $sqlRespuesta,
                [$preguntaId, $textoRespuesta, $esCorrecta]
            );
        }
    }

    public function getPreguntasSugeridas()
    {
        log::info("Obteniendo preguntas sugeridas..");

        $sqlPregunta = "SELECT p.id, 
                          p.texto, 
                          p.estado, 
                          c.nombre, 
                          c.color
                        FROM Pregunta p
                        JOIN Categoria c ON p.categoria_id = c.id 
                        WHERE p.estado IN ('PENDIENTE') ";


        $preguntas = $this->database->query($sqlPregunta);

        foreach ($preguntas as &$pregunta) {

            $sqlRespuestas = "SELECT id, texto, es_correcta
                          FROM Respuesta
                          WHERE pregunta_id = ?";

            $pregunta["respuestas"] =
                $this->database->query($sqlRespuestas, [$pregunta["id"]]);
        }

        return $preguntas;
    }

    public function getCategoriasDisponibles($listaIgnorar = [])
    {
        // Esto hace que no nos devuelva la misma categoría repetida por cada pregunta que tenga
        $sql = "SELECT DISTINCT c.id, c.nombre, c.color 
                FROM Categoria c
                JOIN Pregunta p ON c.id = p.categoria_id
                WHERE p.estado = 'APROBADA'";

        // Si el usuario ya respondió preguntas de manera correcta , filtramos para que no cuente esas
        if (!empty($listaIgnorar)) {
            $idsString = implode(',', array_map('intval', $listaIgnorar));
            $sql .= " AND p.id NOT IN (" . $idsString . ")";
        }

        $resultado = $this->database->query($sql);

        return $resultado ?: []; // Si no hay nada, devolvemos un array vacío por seguridad
    }

    // Métod para guardar el registro en la tabla intermedia
    public function guardarHistorialUsuario($usuarioId, $preguntaId, $fueCorrecta)
    {
        $sql = "INSERT INTO usuario_pregunta (usuario_id, pregunta_id, fue_correcta) VALUES (?, ?, ?)";
        $this->database->execute($sql, [$usuarioId, $preguntaId, $fueCorrecta]);
    }

    // Métod que Suma los contadores de la pregunta y recalcula su dificultad global
    public function actualizarEstadisticasYDificultad($preguntaId, $esCorrecta)
    {
        // Primero, sumamos los contadores
        $sumarCorrecta = $esCorrecta ? 1 : 0;
        $sqlCounters = "UPDATE Pregunta 
                    SET veces_mostrada = veces_mostrada + 1, 
                        veces_correcta = veces_correcta + ? 
                    WHERE id = ?";
        $this->database->execute($sqlCounters, [$sumarCorrecta, $preguntaId]);

        // Segundo, traemos los contadores actualizados para calcular el porcentaje
        $preguntaData = $this->getPregunta($preguntaId);
        $vistas = $preguntaData['veces_mostrada'] ?? 0;
        $correctas = $preguntaData['veces_correcta'] ?? 0;

        if ($vistas >= 10) {
            $porcentajeAciertoGlobal = ($correctas / $vistas) * 100;

            if ($porcentajeAciertoGlobal > 70) {
                $nuevaDificultad = 'FACIL';
            } elseif ($porcentajeAciertoGlobal < 30) {
                $nuevaDificultad = 'DIFICIL';
            } else {
                $nuevaDificultad = 'MEDIO';
            }

            // Guardamos la nueva dificultad de la pregunta
            $sqlUpdate = "UPDATE Pregunta SET dificultad = ? WHERE id = ?";
            $this->database->execute($sqlUpdate, [$nuevaDificultad, $preguntaId]);
        }
    }


}