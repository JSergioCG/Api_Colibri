<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ValeService
{
    /**
     * Crear una transacción completa de venta de vales basándose en los datos recibidos.
     */
    public function createVentaVale(array $data)
    {
        DB::beginTransaction();
        try {
            // Buscar vacId en base al monto
            $vacId = $this->getVacIdByAmount($data['monto']);
    
            // Convertir fecha a formato esperado
            $valFechaString = DB::raw("GETDATE()");
    
            // Crear el registro del vale en cjtValeDetalle
            $valeId = DB::table('cjtValeDetalle')->insertGetId([
                'valCorrelativo' => $data['valCorrelativo'], // Correlativo enviado desde el frontend
                'valEstado'      => $data['valEstado'],     // p.ej. 'A' => activo
                'vacId'          => $vacId,                // ID del vale por monto
                'valOrigen'      => $data['valOrigen'] ?? 'WEB',
                'valFecha'       => $valFechaString,
            ]);
    
            // Insertar en cjtVentaValeRegaloTxn (Encabezado de la venta)
            $vvaId = DB::table('cjtVentaValeRegaloTxn')->insertGetId([
                'vvaNumVenta' => 202502222, // Cambiado a un valor numérico válido
                'vvaFechaTxn' => DB::raw("GETDATE()"),
                'vvaFechaDoc' => DB::raw("CONVERT(DATE, GETDATE())"),
                'vvaEstado' => 'C', // Estado inicial
                'vvatotalArticulo' => 1,
                'funId' => 470, // Funcionario genérico
                'sucId' => 22, // Sucursal ON LINE
                'cliId' => $data['cliId'], // Se usa el cliId obtenido desde el método store
                'gesId' => $this->getGestionId(),
                'monId' => 2, // Dólar
                'vvaMonto' => $data['monto'],
                'vvaComentario' => 'VENTA GENERADA DESDE LA WEB',
                'facId' => 0,
                'cajId' => 184, // Cajero genérico
                'vntbolsa' => 0, // Cambiado a un valor numérico válido para tipo `bit`
            ]);
    
            // Insertar detalles en cjtDetalleVentaValeRegalo
            DB::table('cjtDetalleVentaValeRegalo')->insert([
                'vvaId' => $vvaId,
                'valId' => $valeId,
                'dvvCantidad' => 1,
                'dvvPrecio' => $data['monto'],
                'dvvImporte' => $data['monto'],
                'monId' => 2, // Dólar
            ]);
    
            // Actualizar el estado del vale en cjtValeDetalle
            DB::table('cjtValeDetalle')
                ->where('valCorrelativo', $data['valCorrelativo'])
                ->update(['valEstado' => $data['valEstado']]);
    
            // Insertar encabezado de pago en vntEncFpago
            $epaId = DB::table('vntEncFpago')->insertGetId([
                'vntTipoDoc' => 'VR',
                'vntNumDoc' => $vvaId,
                'epaMontoPagar' => $data['monto'],
                'epaMontoSaldo' => 0,
                'epaTC' => 6.96, // Tipo de cambio
                'cliId' => $data['cliId'], // Se usa el cliId obtenido desde el método store
                'monId' => 2,
                'sucId' => 22,
                'cajId' => 184,
                'epaEstado' => 'A', // Pagado
                'epaFechaTxn' => DB::raw("GETDATE()"),
                'usrIdModificacion' => 'ON LINE',
                'fechaModificacion' => DB::raw("GETDATE()"),
            ]);
    
            // Insertar transacción de pago en VntFPagoTxn
            DB::table('VntFPagoTxn')->insert([
                'fpaTipo' => 'CJ', // Contado
                'fpaMonto' => $data['monto'],
                'fpaMontoCambio' => 0,
                'fpaMontoCambioOtraMoneda' => 0,
                'fpaTipoCaja' => 'E', // Efectivo
                'monId' => 2, // Dólar
                'epaId' => $epaId,
                'fpafechaTxn' => DB::raw("GETDATE()"),
                'fpaEstado' => 'P', // Pendiente
                'fpaMontoOtraMoneda' => 0,
                'cajId' => 184,
                'fpaEsOriginal' => false,
            ]);
    
            // Insertar en cjtValeHistorico
            DB::table('cjtValeHistorico')->insert([
                'valId' => $valeId,
                'vttEstado' => $data['valEstado'],
                'vttFecha' => DB::raw("GETDATE()"),
                'usrCreacion' => $data['cliId'], // Se registra el cliId como creador
                'vttTransaccionalId' => $epaId,
                'sucId' => 22,
                'vttComentario' => 'GENERADO DESDE LA WEB',
            ]);
    
            DB::commit();
            return $vvaId;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    

    /**
     * Buscar el vacId en base al monto del vale.
     */
    private function getVacIdByAmount(float $monto): int
    {
        $row = DB::table('cjtVale')
            ->where('monId', 1) // monId=1 => Bolivianos
            ->where('vacValor', $monto)
            ->first();

        if (!$row) {
            throw new \Exception("No se encontró en cjtVale un registro con monId=1 y vacValor=$monto");
        }

        return $row->vacId;
    }

    /**
     * Obtener la gestión actual.
     */
    public function getGestionId()
    {
        return DB::table('gntGestion')
            ->where('gesEstado', 'A')
            ->value('gesId');
    }
       /**
     * Obtener un Vale por correlativo.
     */
    public function getValeByCorrelativo(string $valCorrelativo)
    {
        return DB::table('cjtValeDetalle')
            ->where('valCorrelativo', $valCorrelativo)
            ->first();
    }

   /**
 * Actualizar estado de un Vale y registrar en cjtValeHistorico.
 * @param string $valCorrelativo  Ej: 'WEB-GC-1500-XYZ'
 * @param string $nuevoEstado     Ej: 'I' o 'C'
 * @param string|null $motivo
 * @param string|null $fecha      Fecha en formato 'Y-m-d H:i:s'
 * @param string $usuario
 * @return bool
 */ 
public function updateValeStatus(
    string $valCorrelativo,
    string $nuevoEstado,
    ?string $motivo,
    ?string $fecha,
    string $usuario = 'api'
) {
    $vale = $this->getValeByCorrelativo($valCorrelativo);
    if (!$vale) {
        throw new \Exception("No se encontró Vale con correlativo=$valCorrelativo");
    }

    // Fecha: Utilizamos directamente la fecha del servidor SQL
    $fechaDetalle   = DB::raw("GETDATE()");
    $fechaHistorico = DB::raw("GETDATE()");

    // Actualizar cjtValeDetalle
    DB::table('cjtValeDetalle')
        ->where('valId', $vale->valId)
        ->update([
            'valEstado' => $nuevoEstado,
            'valFecha'  => $fechaDetalle,
        ]);
        $transaccionalId = "0";
    // Insertar en cjtValeHistorico
    DB::table('cjtValeHistorico')->insert([
        'valId'       => $vale->valId,
        'vttEstado'   => $nuevoEstado,
        'sucId'             => 22, //  valor fijo para sucId

        'vttTransaccionalId' => $transaccionalId,
        'vttFecha'    => $fechaHistorico,
        'usrCreacion' => $usuario,
    ]);

    return true;
}


    /**
     * Obtener SOLO los vales creados por eCommerce  que cumplan algún criterio.(valOrigen='WEB')
     */
    public function getEcomValesForSync(array $filters = [])
    {
        $query = DB::table('cjtValeDetalle')
            ->where('valOrigen', 'WEB');

        if (!empty($filters['estado'])) {
            $query->where('valEstado', $filters['estado']);
        }

        return $query->get();
    }
}
