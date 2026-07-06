<?php

use Dompdf\Dompdf;
use Dompdf\Options;
class PanelAdminController
{
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

    public function mostrar()
    {
        Log::info("panelAdminController::mostrar");
        $this->verificarAdmin();
        $usuario = $this->model->obtenerDatosUsuario($_SESSION["id"]);

        $this->renderer->render("panelAdminView", [
            "usuario" => $usuario["username"],
            "puntaje" => $usuario["puntaje"],
            "trampitas" => $usuario["trampitas"],
            "esAdmin" => ($usuario["username"] === "Admin")

        ]);

    }

    public function verReportes()
    {
        $this->verificarAdmin();

        $preguntasReportadas = $this->preguntaModel->getPreguntasReportadas();
        $datosVista = [
            "lista_reportes" => $preguntasReportadas,
            "hubo_reportes" => !empty($preguntasReportadas),
            "base_url" => "/PanelAdmin",
            "nombre_panel" => "Panel Admin"
        ];
        $this->renderer->render("verPreguntasReportadas", $datosVista);
    }

    public function aprobarPregunta()
    {
        $this->verificarAdmin();
        $idPregunta = isset($_POST['id_pregunta']) ? intval($_POST['id_pregunta']) : 0;
        $this->preguntaModel->aprobarPregunta($idPregunta);
        $this->verSugeridas();
    }

    public function eliminarPregunta()
    {
        $this->verificarAdmin();
        $idPregunta = isset($_POST['id_pregunta']) ? intval($_POST['id_pregunta']) : 0;
        $this->preguntaModel->borrarPregunta($idPregunta);

        // Capturamos la URL anterior (si por algún motivo falla, lo mandamos al panel principal)
        $urlOrigen = $_SERVER['HTTP_REFERER'] ?? '/PanelAdmin/mostrar';

        // Redirigimos
        header("Location: " . $urlOrigen);
        exit();

    }

    public function ignorarPregunta()
    {
        $this->verificarAdmin();
        $idPregunta = isset($_POST['id_pregunta']) ? intval($_POST['id_pregunta']) : 0;
        $this->preguntaModel->ignorarPregunta($idPregunta);
        $this->verReportes();
    }

