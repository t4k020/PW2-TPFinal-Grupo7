<?php

class PreguntaController {
    private $preguntaModel;
    private $renderer;
    private $request;
    private $partidaModel;
    private $usuarioModel;


    public function __construct($preguntaModel, $partidaModel ,$renderer, $request, $usuarioModel) {
        $this->preguntaModel = $preguntaModel;
        $this->partidaModel = $partidaModel;
        $this->usuarioModel = $usuarioModel;
        $this->renderer = $renderer;
        $this->request = $request;
    }

    public function list() {
        Log::info("mostrando preguntas");

        //para resetear cuando haga click en "jugar de nuevo"
        $forzarReinicio = ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reiniciar_partida']));

        // O si viene navegando fresco desde el Lobby (limpieza por URL si es una nueva partida limpia)
        $vieneDelLobby = (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'controller=lobby') !== false);

        if ($forzarReinicio || ($vieneDelLobby && !isset($_SESSION['pregunta_actual_id']))) {
            unset($_SESSION['preguntas_respondidas']);
            unset($_SESSION['partida_puntaje']);
            unset($_SESSION['pregunta_actual_id']);
        }
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

            $puntajeTotal = isset($_SESSION['partida_puntaje']) ? $_SESSION['partida_puntaje'] : 0;
            $idUsuario = $_SESSION['id'];

            $this->partidaModel->guardarPartida($idUsuario, $puntajeTotal);

            // NUEVO: Verificamos si el usuario sube de rango después de su "partida perfecta"
            $this->usuarioModel->actualizarMaestria($idUsuario);

            $_SESSION['partida_puntaje'] = 0;
            unset($_SESSION['preguntas_respondidas']);
            unset($_SESSION['pregunta_actual_id']);

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
        Log::info("evaluando pregunta");
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
            //se guarda la pregunta mal respondida para el reporte
            $_SESSION['preguntas_respondidas'][] = $_SESSION['pregunta_actual_id'];
            //se guardan las ids en local para mostrarlas en gameover
            $idsRespondidos = $_SESSION['preguntas_respondidas'] ?? [];
            $preguntasRespondidasDetalle = [];


            if (!empty($idsRespondidos)) {

                foreach ($idsRespondidos as $idPregunta) {
                    // Traer los datos de cada pregunta
                    $preguntasRespondidasDetalle[] = $this->preguntaModel->getPregunta($idPregunta);
                }
            }



            // agarro el ID del usuario logueado.
            $usuarioId = $_SESSION['id'];

            // tomo el puntaje acumulado de la sesión, si por alguna razón no existe, le asignamos 0 por defecto.
            $puntajeFinal = isset($_SESSION['partida_puntaje']) ? $_SESSION['partida_puntaje'] : 0;

            // uso el metodo de PartidaModel para que guarde tod o de forma segura
            $this->partidaModel->guardarPartida($usuarioId, $puntajeFinal);

            // NUEVO: Verificamos si el usuario sube de rango después de esta partida
            $this->usuarioModel->actualizarMaestria($usuarioId);

            // si el usuario pierde se destruye la lista de p respondidas para que en la prox partida pueda ver todas las preguntas
            //  y se resetea la partida
//            unset($_SESSION['preguntas_respondidas']);
              unset($_SESSION['pregunta_actual_id']);
              unset($_SESSION['partida_puntaje']);
            $datosVista = [
                //nombre que se usan en moustache => contenido
                "historial_preguntas" => $preguntasRespondidasDetalle,
                "hubo_respuestas" => !empty($preguntasRespondidasDetalle) // Un booleano útil para Mustache
            ];

            $this->renderer->render("gameover", $datosVista); //controller=pregunta&method=evaluar&id_respuesta=19
            exit();
        }
    }
    public function iniciarReporte(){
        $origen = $_POST['origen'] ?? 'gameover';
        Log::info("iniciando reporte");
        //  Procesamos el reporte si viene por POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_pregunta_reportada'])) {
            $idReportada = intval($_POST['id_pregunta_reportada']);
            $motivoReporte = $_POST['motivo_reporte'] ?? null;

            if ($idReportada > 0 && !empty($motivoReporte)) {
                // Guardamos el reporte en la DB
                $this->preguntaModel->guardarReporte($idReportada, $motivoReporte);
            }
        }

        // 3. Redirección limpia para la lista de preguntas
        if ($origen === 'lista_preguntas') {
            // $_SERVER['HTTP_REFERER'] tiene la URL de la lista con sus filtros/paginado intactos
            $urlOrigen = $_SERVER['HTTP_REFERER'] ?? '/index.php?controller=pregunta&method=list';

            header("Location: " . $urlOrigen);
            exit();
        }

        // para que vuelva al gameover y pueda ver si quiere reportar  mas preguntas
        $idsRespondidos = $_SESSION['preguntas_respondidas'] ?? [];
        $preguntasRespondidasDetalle = [];

        if (!empty($idsRespondidos)) {
            foreach ($idsRespondidos as $idPregunta) {
                $preguntaData = $this->preguntaModel->getPregunta($idPregunta);
                if ($preguntaData) {
                    $preguntasRespondidasDetalle[] = $preguntaData;
                }
            }
        }

        $datosVista = [
            "historial_preguntas" => $preguntasRespondidasDetalle,
            "hubo_respuestas" => !empty($preguntasRespondidasDetalle)
        ];

        $this->renderer->render("gameover", $datosVista);
        exit();
    }

}
