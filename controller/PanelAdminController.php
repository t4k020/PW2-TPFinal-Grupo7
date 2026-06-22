<?php
class PanelAdminController {
    private $model;
    private $renderer;
    private $request;

    private $preguntaModel;
    private $usuarioModel;
    private $partidaModel;
    public function __construct($model, $renderer, $request, $preguntaModel, $usuarioModel, $partidaModel)
    {
    $this->model = $model;
    $this->renderer = $renderer;
    $this->request = $request;
    $this->preguntaModel = $preguntaModel;
    $this->usuarioModel = $usuarioModel;
    $this->partidaModel = $partidaModel;
    }

    public function mostrar(){
        Log::info("panelAdminController::mostrar");
        $this->verificarAdmin();
        $usuario = $this->model->obtenerDatosUsuario($_SESSION["id"]);

        $this->renderer->render("panelAdminView", [
            "usuario" => $usuario["username"],
            "puntaje" => $usuario["puntaje"],
            "trampitas" => $usuario["trampitas"],
            "esAdmin"   => ($usuario["username"] === "Admin")

        ]);

    }
    public  function verReportes()
    {
        $this->verificarAdmin();

        $preguntasReportadas = $this->preguntaModel->getPreguntasReportadas();
        $datosVista = [
            "lista_reportes" => $preguntasReportadas,
            "hubo_reportes" => !empty($preguntasReportadas)
        ];
        $this->renderer->render("verPreguntasReportadas", $datosVista);
    }

    public function eliminarPregunta(){
        $this->verificarAdmin();
        $idPregunta = isset($_POST['id_pregunta']) ? intval($_POST['id_pregunta']) : 0;
        $this->preguntaModel->borrarPregunta($idPregunta);
        $this->verReportes();
    }

    public function ignorarPregunta(){
        $this->verificarAdmin();
        $idPregunta = isset($_POST['id_pregunta']) ? intval($_POST['id_pregunta']) : 0;
        $this->preguntaModel->ignorarPregunta($idPregunta);
        $this->verReportes();
    }

    public  function verEditarPregunta()
    {
        $this->verificarAdmin();
        $id = $_GET['id'] ?? null;
        $data['pregunta'] = $this->preguntaModel->getPregunta($id);

        $this->renderer->render("editarPregunta", $data);
    }

    public function procesarEdicion()
    {
        $this->verificarAdmin();
        // Validamos que vengan los datos mínimos obligatorios
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
            header("Location: /PanelAdmin/mostrar");
            exit();
        }

        $idPregunta = intval($_POST['id']);
        $nuevoTextoPregunta = $_POST['texto'] ?? '';
        $idRespuestaCorrecta = intval($_POST['respuesta_correcta_id'] ?? 0);
        $respuestasData = $_POST['respuestas'] ?? []; // Trae el array indexado [id => ['texto' => '...']]

        //Mandamos los datos al modelo
        $exito = $this->preguntaModel->updatePreguntaYRespuestas(
            $idPregunta,
            $nuevoTextoPregunta,
            $idRespuestaCorrecta,
            $respuestasData
        );

        // 3. Redirigimos según el resultado
        if ($exito) {
            // Podés agregar un mensaje de éxito si tenés variables de sesión
            log::info("se modificaron exitosamente");
            header("Location: /PanelAdmin/verReportes");
        } else {
            log::error("No se modificaron exitosamente ");
       }
        exit();
    }

    public  function verEstadisticasAdmin()
    {
        $filtro = $_GET['filtro_fecha'] ?? 'todo';

        $fechaDesde = null;

        switch ($filtro) {
            case 'hoy':
                $fechaDesde = date('Y-m-d 00:00:00');
                break;
            case 'semana':
                $fechaDesde = date('Y-m-d H:i:s', strtotime('-7 days'));
                break;
            case 'mes':
                $fechaDesde = date('Y-m-d H:i:s', strtotime('-30 days'));
                break;
            case 'anio':
                $fechaDesde = date('Y-m-d H:i:s', strtotime('-1 year'));
                break;
            default:
                $fechaDesde = null; // 'todo' no filtra por fecha
                break;
        }
        $this->verificarAdmin();
        $totalUsuarios = $this->usuarioModel->getTotalUsuarios($fechaDesde);
        $totalPreguntas = $this->preguntaModel->getTotalPreguntas($fechaDesde);
        $usuariosPorPais = $this->usuarioModel->getUsuariosPorPais($fechaDesde);
        $usuariosPorEdad = $this->usuarioModel->getUsuariosPorEdad($fechaDesde);
        $usuariosPorSexo = $this->usuarioModel->getUsuariosPorSexo($fechaDesde);
        $totalPartidas = $this->partidaModel->getTotalPartidas($fechaDesde);
        $aciertoGlobal = $this->partidaModel->getPorcentajeAciertoGlobal($fechaDesde);

        $datosVista = [
            "total_usuarios" => $totalUsuarios,
            "total_preguntas" => $totalPreguntas,
            "usuarios_por_pais" => $usuariosPorPais,
            "usuarios_por_edad" => $usuariosPorEdad,
            "usuarios_por_sexo" => $usuariosPorSexo,
            "total_partidas" => $totalPartidas,
            "acierto_global" => $aciertoGlobal,

            "filtro_todo"   => $filtro === 'todo',
            "filtro_hoy"    => $filtro === 'hoy',
            "filtro_semana" => $filtro === 'semana',
            "filtro_mes"    => $filtro === 'mes',
            "filtro_anio"   => $filtro === 'anio'


        ];


        $this->renderer->render("verEstadisticasAdmin.mustache", $datosVista);
    }


    /**
     * @return void
     */
    public function verificarAdmin(): void
    {
        if (!isset($_SESSION["id"])) {
            Redirect::to("/lobby");

        }
    }


}