<?php
class MaestriaService {
    private $usuarioModel;

    public function __construct($usuarioModel) {
        $this->usuarioModel = $usuarioModel;
    }

    public function evaluarNivel($idUsuario) {
        $estadisticas = $this->usuarioModel->obtenerEstadisticasJugador($idUsuario);

        $totalRespondidas = $estadisticas['total_respondidas'] ?? 0;
        $totalCorrectas = $estadisticas['total_correctas'] ?? 0;

        // Regla 1: Solo evaluamos si tiene 10 o más preguntas respondidas
        if ($totalRespondidas < 10) {
            return; // Cortamos la ejecución, sigue siendo Aprendiz
        }

        // Regla 2: Calcular el promedio de aciertos
        $porcentajeAcierto = ($totalCorrectas / $totalRespondidas) * 100;

        // Regla 3: Definir el nuevo nivel
        $nuevaMaestria = 'Aprendiz';

        if ($porcentajeAcierto >= 30 && $porcentajeAcierto < 70) {
            $nuevaMaestria = 'Amateur';
        } elseif ($porcentajeAcierto >= 70) {
            $nuevaMaestria = 'Maestro';
        }

        // Guardamos el nuevo nivel en la base de datos
        $this->usuarioModel->actualizarNivelMaestria($idUsuario, $nuevaMaestria);
    }
}