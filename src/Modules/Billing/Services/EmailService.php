<?php

namespace App\Modules\Billing\Services;

/**
 * Servicio de Envío de Emails
 * Maneja el envío de notificaciones por correo electrónico
 */
class EmailService
{
    /**
     * Envía un recordatorio de pago
     * 
     * @param array $data Datos del recordatorio
     * @return bool
     */
    public function sendReminder($data)
    {
        $to = $data['to'];
        $subject = "Recordatorio de Pago - {$data['project_name']}";

        $message = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                    .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                    .amount { font-size: 32px; font-weight: bold; color: #667eea; text-align: center; margin: 20px 0; }
                    .info { background: white; padding: 15px; border-left: 4px solid #667eea; margin: 20px 0; }
                    .footer { text-align: center; margin-top: 30px; color: #666; font-size: 12px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>Recordatorio de Pago</h1>
                    </div>
                    <div class='content'>
                        <p>Hola <strong>{$data['client_name']}</strong>,</p>
                        
                        <p>Te recordamos que tienes un pago próximo a vencer para el proyecto:</p>
                        
                        <div class='info'>
                            <strong>Proyecto:</strong> {$data['project_name']}<br>
                            <strong>Cuota #:</strong> {$data['installment_number']}<br>
                            <strong>Fecha de vencimiento:</strong> {$data['due_date']}
                        </div>
                        
                        <div class='amount'>
                            $" . number_format($data['amount'], 2) . "
                        </div>
                        
                        <p>Por favor, asegúrate de realizar el pago antes de la fecha de vencimiento para evitar cargos adicionales.</p>
                        
                        <p>Si ya realizaste el pago, por favor ignora este mensaje.</p>
                        
                        <div class='footer'>
                            <p>Este es un mensaje automático, por favor no respondas a este correo.</p>
                        </div>
                    </div>
                </div>
            </body>
            </html>
        ";

        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: Sistema de Pagos <noreply@data2rest.com>" . "\r\n";

        // En producción, usar un servicio de email real (SendGrid, Mailgun, etc.)
        // Por ahora, simulamos el envío

        // return mail($to, $subject, $message, $headers);

        // Simulación para desarrollo
        error_log("EMAIL REMINDER: To: {$to}, Subject: {$subject}");
        return true;
    }

    /**
     * Envía notificación de cuota vencida
     * 
     * @param array $data Datos de la cuota vencida
     * @return bool
     */
    public function sendOverdueNotification($data)
    {
        $to = $data['to'];
        $subject = "Pago Vencido - {$data['project_name']}";

        $message = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                    .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                    .amount { font-size: 32px; font-weight: bold; color: #f5576c; text-align: center; margin: 20px 0; }
                    .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; }
                    .info { background: white; padding: 15px; border-left: 4px solid #f5576c; margin: 20px 0; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>⚠️ Pago Vencido</h1>
                    </div>
                    <div class='content'>
                        <p>Hola <strong>{$data['client_name']}</strong>,</p>
                        
                        <div class='warning'>
                            <strong>ATENCIÓN:</strong> Tienes un pago vencido que requiere tu atención inmediata.
                        </div>
                        
                        <div class='info'>
                            <strong>Proyecto:</strong> {$data['project_name']}<br>
                            <strong>Cuota #:</strong> {$data['installment_number']}<br>
                            <strong>Fecha de vencimiento:</strong> {$data['due_date']}<br>
                            <strong>Días de retraso:</strong> {$data['days_overdue']}
                        </div>
                        
                        <div class='amount'>
                            $" . number_format($data['amount'], 2) . "
                        </div>
                        
                        <p>Por favor, contacta con nosotros lo antes posible para regularizar tu situación.</p>
                    </div>
                </div>
            </body>
            </html>
        ";

        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: Sistema de Pagos <noreply@data2rest.com>" . "\r\n";

        error_log("EMAIL OVERDUE: To: {$to}, Subject: {$subject}");
        return true;
    }
}
