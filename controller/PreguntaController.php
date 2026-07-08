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

        // 1. Control de reinicio explícito (cuando tocan "Jugar de nuevo")
        $forzarReinicio = ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reiniciar_partida']));

        if ($forzarReinicio) {
            unset($_SESSION['preguntas_respondidas']);
            unset($_SESSION['partida_puntaje']);
            unset($_SESSION['pregunta_actual_id']);
        }

        // Atrapamos la categoría de la URL
        $idCategoria = isset($_GET['categoria']) ? intval($_GET['categoria']) : null;

        // 2. INICIALIZACIÓN COMPLETA Y SEGURA: Si no existen, se crean. Si ya existen, NO se tocan.
        if (!isset($_SESSION['preguntas_respondidas'])) {
            $_SESSION['preguntas_respondidas'] = [];
        }
        if (!isset($_SESSION['partida_puntaje'])) {
            $_SESSION['partida_puntaje'] = 0;
        }

        $usuarioId = $_SESSION['id'];
        $listaIgnorar = $this->preguntaModel->getPreguntasVistasPorUsuario($usuarioId);

        // 3. FLUJO DE PREGUNTA ACTIVA (Mitigación de F5)
        if (isset($_SESSION['pregunta_actual_id'])) {
            $idPreguntaEnCurso = $_SESSION['pregunta_actual_id'];
            $preguntaCompleta = $this->preguntaModel->getPregunta($idPreguntaEnCurso);

            // Temporizador
            $tiempoRestante = $_SESSION['tiempo_limite'] - (time() - $_SESSION['inicio_pregunta']);
            if ($tiempoRestante < 0) {
                $tiempoRestante = 0;
            }
            $preguntaCompleta['tiempo_restante'] = $tiempoRestante;

            if ($preguntaCompleta['tiempo_restante'] <= 0) {
                header("Location: /Pregunta/evaluar?timeout=1");
                exit();
            }
        } else {
            // 4. GENERAR NUEVA PREGUNTA (Viene de la ruleta o respondió bien)
            $username = $_SESSION['username'] ?? null;
            $usuarioDatos = $this->usuarioModel->getUsuario($username);
            $maestriaJugador = $usuarioDatos['maestria'] ?? 'Amateur';

            $preguntaCompleta = $this->preguntaModel->getRandomPregunta($listaIgnorar, $maestriaJugador, $idCategoria);

            if ($preguntaCompleta !== null) {
                $_SESSION['pregunta_actual_id'] = $preguntaCompleta['id'];
                $_SESSION['inicio_pregunta'] = time();
                $_SESSION['tiempo_limite'] = 15;
                $preguntaCompleta['tiempo_restante'] = 15;
            }
        }

        // 5. CHEQUEO DE VICTORIA (Partida Perfecta)
        if ($preguntaCompleta === null) {
            $puntajeTotal = isset($_SESSION['partida_puntaje']) ? intval($_SESSION['partida_puntaje']) : 0;

            if ($usuarioId) {
                // Guardamos la victoria en la base de datos
                $this->partidaModel->guardarPartida($usuarioId, $puntajeTotal);
                $this->usuarioModel->actualizarNivelMaestria($usuarioId);

                // Reseteamos el historial físico para que la próxima partida tenga preguntas
                $this->preguntaModel->resetearPreguntasVistas($usuarioId);
            }

            // Preparamos la vista de éxito
            $data['mensaje_victoria'] = "¡Increíble! Respondiste absolutamente todas las preguntas disponibles. 🏆";
            $data['puntaje_final'] = $puntajeTotal;

            // EL CAMBIO DEFINITIVO: NO usamos unset() acá.
            // Dejamos que las variables mueran recién cuando el usuario decida salir o reiniciar.
            // Solo limpiamos el ID de la pregunta para que no se quede trabado en el F5.
            unset($_SESSION['pregunta_actual_id']);

            $this->renderer->render("partida_perfecta", $data);
            return;
        }

        // 6. CONTROL DE ACCESO AL LOBBY
        if (!isset($_SESSION['pregunta_actual_id']) && $idCategoria === null) {
            header("Location: /Lobby");
            exit();
        }

        // Mezclamos respuestas y renderizamos pregunta normal
        $respuestasAMezclar = $preguntaCompleta['respuestas'];
        shuffle($respuestasAMezclar);
        $preguntaCompleta['respuestas'] = $respuestasAMezclar;

        $this->renderer->render("pregunta", $preguntaCompleta);
    }

    public function evaluar() {
        Log::info("evaluando pregunta");
        $usuarioId = $_SESSION['id'];
        $idPreguntaActual = $_SESSION['pregunta_actual_id'] ?? null;

        // --- ESCENARIO 1: SE TERMINÓ EL TIEMPO (PERDIÓ) ---
        if (isset($_GET['timeout'])) {
            if ($idPreguntaActual) {
                $_SESSION['preguntas_respondidas'][] = $idPreguntaActual;
                $this->preguntaModel->guardarHistorialUsuario($usuarioId, $idPreguntaActual, 0);
                $this->preguntaModel->actualizarEstadisticasYDificultad($idPreguntaActual, false);
            }

            $idsRespondidos = $_SESSION['preguntas_respondidas'] ?? [];
            $preguntasRespondidasDetalle = [];
            foreach ($idsRespondidos as $idPregunta) {
                $pregunta = $this->preguntaModel->getPregunta($idPregunta);
                if ($pregunta) {
                    $preguntasRespondidasDetalle[] = $pregunta;
                }
            }

            // Guardamos los puntos de la derrota
            $puntajeFinal = $_SESSION['partida_puntaje'] ?? 0;
            $this->partidaModel->guardarPartida($usuarioId, $puntajeFinal);
            $this->usuarioModel->actualizarNivelMaestria($usuarioId);

            // Limpieza absoluta para la próxima partida
            unset($_SESSION['inicio_pregunta']);
            unset($_SESSION['tiempo_limite']);
            unset($_SESSION['preguntas_respondidas']);
            unset($_SESSION['pregunta_actual_id']);
            unset($_SESSION['partida_puntaje']);

            $this->renderer->render("gameOver", [
                "historial_preguntas" => $preguntasRespondidasDetalle,
                "hubo_respuestas" => !empty($preguntasRespondidasDetalle)
            ]);
            exit();
        }

        $idRespuesta = intval($this->request->get('id_respuesta'));

        if ($idRespuesta === 0) {
            header("Location: /Lobby");
            exit();
        }

        $esCorrecta = $this->preguntaModel->verificarRespuesta($idRespuesta);

        // --- ESCENARIO 2: RESPONDIÓ CORRECTAMENTE ---
        if ($esCorrecta) {
            if ($idPreguntaActual) {
                $_SESSION['preguntas_respondidas'][] = $idPreguntaActual;
                $this->preguntaModel->guardarHistorialUsuario($usuarioId, $idPreguntaActual, 1);
                $this->preguntaModel->actualizarEstadisticasYDificultad($idPreguntaActual, true);
            }

            // Inicializamos si no existe, y sumamos el punto de forma segura
            if (!isset($_SESSION['partida_puntaje'])) {
                $_SESSION['partida_puntaje'] = 0;
            }
            $_SESSION['partida_puntaje']++;

            // Limpiamos los datos de la pregunta vieja para que LIST cargue la nueva
            unset($_SESSION['pregunta_actual_id']);
            unset($_SESSION['inicio_pregunta']);
            unset($_SESSION['tiempo_limite']);

            // Vamos a la ruleta o al flujo que maneje tu juego
            header("Location: /Pregunta/ruleta");
            exit();

        } else {
            // --- ESCENARIO 3: RESPONDIÓ INCORRECTAMENTE (PERDIÓ) ---
            if ($idPreguntaActual) {
                $_SESSION['preguntas_respondidas'][] = $idPreguntaActual;
                $this->preguntaModel->guardarHistorialUsuario($usuarioId, $idPreguntaActual, 0);
                $this->preguntaModel->actualizarEstadisticasYDificultad($idPreguntaActual, false);
            }

            $idsRespondidos = $_SESSION['preguntas_respondidas'] ?? [];
            $preguntasRespondidasDetalle = [];
            if (!empty($idsRespondidos)) {
                foreach ($idsRespondidos as $idPregunta) {
                    $preguntasRespondidasDetalle[] = $this->preguntaModel->getPregunta($idPregunta);
                }
            }

            // Guardamos la partida con el puntaje acumulado real
            $puntajeFinal = isset($_SESSION['partida_puntaje']) ? intval($_SESSION['partida_puntaje']) : 0;
            $this->partidaModel->guardarPartida($usuarioId, $puntajeFinal);
            $this->usuarioModel->actualizarNivelMaestria($usuarioId);

            // Limpiamos de forma segura sin romper la estructura analítica
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
        unset($_SESSION['inicio_pregunta']);
        unset($_SESSION['tiempo_limite']);
        unset($_SESSION['preguntas_respondidas']);
        unset($_SESSION['pregunta_actual_id']);
        unset($_SESSION['partida_puntaje']);

        // 5. Lo mandamos al Lobby
        header("Location: /Lobby");
        exit();
    }

}
