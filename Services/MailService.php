<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailService {

    private $mailer;

    public function __construct(PHPMailer $mailer) {
        $this->mailer = $mailer;
    }

    public function enviarValidacion($email, $nombre, $token): bool {

        try {
            Log::info("Inicia el envio de correo");

            $link = "http://localhost/tu_proyecto/verify.php?token=" . $token;
            $this->mailer->SMTPDebug = 2;

            $this->mailer->clearAddresses();

            $this->mailer->addAddress($email);

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