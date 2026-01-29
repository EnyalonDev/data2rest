<?php

namespace App\Services;

use App\Core\Config;
use App\Core\Logger;

class MailService
{
    private $apiKey;
    private $fromEmail;
    private $fromName;

    public function __construct()
    {
        // Try to get from ENV first, then DB settings
        $this->apiKey = getenv('RESEND_API_KEY') ?: Config::getSetting('resend_api_key');
        $this->fromEmail = getenv('MAIL_FROM_ADDRESS') ?: Config::getSetting('mail_from_address', 'onboarding@resend.dev');
        $this->fromName = getenv('MAIL_FROM_NAME') ?: Config::getSetting('mail_from_name', 'Data2Rest System');
    }

    /**
     * Sends a welcome email to a new user.
     */
    public function sendWelcome($toEmail, $userName, $confirmUrl, $projectName)
    {
        $subject = "¡Bienvenido a $projectName!";

        // Plantilla HTML con estilos limpios
        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #333; line-height: 1.6; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 8px; }
                .header { text-align: center; margin-bottom: 30px; }
                .btn { display: inline-block; background-color: #000; color: #fff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; }
                .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #888; text-align: center; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Bienvenido a $projectName</h2>
                </div>
                
                <p>Hola <strong>$userName</strong>,</p>
                
                <p>Gracias por registrarte. Estamos encantados de tenerte con nosotros.</p>
                <p>Para activar tu cuenta y acceder al panel, por favor confirma tu correo electrónico:</p>
                
                <p style='text-align: center; margin: 30px 0;'>
                    <a href='$confirmUrl' class='btn'>Confirmar Email</a>
                </p>
                
                <p>Si no has creado esta cuenta, puedes ignorar este mensaje.</p>
                
                <div class='footer'>
                    <p>Por <strong>Data2Rest</strong> de <strong>Portafolio Creativo</strong><br>
                    <a href='https://www.portafoliocreativo.com' style='color: #888;'>www.portafoliocreativo.com</a></p>
                </div>
            </div>
        </body>
        </html>
        ";

        return $this->send($toEmail, $subject, $html);
    }

    /**
     * Sends an email via Resend API.
     */
    public function send($to, $subject, $html)
    {
        if (!$this->apiKey) {
            Logger::log('MAIL_ERROR', ['message' => 'Resend API Key is missing']);
            return false;
        }

        $url = 'https://api.resend.com/emails';

        $data = [
            'from' => "$this->fromName <$this->fromEmail>",
            'to' => [$to],
            'subject' => $subject,
            'html' => $html
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            Logger::log('MAIL_SENT', ['to' => $to, 'subject' => $subject]);
            return true;
        } else {
            Logger::log('MAIL_FAIL', ['to' => $to, 'error' => $response, 'code' => $httpCode]);
            return false;
        }
    }
}
