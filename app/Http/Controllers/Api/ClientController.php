<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ClientService;
use Illuminate\Support\Facades\Log;

class ClientController extends Controller
{
    protected $clientService;

    public function __construct(ClientService $clientService)
    {
        $this->clientService = $clientService;
    }

    /**
     * Verificar si existe el cliente por carnet, o crear uno nuevo.
     */
 
public function createOrVerifyClient(Request $request)
{
    try {
        // Validación de los datos
        $data = $request->validate([
            'cliNombre' => 'required|string',
            'cliCelular' => 'required|string',
            'cliEmail1' => 'required|email',
            'cliCarnet' => 'required|string',
        ]);

        Log::info('Datos recibidos para crear/verificar cliente', $data);

        // Llamar al servicio para verificar/crear el cliente
        $cliId = $this->clientService->verifyOrCreateClient(
            $data['cliNombre'],
            $data['cliCelular'],
            $data['cliEmail1'],
            $data['cliCarnet']
        );

        return response()->json([
            'message' => 'Cliente verificado o creado con éxito',
            'clientId' => $cliId
        ], 200);

    } catch (\Exception $e) {
        Log::error('Error en createOrVerifyClient:', ['error' => $e->getMessage()]);
        return response()->json([
            'error' => 'Ocurrió un error al crear o verificar el cliente.'
        ], 500);
    }
}
}