    public function verEditarPregunta()
    {
        $this->verificarAdmin();
        $id = $_GET['id'] ?? $_POST['id'] ?? null;
        $data['pregunta'] = $this->preguntaModel->getPregunta($id);

        $data['url_procesar'] = "/PanelAdmin/procesarEdicion";

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
            Log::info("Se modificaron exitosamente los datos de la pregunta.");

            // Atrapamos el estado que mandó el formulario oculto
            $estadoPregunta = $_POST['estado_pregunta'] ?? '';

            // Definimos la base de la URL según el rol
            $baseUrl = ($_SESSION['rol'] === 'ADMIN') ? '/PanelAdmin' : '/PanelEditor';

            // Redirigimos como corresponde
            if ($estadoPregunta === 'PENDIENTE') {
                header("Location: " . $baseUrl . "/verSugeridas");
            } else {
                header("Location: " . $baseUrl . "/verReportes");
            }

        } else {
            Log::error("No se modificaron exitosamente.");
            $baseUrl = ($_SESSION['rol'] === 'ADMIN') ? '/PanelAdmin' : '/PanelEditor';
            header("Location: " . $baseUrl . "/mostrar");
        }
        exit();
    }

    public function verEstadisticasAdmin()
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

        $datosTotalUsuariosEscalera = $this->usuarioModel->getUsuariosGrafico();
        $preguntasTotalEscalera = $this->preguntaModel->getPreguntasGrafico();
        $partidasTotalEscalera = $this->partidaModel->getPartidasGrafico();




        $totalPreguntas = $this->preguntaModel->getTotalPreguntas($fechaDesde);
        $usuariosPorPais = $this->usuarioModel->getUsuariosPorPais($fechaDesde);
        $usuariosPorEdad = $this->usuarioModel->getUsuariosPorEdad($fechaDesde);
        $usuariosPorSexo = $this->usuarioModel->getUsuariosPorSexo($fechaDesde);
        $totalPartidas = $this->partidaModel->getTotalPartidas($fechaDesde);
        $aciertoGlobal = $this->partidaModel->getPorcentajeAciertoGlobal($fechaDesde);
        $error = 100 - $aciertoGlobal;
        $datosDonaAciertos = [
            ["tipo" => "Aciertos", "porcentaje" => $aciertoGlobal],
            ["tipo" => "Errores", "porcentaje" => $error]
        ];

        $datosVista = [
            "total_usuarios" => $totalUsuarios,
            "total_preguntas" => $totalPreguntas,
            "usuarios_por_pais" => $usuariosPorPais,
            "usuarios_por_edad" => $usuariosPorEdad,
            "usuarios_por_sexo" => $usuariosPorSexo,
            "total_partidas" => $totalPartidas,
            "acierto_global" => $aciertoGlobal,

            // JSON para los graficos
            "json_preguntas_tiempo" => json_encode($preguntasTotalEscalera),
            "json_usuarios_tiempo" => json_encode($datosTotalUsuariosEscalera),
            "json_pais" => json_encode($usuariosPorPais ?: []),
            "json_edad" => json_encode($usuariosPorEdad ?: []),
            "json_sexo" => json_encode($usuariosPorSexo ?: []),
            "json_partidas_tiempo" => json_encode($partidasTotalEscalera),
            "json_acierto_dona" => json_encode($datosDonaAciertos),

            "filtro_todo" => $filtro === 'todo',
            "filtro_hoy" => $filtro === 'hoy',
            "filtro_semana" => $filtro === 'semana',
            "filtro_mes" => $filtro === 'mes',
            "filtro_anio" => $filtro === 'anio'


        ];


        $this->renderer->render("verEstadisticasAdmin", $datosVista);
    }


    /**
     * @return void
     */
    public function verificarAdmin(): void
    {
        if (!isset($_SESSION["rol"]) || $_SESSION["rol"] !== "ADMIN") {
            header("Location: /lobby");
            exit();
        }
    }

    public function verSugeridas()
    {
        Log::info("Mostrando Preguntas Sugeridas");
        $preguntasSugeridas = $this->preguntaModel->getPreguntasSugeridas();
        $this->renderer->render("preguntasSugeridas", [
            "lista_sugeridas" => $preguntasSugeridas,
            "hubo_sugerencias" => !empty($preguntasSugeridas),
            "base_url" => "/PanelAdmin",
            "nombre_panel" => "Panel Admin"
        ]);
    }


    //NECESITA ACTIVAR EXTENSION GD EN APACHE (php.ini) para dibujar las graficas en el pdf
    public function generarReportePdf()
    {
        // Verificamos que los datos hayan llegado por POST y que existan las imágenes
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['img_pais'])) {

            // capturamos y limpiamos los Base64 eliminando espacios o saltos de línea
            // los graficos esta codificado en base64 para que pueda ser reescrito en pdf
            $imgPais   = str_replace([" ", "\n", "\r"], ["+", "", ""], $_POST['img_pais']);
            $imgEdad   = str_replace([" ", "\n", "\r"], ["+", "", ""], $_POST['img_edad']);
            $imgGenero = str_replace([" ", "\n", "\r"], ["+", "", ""], $_POST['img_genero']);
            $imgTiempo  = str_replace([" ", "\n", "\r"], ["+", "", ""], $_POST['img_usuarios_tiempo']);
            $imgPreguntas = str_replace([" ", "\n", "\r"], ["+", "", ""], $_POST['img_preguntas_tiempo']);
            $imgPartidas  = str_replace([" ", "\n", "\r"], ["+", "", ""], $_POST['img_partidas_tiempo']);
            $imgAciertos = str_replace([" ", "\n", "\r"], ["+", "", ""], $_POST['img_acierto_dona']);


            // Html del Pdf
            $htmlPdf = "
            <!DOCTYPE html>
            <html lang='es'>
            <head>
                <meta charset='UTF-8'>
                <style>
                    body { 
                        font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; 
                        color: #333; 
                        margin: 30px; 
                    }
                    .header { 
                        text-align: center; 
                        border-bottom: 2px solid #212529; 
                        padding-bottom: 10px; 
                        margin-bottom: 30px; 
                    }
                    h1 { color: #212529; margin-bottom: 5px; font-size: 24px; }
                    .subtitle { color: #666; font-size: 14px; }
                    .chart-box { 
                        text-align: center; 
                        margin-bottom: 30px; 
                        page-break-inside: avoid; 
                    }
                    .chart-title {
                        font-size: 16px;
                        color: #495057;
                        margin-bottom: 10px;
                        text-align: left;
                        border-left: 4px solid #0d6efd;
                        padding-left: 10px;
                    }
                    .chart-img { 
                        width: 100%; 
                        max-width: 550px; 
                        border: 1px solid #dee2e6; 
                        padding: 10px; 
                        background: #fff; 
                        border-radius: 6px;
                    }
                    .footer { 
                        text-align: center; 
                        font-size: 10px; 
                        color: #adb5bd; 
                        position: fixed; 
                        bottom: 0; 
                        width: 100%; 
                    }
                </style>
            </head>
            <body>
                <div class='header'>
                    <h1>Preguntados - Reporte Estadístico de Administración</h1>
                    <p class='subtitle'>Generado en tiempo real el " . date('d/m/Y H:i') . "</p>
                </div>
                
                <div class='chart-box'>
                    <div class='chart-title'>Evolución Acumulativa de Usuarios Registrados</div>
                    <img class='chart-img' src='{$imgTiempo}' />
                 </div>
                 
                <div class='chart-box'>
                    <div class='chart-title'>Evolución Acumulativa de Preguntas Creadas</div>
                    <img class='chart-img' src='{$imgPreguntas}' />
                </div>
            
                <div class='chart-box'>
                    <div class='chart-title'>Distribución de Usuarios por País</div>
                    <img class='chart-img' src='{$imgPais}' />
                </div>
            
                <div class='chart-box'>
                    <div class='chart-title'>Distribución de Usuarios por Rango de Edad</div>
                    <img class='chart-img' src='{$imgEdad}' />
                </div>
            
                <div class='chart-box'>
                    <div class='chart-title'>Distribución de Usuarios por Género</div>
                    <img class='chart-img' src='{$imgGenero}' />
                </div>
                <div class='chart-box'>
                    <div class='chart-title'>Evolución Acumulativa de Partidas Jugadas</div>
                    <img class='chart-img' src='{$imgPartidas}' />
                </div>
                
                 <div class='chart-box'>
                    <div class='chart-title'>Evolución Acumulativa de Partidas Jugadas</div>
                    <img class='chart-img' src='{$imgAciertos}' />
                </div>
            
                <div class='footer'>
                    Preguntados - Panel Administrativo Confidencial
                </div>
            </body>
            </html>
            ";

            // se crea el objeto que configura el motor DoomPdf
            $options = new Options();
            // permite intepretar Base64 para los graficos
            $options->set('isRemoteEnabled', true);
            // que use el html moderno
            $options->set('isHtml5ParserEnabled', true);
            //define el directorio root
            $options->set('chroot', __DIR__ . '/..');
            //al iniciar el motor se le pasa el objeto para que sea configurado
            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($htmlPdf);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            // Fuerza la descarga del archivo
            $dompdf->stream("Reporte_Estadisticas_Preguntados_" . date('Ymd') . ".pdf", array("Attachment" => true));
            exit;
        }

        header("Location: /PanelAdmin/verEstadisticasAdmin");
        exit;
    }
}