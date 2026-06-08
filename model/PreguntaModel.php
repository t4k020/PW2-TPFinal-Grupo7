<?php

class PreguntaModel
{
    private $database;

    public function __construct($database){
        $this->database = $database;
    }

    // Metodo para traer una pregunta específica con sus 4 respuestas
    public function getPregunta($id) {
        //Traigo los datos de la pregunta y su categoría
        $sqlPregunta = "SELECT p.id, p.texto, c.nombre AS categoria_nombre, c.color AS categoria_color 
                        FROM preguntas p 
                        JOIN categorias c ON p.categoria_id = c.id 
                        WHERE p.id = " . intval($id);

        $resultadoPregunta = $this->database->query($sqlPregunta);

        // Si no existe la pregunta, salgo
        if (empty($resultadoPregunta)) {
            return null;
        }

        // Tomo el primer registro de la pregunta
        $pregunta = $resultadoPregunta[0];

        // Traigo las 4 respuestas asociadas a esa pregunta
        $sqlRespuestas = "SELECT id, texto, es_correcta FROM respuestas WHERE pregunta_id = " . intval($id);
        $respuestas = $this->database->query($sqlRespuestas);

        //Meto el array de respuestas adentro del array de la pregunta
        $pregunta['respuestas'] = $respuestas;

        return $pregunta;
    }

    public function verificarRespuesta($idRespuesta) {
        // Consulta para traer el campo es_correcta
        $sql = "SELECT es_correcta FROM respuestas WHERE id = " . intval($idRespuesta);

        $resultado = $this->database->query($sql);

        if (empty($resultado)) {
            return false;
        }
        // verifico qué contiene el campo es_correcta de la fila
        $fila = $resultado[0];

        // Si es 1 devuelve true, si es 0 devuelve false
        return $fila['es_correcta'] == 1;
    }

    public function getRandomPregunta($listaIgnorar = []) {
        // Base de la query
        $sql = "SELECT id FROM preguntas";

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
}