<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ValeService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Services\ClientService;
use Illuminate\Support\Facades\Log;


class ValeController extends Controller
{
    protected $valeService;

    public function __construct(ValeService $valeService)
    {
        $this->valeService = $valeService;
    }

  /**
 * Crear un vale
 * POST /api/vales
 *
 * Body JSON típico:
 * {
 *   "valCorrelativo": "WEB-GC-500-ABCD",
 *   "valEstado": "A",
 *   "monto": 500,
 *   "valOrigen": "WEB",
 *   "valFecha": "2025-01-15 10:00:00",
 *   "usuario": "woo-plugin",
 *   "cliente": {
 *       "cliNombre": "John Doe",
 *       "cliEmail": "johndoe@example.com",
 *       "cliTelefono": "123456789",
 *       "cliCarnet": "ABC12345"
 *   }
 * }
 */
public function store(Request $request)
{
    $data = $request->validate([
        'valCorrelativo' => 'required|string|max:50|unique:cjtValeDetalle,valCorrelativo',
        'valEstado'      => 'required|string|max:1',
        'monto'          => 'required|numeric|min:0.01',
        'valOrigen'      => 'nullable|string|max:10',
        'valFecha'       => 'nullable|date',
        'usuario'        => 'nullable|string|max:50',
        'cliente'        => 'nullable|array', // Validación del cliente
        'cliente.cliNombre'  => 'required_with:cliente|string|max:100',
        'cliente.cliEmail'   => 'nullable|email|max:100',
        'cliente.cliTelefono' => 'nullable|string|max:15',
        'cliente.cliCarnet'  => 'required_with:cliente|string|max:20',
    ]);

    try {
        // Si hay datos del cliente, verificar o crear el cliente
        $clienteId = null;
        if (!empty($data['cliente'])) {
            $clienteId = app(ClientService::class)->verifyOrCreateClient(
                $data['cliente']['cliNombre'],
                $data['cliente']['cliTelefono'] ?? null,
                $data['cliente']['cliEmail'] ?? null,
                $data['cliente']['cliCarnet']
            );
        } else {
            // Usar un cliente genérico si no se proporciona información del cliente
            $clienteId = 1523634;
        }

        // Agregar clienteId al conjunto de datos 
        $data['cliId'] = $clienteId;

        // Llamar al servicio para crear el vale
        $vvaId = $this->valeService->createVentaVale($data);

        return response()->json([
            'message' => 'Vale creado correctamente',
            'valId'   => $vvaId,
        ], 201);

    } catch (\Exception $e) {
        Log::error('Error al crear vale:', ['error' => $e->getMessage()]);
        return response()->json([
            'error' => $e->getMessage()
        ], 400);
    }
}


    /**
     * 2) Obtener detalle (estado) de un vale
     * GET /api/vales/{valCorrelativo}
     *
     * Ejemplo: GET /api/vales/detalle/WEB-GC-500-ABCD
     */
    public function show($valCorrelativo)
    {
        $vale = $this->valeService->getValeByCorrelativo($valCorrelativo);
        if (!$vale) {
            return response()->json([
                'error' => "No se encontró Vale con correlativo=$valCorrelativo"
            ], 404);
        }

        return response()->json([
            'valCorrelativo' => $vale->valCorrelativo,
            'vacId'          => $vale->vacId,
            'valEstado'      => $vale->valEstado,
            'valOrigen'      => $vale->valOrigen ?? '',
            'valFecha'       => $vale->valFecha,
        ], 200);
    }

    /**
     * 3) Actualizar el estado de un vale
     * PUT /api/vales/{valCorrelativo}/status
     *
     * Body JSON:
     * {
     *   "nuevoEstado": "I",
     *   "motivo": "Canjeado en la tienda",
     *   "fecha": "2025-01-20 09:30:00",
     *   "usuario": "woo-plugin"
     * }
     */
    public function updateStatus(Request $request, $valCorrelativo)
    {
        $data = $request->validate([
            'nuevoEstado' => 'required|string|max:1',
            'motivo'      => 'nullable|string|max:255',
            'fecha'       => 'nullable|date',
            'usuario'     => 'nullable|string|max:50',
        ]);

        $nuevoEstado = $data['nuevoEstado'];
        $motivo      = $data['motivo']   ?? null;
        $fecha       = $data['fecha']    ?? null;
        $usuario     = $data['usuario']  ?? 'api';

        try {
            $this->valeService->updateValeStatus($valCorrelativo, $nuevoEstado, $motivo, $fecha, $usuario);

            return response()->json([
                'message'   => 'Estado actualizado',
                'valEstado' => $nuevoEstado,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * 4) Listar vales creados por 'WEB' con filtros:
     * GET /api/vales/sync-ecom?estado=A
     */
    public function getEcomValesForSync(Request $request)
    {
        $filters = [
            'estado' => $request->query('estado'), 
        ];

$vales = $this->valeService->getEcomValesForSync($filters);
        return response()->json($vales, 200);
    }
} 
