<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;

use App\Services\MailService;

class EmailController extends Controller
{
    protected $mailService;

    public function __construct(MailService $mailService)
    {
        $this->mailService = $mailService;
    }

    public function sendTestEmail()
    {
        $to = 'jmenacho@casaelena.com.bo';
        $subject = 'Correo de prueba desde Laravel';
        $body = 'Este es un correo de prueba enviado desde Laravel utilizando Gmail.';

        $this->mailService->sendEmail($to, $subject, $body);

        return response()->json(['message' => 'Correo enviado (verifica los logs para más detalles).']);
    }
}
