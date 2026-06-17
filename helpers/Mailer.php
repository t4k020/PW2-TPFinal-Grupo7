<?php
//Import PHPMailer classes into the global namespace
//These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

//Load Composer's autoloader (created by composer, not included with PHPMailer)
require_once __DIR__ . '/../vendor/autoload.php';
class Mailer
{
    public static function enviarValidacion($mail, $link)
    {
        //Create an instance; passing `true` enables exceptions
        $mailer = new PHPMailer(true);

        try {
            //Server settings
            //$mail->SMTPDebug = SMTP::DEBUG_SERVER;      //Enable verbose debug output
            $mailer->isSMTP();        //Send using SMTP
            $mailer->Host       = 'smtp.gmail.com';       //Set the SMTP server to send through
            $mailer->SMTPAuth   = true;       //Enable SMTP authentication
            $mailer->Username   = 'pwtpg2712@gmail.com';     //SMTP username
            $mailer->Password   = 'ehrq zayo dsay wrlt';        //SMTP password
            $mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;        //Enable implicit TLS encryption
            $mailer->Port       = 465;        //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

            //Recipients
            $mailer->setFrom('pwtpg2712@gmail.com', 'Preguntados');
            $mailer->addAddress($mail);     //Add a recipient
            //$mail->addReplyTo('info@example.com', 'Information');
            //$mail->addCC('cc@example.com');
            //$mail->addBCC('bcc@example.com');

            //Attachments
            //$mail->addAttachment('/var/tmp/file.tar.gz');         //Add attachments
            //$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    //Optional name

            //Content
            $mailer->isHTML(true);                                  //Set email format to HTML
            $mailer->Subject = 'Activación de Cuenta - Preguntados';
            $mailer->Body    = "This is the <a href='$link'>HTML</a> message body <b>in bold!</b>";
            //$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

            $mailer->send();
            return true;
        }

        catch (Exception $e) {
            return false;
        }
    }
}