<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log; // <-- Importante, la facade de Laravel
use App\Services\MailService; // Import

class SalesService
{
    protected $db;
    protected $mailService;

    public function __construct(MailService $mailService = null)
    {
        // Usa la conexión por defecto
        $this->db = DB::connection();

        // Instancia MailService (o inyección si usas un contenedor)
        $this->mailService = $mailService ?? new MailService();
    }
    public function createSale(array $productos, string $tipoPago, float $montoPagado, int $clienteId)
{
    // 0️⃣ Log inicial
    Log::info('[createSale] Iniciando creación de venta', [
        'productos'   => $productos,
        'tipoPago'    => $tipoPago,
        'montoPagado' => $montoPagado,
        'clienteId'   => $clienteId,
    ]);

    DB::beginTransaction();
    try {
    //
// 1️⃣ Obtener gestión activa (para gesId **y** gesNombre)
//
$gestion = DB::table('gntGestion')
->where('gesEstado', 'A')
->first();

if (! $gestion) {
Log::error('[createSale] No se encontró la gestión activa (gesEstado="A").');
throw new \Exception("No se encontró la gestión activa.");
}

$gesId       = $gestion->gesId;      // el ID interno
$gestionYear = $gestion->gesNombre;  // el año, p.ej. "2025"

Log::info("[createSale] gestión activa obtenida: gesId={$gesId}, gesNombre={$gestionYear}");

//
// 2️⃣ Obtener secuencia para tipo PR usando el **año** (secgestion = gesNombre)
//
$secuencia = $this->db->table('gntSecuencialDoc')
->where('sucId', 22)
->where('secgestion', $gestionYear)   // <-- filtramos por el año, no por gesId
->where('secTipoDoc', 'PR')
->first();

if (! $secuencia) {
Log::error("[createSale] No se encontró secuencial para sucursal=22, año={$gestionYear}, tipo=PR");
throw new \Exception("No se encontró la secuencia para sucursal 22 en la gestión {$gestionYear} (tipo PR).");
}

//
// 3️⃣ Generar número de venta
//
$vntNumVenta = "{$gestionYear}22{$secuencia->secId}";
Log::info("[createSale] vntNumVenta generado: {$vntNumVenta}");

        //
        // 4️⃣ Obtener tipo de cambio
        //
        $tipoCambio = $this->db->table('gntTipoCambio')
            ->where('monId', 2)
            ->where('tcafecha', '<=', Carbon::today())
            ->orderBy('tcafecha', 'desc')
            ->first();

        if (! $tipoCambio) {
            Log::error('[createSale] No se encontró tipo de cambio para monId=2');
            throw new \Exception("No se encontró tipo de cambio para la fecha actual.");
        }
        $tcaId    = $tipoCambio->tcaId;
        $tcaValor = $tipoCambio->tcavalor;
        Log::info('[createSale] Tipo de cambio obtenido', [
            'tcaId'    => $tcaId,
            'tcavalor' => $tcaValor,
        ]);

        //
        // 5️⃣ Preparar fechas
        //
        $fechaTxn       = Carbon::now()->format('Y-m-d H:i:s');
        $fechaOperacion = Carbon::today()->format('Y-m-d');
        $fechaValida    = Carbon::today()->format('Y-m-d');
        Log::info('[createSale] Fechas preparadas', [
            'fechaTxn'        => $fechaTxn,
            'fechaOperacion'  => $fechaOperacion,
            'fechaValida'     => $fechaValida,
        ]);

        //
        // 6️⃣ Insertar encabezado vntTxn
        //
        $totalAPagar = $this->calcularTotalAPagar($productos); //Precio de solo productos (sin contar envio)
        $vntId = $this->db->table('vntTxn')->insertGetId([
            'gesId'             => $gesId,
            'sucId'             => 22,
            'vntNumVenta'       => $vntNumVenta,
            'vntfechaTxn'       => DB::raw("CONVERT(datetime, '{$fechaTxn}', 121)"),
            'vntfechaOperacion' => DB::raw("CONVERT(date, '{$fechaOperacion}', 121)"),
            'vntEstado'         => 'A',
            'cliId'             => $clienteId,
            'funId'             => 470,
            'monId'             => 2,
            'tcaId'             => $tcaValor,
            'vntTipoDoc'        => 'PR',
            'vnttotalArticulo'  => count($productos),
            'vnttotalDescuento' => $totalAPagar / $tcaValor, //$montoPagado,
            'vnttotalReserva'   => 0,
            'vnttotalTxn'       => $totalAPagar / $tcaValor,
            'vntComentario'     => 'VENTA ON LINE',
            'vntNumSalida'      => $vntNumVenta,
            // ── Nuevos campos obligatorios ──
            'vnt_tipo_v'        => 'WB',
            'vnt_razon'         => 'SIN NOMBRE',
            'vnt_nit'           => 0,
            'facId'             => 0,
            'vntIdReferencia'   => 1,
            'NombreEntrega'     => ' ',
            // ────────────────────────────────
            'vntfechaValida'    => DB::raw("CONVERT(date, '{$fechaValida}', 121)"),
            'vntmontoFacturado' => 0,
            'cajId'             => 184,
            'vnttipodesc'       => 'T',
            'vntsucIdBolsa'     => 22,
            'vntbolsa'          => 0,
        ]);
        Log::info("[createSale] vntTxn insertado con vntId={$vntId}");

        //
        // 7️⃣ Insertar detalle (vntDetalleTxn) y ajustar stock
        //
        Log::info('[createSale] Recorriendo productos para crear vntDetalleTxn');
        $dvnIds = [];
        foreach ($productos as $producto) {
            Log::info('[createSale] Procesando SKU: '.$producto['sku'], [
                'precio'   => $producto['precio'],
                'cantidad' => $producto['cantidad'],
            ]);

            $cantidadNecesaria = $producto['cantidad'];

            // Buscar lotes disponibles en almacén tipo 'I'
            $lotes = $this->db->table('intExistencia')
                ->join('intAlmacen','intAlmacen.almId','=','intExistencia.almId')
                ->where('intExistencia.artId',   $producto['sku'])
                ->where('intExistencia.extCantidad','>', 0)
                ->where('intAlmacen.almTipoAlmacen','I')
                ->orderByRaw("
                    CASE 
                        WHEN intAlmacen.almId = 11 THEN 1
                        WHEN intAlmacen.almId = 0  THEN 2
                        WHEN intAlmacen.almId = 1  THEN 3
                        WHEN intAlmacen.almId = 19 THEN 4
                        WHEN intAlmacen.almId = 18 THEN 5
                        WHEN intAlmacen.almId = 15 THEN 6
                        WHEN intAlmacen.almId = 14 THEN 7
                        ELSE 9999
                    END
                ")
                ->select('intExistencia.*')
                ->get();

            if ($lotes->isEmpty()) {
                Log::error("[createSale] No hay stock en almacenes tipo I para SKU={$producto['sku']}");
                throw new \Exception("No hay stock para el artículo: {$producto['sku']}");
            }

            foreach ($lotes as $fila) {
                if ($cantidadNecesaria <= 0) {
                    break;
                }

                $aConsumir = min($fila->extCantidad, $cantidadNecesaria);
                $importe   = $producto['precio'] * $aConsumir;

                Log::info("[createSale] Insertando vntDetalleTxn parcial SKU={$producto['sku']}, almId={$fila->almId}, lotId={$fila->lotId}", [
                    'cantidad' => $aConsumir,
                    'importe'  => $importe,
                ]);

                $dvnId = $this->db->table('vntDetalleTxn')->insertGetId([
                    'vntId'             => $vntId,
                    'artId'             => $producto['sku'],
                    'lotId'             => $fila->lotId,
                    'dvnCantidad'       => $aConsumir,
                    'dvnprecioArticulo' => $producto['precio'] / $tcaValor,
                    'camId'             => 1,
                    'dvnprecioNormal'   => $producto['precio'] / $tcaValor,
                    'dtoId'             => 0,
                    'dvnImporte'        => $importe / $tcaValor,
                    'funId'             => 470,
                    'dvnDevuelto'       => 0,
                ]);
                Log::info("[createSale] vntDetalleTxn insertado dvnId={$dvnId}");

                $dvnIds[] = [
                    'dvnId'    => $dvnId,
                    'artId'    => $producto['sku'],
                    'lotId'    => $fila->lotId,
                    'cantidad' => $aConsumir,
                ];

                // Decrementar stock e informar por correo
                $this->db->table('intExistencia')
                    ->where('extId', $fila->extId)
                    ->decrement('extCantidad', $aConsumir);
                
                    // Luego incrementa los vendidos
                $this->db->table('intExistencia')
                    ->where('extId', $fila->extId)
                    ->increment('extVendidos', $aConsumir);
                    
                $this->mailService->sendStockConsumptionEmail($fila->almId, $producto['sku'], $aConsumir);

                $cantidadNecesaria -= $aConsumir;
            }

            if ($cantidadNecesaria > 0) {
                Log::error("[createSale] Stock insuficiente para SKU={$producto['sku']}. Faltan {$cantidadNecesaria}");
                throw new \Exception("No hay stock suficiente para el artículo: {$producto['sku']}");
            }
        }

        //
        // 8️⃣ Insertar encabezado de pago (vntEncFpago)
        //
        Log::info('[createSale] Insertando vntEncFpago');
        $epaId = $this->db->table('vntEncFpago')->insertGetId([
            'vntTipoDoc'        => 'PR',
            'vntNumDoc'         => $vntId,
            'epaMontoPagar'     => $totalAPagar / $tcaValor, // $montoPagado
            'epaMontoSaldo'     => 0,
            'epaTC'             => $tcaValor,
            'cliId'             => $clienteId,
            'monId'             => 2,
            'sucId'             => 22,
            'cajId'             => 184,
            'facId'             => null,
            'epaEstado'         => 'A',
            'epaFechaTxn'       => DB::raw("CONVERT(datetime, '{$fechaTxn}', 121)"),
            'usrIdModificacion' => 'ON LINE',
            'fechaModificacion' => DB::raw("CONVERT(datetime, '{$fechaTxn}', 121)"),
        ]);
        Log::info("[createSale] vntEncFpago insertado epaId={$epaId}");

        //
        // 9️⃣ Insertar forma de pago (VntFPagoTxn)
        //
        Log::info('[createSale] Insertando VntFPagoTxn');
        $this->db->table('VntFPagoTxn')->insert([
            'fpaTipo'                   => 'CJ',
            'fpaMonto'                  => $totalAPagar, //$montoPagado,
            'fpaMontoCambio'            => 0,
            'fpaMontoCambioOtraMoneda'  => 0,
            'fpaTipoCaja'               => $tipoPago,
            'monId'                     => 1,
            'fpaTipoCtaCte'             => '',
            'epaId'                     => $epaId,
            'fpafechaTxn'               => DB::raw("CONVERT(datetime, '{$fechaTxn}', 121)"),
            'fpaEstado'                 => 'A',
            'fpaMontoOtraMoneda'        => $totalAPagar / $tcaValor, // $montoPagado,
            'cajId'                     => 184,
            'fpaEsOriginal'             => 0,
        ]);
        Log::info('[createSale] VntFPagoTxn insertado');

        //
        // 🔟 Insertar Inventario Txn + detalle
        //
        Log::info('[createSale] Insertando IntInventarioTxn');
        $intId = $this->db->table('IntInventarioTxn')->insertGetId([
            'intFechaTxn'       => DB::raw("CONVERT(datetime, '{$fechaTxn}', 121)"),
            'intFechaOperacion' => DB::raw("CONVERT(date, '{$fechaOperacion}', 121)"),
            'intEstado'         => 'A',
            'intTipoTxn'        => 'S',
            'intTipoOperacion'  => 'VE',
            'icmId'             => 9,
            'intComentario'     => 'VENTA ON LINE',
            'modId'             => 2,
            'refId'             => $vntId ,
            'entId'             => $clienteId,
            'almId'             => 22,
            'gesId'             => $gesId,
            'monId'             => 2,
            'intTc'             => $tcaValor,
            'intTipoent'        => 'C',
            'intNumInventario'  => 0,
        ]);
        Log::info("[createSale] IntInventarioTxn insertado intId={$intId}");

        Log::info('[createSale] Recorriendo dvnIds para detalle de inventario');
        foreach ($dvnIds as $dDetalle) {
            Log::info("[createSale] Insertando IntInventarioDetalleTxn para dvnId={$dDetalle['dvnId']}");
            $art = $this->db->table('IntArticulo')
                ->where('artId', $dDetalle['artId'])
                ->first();
            $ideCosto = $art->artCifPromedio;
            $uniId    = $art->uniId;

            $existencia = $this->db->table('intExistencia')
                ->where('artId', $dDetalle['artId'])
                ->where('lotId', $dDetalle['lotId'])
                ->first();
            $codigoBarras = $existencia->lotCodigoBarras ?? null;

            $this->db->table('IntInventarioDetalleTxn')->insert([
                'intId'           => $intId,
                'artId'           => $dDetalle['artId'],
                'ideCosto'        => $ideCosto,
                'ideCantidad'     => $dDetalle['cantidad'],
                'lotId'           => $dDetalle['lotId'],
                'uniId'           => $uniId,
                'dvnId'           => $dDetalle['dvnId'],
                'ideCodigoBarras' => $codigoBarras,
            ]);
        }

        DB::commit();
        Log::info("[createSale] Venta creada exitosamente con vntId={$vntId}");

        return [
            'vntId'   => $vntId,
            'mensaje' => 'Venta creada exitosamente',
        ];
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('[createSale] Ocurrió una excepción: '.$e->getMessage(), [
            'trace' => $e->getTraceAsString(),
        ]);
        $this->mailService->sendErrorEmail($e->getMessage(), $e->getTraceAsString());
        throw $e;
    }
}

    /**
     * Calcula el total a pagar sumando el precio de cada producto por su cantidad.
     *
     * @param array $productos Array de productos con claves 'precio' y 'cantidad'.
     * @return float Total a pagar.
     */
    private function calcularTotalAPagar(array $productos): float
    {
        return array_reduce($productos, function($carry, $producto) {
            return $carry + ($producto['precio'] * $producto['cantidad']);
        }, 0.0);
    }
    
}
