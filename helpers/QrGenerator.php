<?php
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Label\Label;
use Endroid\QrCode\Logo\Logo;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;

class QrGenerator
{
    public static function crearQrUsuario($username, $urlBase) {
        $writer = new PngWriter();

        //Create QR code
        $qrCode = new QrCode(
            data: "$urlBase/usuario/ver/$username",
            encoding: new Encoding('UTF-8'),
            //errorCorrectionLevel: ErrorCorrectionLevel::Low,
            size: 300,
            margin: 10,
            //roundBlockSizeMode: RoundBlockSizeMode::Margin,
            foregroundColor: new Color(0, 0, 0),
            backgroundColor: new Color(255, 255, 255)
        );

        //Create generic logo
//        $logo = new Logo(
//            path: __DIR__.'/assets/bender.png',
//            resizeToWidth: 50,
//            punchoutBackground: true
//        );

        //Create generic label
//        $label = new Label(
//            text: 'Label',
//            textColor: new Color(255, 0, 0)
//        );

//        $result = $writer->write($qrCode, $logo, $label);
        $result = $writer->write($qrCode);
        if (!$result) {
            Log::error("No se pudo generar el QR");
            return false;
        }

        //Directly output the QR code
//        header('Content-Type: '.$result->getMimeType());
//        echo $result->getString();

        //Save it to a file
        $qrCode = uniqid('qr_') . ".png";
        $dir = __DIR__ . '/../public/img/qr';
        if (!is_dir($dir))
            mkdir($dir, 0775, true);

        $result->saveToFile("$dir/$qrCode");

// Generate a data URI to include image data inline (i.e. inside an <img> tag)
//        $dataUri = $result->getDataUri();
        return $qrCode;
    }
}