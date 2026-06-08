<?php

class PreguntaController {
    private $preguntaModel;
    private $renderer;
    private $request;


    public function __construct($preguntaModel, $renderer, $request) {
        $this->preguntaModel = $preguntaModel;
        $this->renderer = $renderer;
        $this->request = $request;
    }

    public function list() {
        //si el usuario nunca jugó se crea una lista nueva, a partir de la segunda vez que juega usa su lista creada
        if (!isset($_SESSION['preguntas_respondidas'])) {
            $_SESSION['preguntas_respondidas'] = [];
        }

        //Le pido al modelo una pregunta al azar, pero le paso la lista de IDs que no quiero
        $listaIgnorar = $_SESSION['preguntas_respondidas'];
        $preguntaCompleta = $this->preguntaModel->getRandomPregunta($listaIgnorar);

        // si ya no hay más preguntas que responder, salimos.
        if ($preguntaCompleta === null) {
            // Podés mandarlo a una pantalla de "¡Ganaste el juego completo!" o al lobby
            echo "<h1>¡Increíble! Respondiste absolutamente todas las preguntas disponibles. 🏆</h1>";
            echo "<a href='/index.php?controller=lobby&method=list'>Volver al Lobby</a>";
            exit();
        }

        // 4. Guardamos el ID de la pregunta actual en la sesión para saber cuál está respondiendo
        $_SESSION['pregunta_actual_id'] = $preguntaCompleta['id'];

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
            // ¡Punto para el jugador! Anotamos el ID de la pregunta actual para que no vuelva a salir
            if (isset($_SESSION['pregunta_actual_id'])) {
                $_SESSION['preguntas_respondidas'][] = $_SESSION['pregunta_actual_id'];
            }

            header("Location: /index.php?controller=pregunta&method=list");
            exit();
        } else {
            // ¡Perdió! Como la partida terminó, destruimos la lista de respondidas
            // para que en la próxima partida pueda volver a jugar todas las preguntas.
            unset($_SESSION['preguntas_respondidas']);
            unset($_SESSION['pregunta_actual_id']);

            $this->renderer->render("gameover");
            exit();
        }
    }
}
