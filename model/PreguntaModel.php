<?php

class PreguntaModel
{
    private $database;

    public function __construct($database){
        $this->database = $database;
    }

    // Cambio 1: cambio de nombre de las tablas preguntas, categorias y respuestas, linea 16,17 y 31
    // Metodo para traer una pregunta específica con sus 4 respuestas
    public function getPregunta($id) {
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

    //Cambio 2: cambio de nombre tabla respuestas
    public function verificarRespuesta($idRespuesta) {
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

    //Cambio 3: cambio de nombre tabla preguntas
    public function getRandomPregunta($listaIgnorar = []) {
        // Base de la query
        $sql = "SELECT id FROM Pregunta";

        // Si hay IDs para ignorar, los transformamos a un string separado por comas (ej: "1,4,5")
        // y modificamos la consulta SQL
        if (!empty($listaIgnorar)) {
            // implode junta los elementos de un array con el conector que le pidas
            $idsString = implode(',', array_map('intval', $listaIgnorar));
            $sql .= " WHERE id NOT IN (" . $idsString . ")";
        }

        // Le sumamos el orden aleatorio
        $sql .= " ORDER BY RAND() LIMIT 1";

        $resultado = $this->database->query($sql);

        if (empty($resultado)) {
            return null; // No quedan más preguntas disponibles que no hayan sido respondidas
        }

        $idAzar = $resultado[0]['id'];
        return $this->getPregunta($idAzar);
    }
    public function guardarReporte($idPregunta, $motivoReporte) {
        //  asegura que sea un numero
        $id = intval($idPregunta);

        // se arma el query
        $sql = "UPDATE Pregunta 
            SET reportado = '" . $motivoReporte . "' 
            WHERE id = " . $id;


        return $this->database->query($sql);
    }

    public function getPreguntasReportadas(){
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

    public function aprobarPregunta($id) {
        $id = intval($id);
        Log::info("Aprobando pregunta $id");
        $sql = "UPDATE Pregunta 
                 SET estado = 'APROBADA'
                 WHERE id = " . $id;
        $this->database->query($sql);
    }
    public function  borrarPregunta($id)
    {

        log::info("borrando pregunta $id");
        $id = intval($id);
        $sql = "DELETE FROM Pregunta WHERE id = " . $id;
         $this->database->query($sql);
    }

    public function ignorarPregunta($id){
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

    public function getCategorias() {
        $sql = "SELECT id, nombre, color FROM Categoria";
        $resultado = $this->database->query($sql);
        if (empty($resultado))
            return null;

        return $resultado;
    }

    public function guardarPreguntaYRespuestas($categoriaId, $pregunta, $respuestaCorrecta, $respuestas) {
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

    public function getPreguntasSugeridas(){
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
}