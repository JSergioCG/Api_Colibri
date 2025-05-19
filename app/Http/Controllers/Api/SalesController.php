<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Services\ClientService;

use Illuminate\Http\Request;
use App\Services\SalesService;

class SalesController extends Controller
{
    protected $saleService;
    protected $clientService;

    public function __construct(SalesService $saleService, ClientService $clientService)
    {
        $this->saleService = $saleService;
        $this->clientService = $clientService;
    }

    public function createSale(Request $request)
    {
        $validatedData = $request->validate([
            'nombre' => 'required|string',
            'celular' => 'required|string',
            'email1' => 'required|email',
            'carnet' => 'required|string',
            'productos' => 'required|array',
            'productos.*.sku' => 'required|string',
            'productos.*.precio' => 'required|numeric',
            'productos.*.cantidad' => 'required|integer|min:1',
            'tipoPago' => 'required|string|in:E,T,AB,VR,BO',
            'montoPagado' => 'required|numeric',
        ]);

        try {
            // 1. Verificar o Crear Cliente
            $clienteId = $this->clientService->verifyOrCreateClient(
                $validatedData['nombre'],
                $validatedData['celular'],
                $validatedData['email1'],
                $validatedData['carnet']
            );

            // 2. Crear la Venta
            $response = $this->saleService->createSale(
                $validatedData['productos'],
                $validatedData['tipoPago'],
                $validatedData['montoPagado'],
                $clienteId
            );

            return response()->json(['success' => true, 'data' => $response], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
