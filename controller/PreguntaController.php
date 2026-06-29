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

        if ($forzarReinicio) {
            unset($_SESSION['preguntas_respondidas']);
            unset($_SESSION['partida_puntaje']);
            unset($_SESSION['pregunta_actual_id']);
        }

        // Atrapamos la categoría de la URL
        $idCategoria = isset($_GET['categoria']) ? intval($_GET['categoria']) : null;

        // CONTROL DE ACCESO: Si no hay pregunta en curso Y tampoco viene una categoría de la ruleta, al Lobby.
        if (!isset($_SESSION['pregunta_actual_id']) && $idCategoria === null) {
            header("Location: /Lobby");
            exit();
        }

        // Si el usuario nunca jugó o se acaba de resetear, se crea la lista limpia
        if (!isset($_SESSION['preguntas_respondidas'])) {
            $_SESSION['preguntas_respondidas'] = [];
            $_SESSION['partida_puntaje'] = 0;
        }

        // con este if evitamos lo que mencionó el profe, si el usuario recarga la pregunta, se va a mostrar siempre la misma
        if (isset($_SESSION['pregunta_actual_id'])) {
            $idPreguntaEnCurso = $_SESSION['pregunta_actual_id'];
            $preguntaCompleta = $this->preguntaModel->getPregunta($idPreguntaEnCurso);
        } else {
            // Si viene de la ruleta o de responder bien:
            $listaIgnorar = $_SESSION['preguntas_respondidas'];

            // Buscamos el username de la sesión (asegurate de usar la clave exacta que guardan al loguearse, ej: 'username' o 'usuario')
            $username = $_SESSION['username'] ?? null;
            $usuarioDatos = $this->usuarioModel->getUsuario($username);

            // Si encontramos al usuario, usamos su maestría real de la BD. Si no, fallback a 'Amateur'
            $maestriaJugador = $usuarioDatos['maestria'] ?? 'Amateur';

            $preguntaCompleta = $this->preguntaModel->getRandomPregunta($listaIgnorar, $maestriaJugador, $idCategoria);
            if ($preguntaCompleta !== null) {
                $_SESSION['pregunta_actual_id'] = $preguntaCompleta['id'];
            }
        }

        // Si ya no hay más preguntas disponibles en esta categoría/nivel (Partida Perfecta)
        if ($preguntaCompleta === null) {
            $puntajeTotal = isset($_SESSION['partida_puntaje']) ? $_SESSION['partida_puntaje'] : 0;
            $idUsuario = isset($_SESSION['id']) ? $_SESSION['id'] : null;

            if ($idUsuario) {
                $this->partidaModel->guardarPartida($idUsuario, $puntajeTotal);
                $this->usuarioModel->actualizarNivelMaestria($idUsuario);
            }

            // Limpiamos la sesión de juego
            unset($_SESSION['partida_puntaje']);
            unset($_SESSION['preguntas_respondidas']);
            unset($_SESSION['pregunta_actual_id']);

            $data['mensaje_victoria'] = "¡Increíble! Respondiste absolutamente todas las preguntas disponibles. 🏆";
            $this->renderer->render("partida_perfecta", $data);
            return;
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

        if ($idRespuesta === 0) {
            header("Location: /Lobby");
            exit();
        }

        $usuarioId = $_SESSION['id'];
        $idPreguntaActual = $_SESSION['pregunta_actual_id'] ?? null;

        $esCorrecta = $this->preguntaModel->verificarRespuesta($idRespuesta);

        if ($esCorrecta) {
            // --- TABLA INTERMEDIA Y ESTADÍSTICAS ---
            if ($idPreguntaActual) {
                $_SESSION['preguntas_respondidas'][] = $idPreguntaActual;
                // Guardamos en la tabla intermedia (1 = correcta)
                $this->preguntaModel->guardarHistorialUsuario($usuarioId, $idPreguntaActual, 1);
                // Actualizamos contadores y dificultad de la pregunta
                $this->preguntaModel->actualizarEstadisticasYDificultad($idPreguntaActual, true);
            }

            $_SESSION['partida_puntaje']++;
            unset($_SESSION['pregunta_actual_id']);
            header("Location: /Pregunta/ruleta");
            exit();

        } else {
            // --- TABLA INTERMEDIA Y ESTADÍSTICAS ---
            if ($idPreguntaActual) {
                $_SESSION['preguntas_respondidas'][] = $idPreguntaActual;
                // Guardamos en la tabla intermedia (0 = incorrecta)
                $this->preguntaModel->guardarHistorialUsuario($usuarioId, $idPreguntaActual, 0);
                // Actualizamos contadores y dificultad de la pregunta
                $this->preguntaModel->actualizarEstadisticasYDificultad($idPreguntaActual, false);
            }

            $idsRespondidos = $_SESSION['preguntas_respondidas'] ?? [];
            $preguntasRespondidasDetalle = [];

            if (!empty($idsRespondidos)) {
                foreach ($idsRespondidos as $idPregunta) {
                    $preguntasRespondidasDetalle[] = $this->preguntaModel->getPregunta($idPregunta);
                }
            }

            $puntajeFinal = isset($_SESSION['partida_puntaje']) ? $_SESSION['partida_puntaje'] : 0;
            $this->partidaModel->guardarPartida($usuarioId, $puntajeFinal);

            // RECALCULAR MAESTRÍA JUGADOR: Va a usar el método nuevo con la tabla intermedia
            $this->usuarioModel->actualizarNivelMaestria($usuarioId);
            unset($_SESSION['preguntas_respondidas']);
            unset($_SESSION['pregunta_actual_id']);
            unset($_SESSION['partida_puntaje']);

            $datosVista = [
                "historial_preguntas" => $preguntasRespondidasDetalle,
                "hubo_respuestas" => !empty($preguntasRespondidasDetalle)
            ];

            $this->renderer->render("gameOver", $datosVista);
            exit();
        }
    }

    public function iniciarReporte(){
        $origen = $_POST['origen'] ?? 'gameOver';
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
            $urlOrigen = $_SERVER['HTTP_REFERER'] ?? '/Pregunta/list';

            header("Location: " . $urlOrigen);
            exit();
        }

        // para que vuelva al gameOver y pueda ver si quiere reportar  mas preguntas
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

        $this->renderer->render("gameOver", $datosVista);
        exit();
    }

    public function sugerir() {
        Log::info("Iniciando formulario sugerir");
        $categorias = $this->preguntaModel->getCategorias();
        $this->renderer->render("sugerirPregunta",
                ["categorias" => $categorias,
                "opciones" => [1,2,3,4]]);
    }

    public function enviarSugerencia() {
        Log::info("Enviando sugerencia");
        $categoriaId = $_POST['categoria_id'];
        $pregunta  = $_POST['texto_pregunta'];
        $respuestaCorrecta = $_POST['respuesta_correcta'];
        $respuestas = $_POST['respuestas'];

        $this->preguntaModel->guardarPreguntaYRespuestas($categoriaId, $pregunta, $respuestaCorrecta, $respuestas);
        Redirect::toIndex();
    }

    // calcula categorias y gira la ruleta
    public function ruleta() {
        Log::info("Calculando categorías para la ruleta...");

        // 1. Traemos la lista de lo que el jugador ya respondió en esta partida
        $listaIgnorar = $_SESSION['preguntas_respondidas'] ?? [];

        // 2. Le pedimos al modelo solo las categorías que tienen preguntas sin responder
        $categorias = $this->preguntaModel->getCategoriasDisponibles($listaIgnorar);
        $cantidadCategorias = count($categorias);

        // ESCENARIO A: Vació toda la base de datos (Partida Perfecta)
        if ($cantidadCategorias === 0) {
            // Lo mandamos al méto do list() sin categoría para que dispare la pantalla de victoria
            header("Location: /Pregunta/list");
            exit();
        }

        // ESCENARIO B: Queda una sola categoría, aca hacemos que el usuario vaya directamente a responder esa categoria y no vaya a la ruleta de nuevo.
        if ($cantidadCategorias === 1) {
            $idUnicaCategoria = $categorias[0]['id'];
            // Salteamos la vista de la ruleta y lo mandamos directo a jugar esa categoría
            header("Location: /Pregunta/list?categoria=" . $idUnicaCategoria);
            exit();
        }

        // ESCENARIO C: Quedan 2 o más categorías (Juego Normal)
        // Renderizamos la vista de la ruleta pasándole el array de categorías
        $this->renderer->render("ruletaView", [
            "categorias" => $categorias
        ]);
    }

    //hace limpieza general y luego empuja al jugador hacia la ruleta
    public function nuevaPartida() {
        Log::info("Iniciando una partida completamente nueva");

        // Destruimos cualquier rastro de la partida anterior
        unset($_SESSION['preguntas_respondidas']);
        unset($_SESSION['partida_puntaje']);
        unset($_SESSION['pregunta_actual_id']);

        // Redirigimos a la ruleta con la pizarra en blanco
        header("Location: /pregunta/ruleta");
        exit();
    }

    public function terminarPartida() {
        Log::info("El jugador decidió terminar la partida prematuramente");

        // 1. Agarramos los datos actuales
        $usuarioId = $_SESSION['id'];
        $puntajeFinal = isset($_SESSION['partida_puntaje']) ? $_SESSION['partida_puntaje'] : 0;

        // 2. Guardamos la partida con el puntaje que haya logrado (aunque sea 0)
        $this->partidaModel->guardarPartida($usuarioId, $puntajeFinal);

        // 3. Verificamos si este puntaje le alcanza para subir de maestría
        $this->usuarioModel->actualizarNivelMaestria($usuarioId);

        // 4. Limpiamos la session para el próximo juego
        unset($_SESSION['preguntas_respondidas']);
        unset($_SESSION['pregunta_actual_id']);
        unset($_SESSION['partida_puntaje']);

        // 5. Lo mandamos al Lobby
        header("Location: /Lobby");
        exit();
    }







}
