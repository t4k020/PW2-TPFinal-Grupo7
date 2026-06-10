<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// se crea un servicio para que to-do lo relacionado a PHPmailer pase aca
class MailService {

    private $mailer;

    public function __construct(PHPMailer $mailer) {
        $this->mailer = $mailer;
    }

    public function enviarValidacion($email, $nombre, $token): bool {

        try {
            Log::info("Inicia el envio de correo");

            $link = "http://localhost/tu_proyecto/verify.php?token=" . $token;

            //borra cualquier direccion mail anteriormente usada
            $this->mailer->clearAddresses();

            $this->mailer->addAddress($email);
            //le indica que el mensaje sera en html en vez de texto plano
            $this->mailer->isHTML(true);

            $this->mailer->Subject = 'Verifica tu cuenta';

            $this->mailer->Body = "
                <h2>Hola {$nombre}</h2>

                <p>Gracias por registrarte.</p>

                <p>
                    Haz clic en el siguiente enlace para activar tu cuenta:
                </p>

                <a href='{$link}'>
                    Activar cuenta
                </a>
            ";
            // El nivel 2 le pide a PHPMailer que imprima en la pantalla toda la conversación  que tiene tu servidor con los servidores de Google
            $this->mailer->SMTPDebug = 2;
            $this->mailer->Debugoutput = 'html';

            if (!$this->mailer->send()) {
                die($this->mailer->ErrorInfo);
            }


            return true;

        } catch (Exception $e) {

            Log::error("MailService::enviarValidacion - " . $e->getMessage());

            return false;
        }
    }
}