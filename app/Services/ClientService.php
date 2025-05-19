<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Exception;
class ClientService
{
    protected $db;

    public function __construct()
    {
        // Usa la conexión por defecto (sqlsrv)
        $this->db = DB::connection(); 
    }

    /**
     * Verificar si un cliente existe por cliCarnet, o crear uno nuevo.
     *
     * @param string $nombre
     * @param string $celular
     * @param string $email1
     * @param string $carnet
     * @return int $cliId
     */
   
public function verifyOrCreateClient($nombre, $celular, $email1, $carnet)
{
    try {
        // Verificar si el cliente existe por cliCarnet
        $cliente = $this->db->table('gntCliente')
            ->where('cliCarnet', $carnet)
            ->first();

        if ($cliente) {
            Log::info('Cliente encontrado:', ['cliId' => $cliente->cliId]);
            return $cliente->cliId;
        }

        // Si no existe, crear el cliente
        $cliId = $this->db->table('gntCliente')->insertGetId([
            'cliNombre' => $nombre,
            'cliEstado' => 'A',
            'cliCelular' => $celular,
            'cliEmail1' => $email1,
            'cliCarnet' => $carnet,
            'usrIdCreacion' => 'ON LINE',
            'dtoId'=> '0',
        ]);

        Log::info('Cliente creado exitosamente:', ['cliId' => $cliId]);
        return $cliId;

    } catch (Exception $e) {
        Log::error('Error en verifyOrCreateClient:', ['error' => $e->getMessage()]);
        throw $e; // Re-lanzar la excepción
    }
}
}