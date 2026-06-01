<?php

class Session
{
    private const TIMEOUT = 3600; // 1 hora

    public static function start()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        self::checkTimeout();
    }

    private static function checkTimeout()
    {
        if (isset($_SESSION['ultima_actividad'])) {

            if (time() - $_SESSION['ultima_actividad'] > self::TIMEOUT) {

                self::destroy();

                Redirect::to("/login");
                exit;
            }
        }

        $_SESSION['ultima_actividad'] = time();
    }

    public static function destroy()
    {
        $_SESSION = [];
        session_destroy();
    }
}