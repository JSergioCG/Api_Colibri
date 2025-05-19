<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MailService
{
    // Lista de correos centrales (CC)
    protected $correosCentrales = [
        'jmenacho@casaelena.com.bo'
    ];

    // Mapeo de almId -> correo de sucursal
    protected $almacenesEmails = [
        11 => 'jmenacho@casaelena.com.bo',
        0  => 'jmenacho@casaelena.com.bo',
        1  => 'jmenacho@casaelena.com.bo',
        19 => 'jmenacho@casaelena.com.bo',
        18 => 'josemenacho@upb.edu',
        15 => 'jmenacho@casaelena.com.bo',
        14 => 'jmenacho@casaelena.com.bo',
        4 => 'jmenacho@casaelena.com.bo',
        3 => 'jmenacho@casaelena.com.bo',



    ];
     /**
     * Enviar correo.
     *
     * @param string $to
     * @param string $subject
     * @param string $body
     */
    public function sendEmail($to, $subject, $body)
    {
        try {
            Mail::raw($body, function ($message) use ($to, $subject) {
                $message->to($to)
                        ->subject($subject);
            });

            Log::info("[MailService] Correo enviado a $to con asunto: $subject");
        } catch (\Exception $e) {
            Log::error("[MailService] Error al enviar correo: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Notifica por correo que se ha consumido stock en un almacén.
     *
     * @param int    $almId
     * @param string $sku
     * @param int    $cantidad
     */
    public function sendStockConsumptionEmail($almId, $sku, $cantidad)
    {
        $almacenEmail = $this->almacenesEmails[$almId] ?? null;
        if (!$almacenEmail) {
            Log::warning("[MailService] No hay correo configurado para almId=$almId. No se envía.");
            return;
        }
    
        $subject = "Notificación de salida de stock (almId=$almId)";
        $body = "Estimado(a),\n\n"
              . "Se consumió stock en el almacén con ID=$almId.\n"
              . "SKU: $sku\n"
              . "Cantidad consumida: $cantidad\n\n"
              . "Saludos,\nSistema de Ventas (Colibri)";
    
        try {
            Mail::raw($body, function($message) use ($almacenEmail, $subject) {
                $message->to($almacenEmail)
                        ->subject($subject);
    
                foreach ($this->correosCentrales as $cc) {
                    $message->cc($cc);
                }
            });
            Log::info("[MailService] Correo enviado a $almacenEmail, SKU=$sku, cant=$cantidad");
        } catch (\Exception $e) {
            Log::warning("[MailService] Error al enviar correo de stock: " . $e->getMessage());
        }
    }

    /**
     * Envía correo cuando ocurre un error en createSale
     * 
     * @param string $errorMessage
     * @param string $errorTrace
     */
    public function sendErrorEmail($errorMessage, $errorTrace)
{
    $subject = "Error al sincronizar productos / crear venta";
    $body = "Se produjo un error al crear la venta:\n\n"
          . "Mensaje: $errorMessage\n\n"
          . "Trace:\n$errorTrace\n\n"
          . "Saludos,\nSistema WooCommerce/Colibri";

    try {
        Mail::raw($body, function($message) use ($subject) {
            $message->to('soporte@casaelena.com.bo')
                    ->subject($subject);

            foreach ($this->correosCentrales as $cc) {
                $message->cc($cc);
            }
        });
        Log::info("[MailService] Correo de error enviado (createSale)");
    } catch (\Exception $e) {
        Log::warning("[MailService] Error al enviar correo de error: " . $e->getMessage());
    }
}

}

