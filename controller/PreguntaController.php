<?php

class PreguntaController {
    private $preguntaModel;
    private $renderer;
    private $request;
    private $partidaModel;
    // private $maestriaService; Maestria de usuario


    public function __construct($preguntaModel, $partidaModel ,$renderer, $request) {
        $this->preguntaModel = $preguntaModel;
        $this->partidaModel = $partidaModel;
        $this->renderer = $renderer;
        $this->request = $request;
    }

    public function list() {
        //si el usuario nunca jugó se crea una lista nueva, a partir de la segunda vez que juega usa su lista creada
        if (!isset($_SESSION['preguntas_respondidas'])) {
            $_SESSION['preguntas_respondidas'] = [];
            $_SESSION['partida_puntaje'] = 0;
        }

        // con este if evitamos lo que mencionó el profe, si el usuario recarga la pregunta, se va a mostrar siempre la misma
        if (isset($_SESSION['pregunta_actual_id'])) {
            $idPreguntaEnCurso = $_SESSION['pregunta_actual_id'];
            $preguntaCompleta = $this->preguntaModel->getPregunta($idPreguntaEnCurso);
        } else {
            // si viene del lobby o de responder bien:
            $listaIgnorar = $_SESSION['preguntas_respondidas'];
            $preguntaCompleta = $this->preguntaModel->getRandomPregunta($listaIgnorar);

            if ($preguntaCompleta !== null) {
                // guardo el id de le nueva pregunta
                $_SESSION['pregunta_actual_id'] = $preguntaCompleta['id'];
            }
        }

        // si ya no hay más preguntas que responder, salimos.
        if ($preguntaCompleta === null) {
            echo "<h1>¡Increíble! Respondiste absolutamente todas las preguntas disponibles. 🏆</h1>";
            echo "<a href='/index.php?controller=lobby&method=list'>Volver al Lobby</a>";
            exit();
        }

        // se mezclan las respuestas, para que no esté siempre en la misma posición
        $respuestasAMezclar = $preguntaCompleta['respuestas'];
        shuffle($respuestasAMezclar);
        $preguntaCompleta['respuestas'] = $respuestasAMezclar;

        $this->renderer->render("pregunta", $preguntaCompleta);
    }

    public function evaluar() {
        $idRespuesta = intval($this->request->get('id_respuesta'));

        //si el usuario borra o ingresa texto en la url, el intval le va a poner =0
        if ($idRespuesta === 0) {
            header("Location: /index.php?controller=lobby&method=list");
            exit();
        }

        $esCorrecta = $this->preguntaModel->verificarRespuesta($idRespuesta);

        if ($esCorrecta) {
            // si la respuesta es correcta, anota el id de la pregunta actual para que no la vuelva a mostrar
            if (isset($_SESSION['pregunta_actual_id'])) {
                $_SESSION['preguntas_respondidas'][] = $_SESSION['pregunta_actual_id'];
            }
            $_SESSION['partida_puntaje']++; //si el usuario responde bien, le sumamos un punto.
            unset($_SESSION['pregunta_actual_id']);
            header("Location: /index.php?controller=pregunta&method=list");
            exit();
        } else {
            // agarro el ID del usuario logueado.
            $usuarioId = $_SESSION['id'];

            // tomo el puntaje acumulado de la sesión, si por alguna razón no existe, le asignamos 0 por defecto.
            $puntajeFinal = isset($_SESSION['partida_puntaje']) ? $_SESSION['partida_puntaje'] : 0;

            // uso el metodo de PartidaModel para que guarde tod o de forma segura
            $this->partidaModel->guardarPartida($usuarioId, $puntajeFinal);

            // si el usuario pierde se destruye la lista de p respondidas para que en la prox partida pueda ver todas las preguntas
            //  y se resetea la partida
            unset($_SESSION['preguntas_respondidas']);
            unset($_SESSION['pregunta_actual_id']);
            unset($_SESSION['partida_puntaje']);

            $this->renderer->render("gameover");
            exit();
        }
    }
}
