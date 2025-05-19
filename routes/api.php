<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SalesController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\Api\ValeController;


Route::get('/health-check', function () {
    return response()->json(['status' => 'API is working'], 200);
});

Route::middleware(['api'])->group(function () {
    Route::get('/products', [ProductController::class, 'index']);
});

Route::get('/producto', [ProductController::class, 'getProductByCodigoUnicoEndpoint']);

Route::get('/test-products', [ProductController::class, 'test']);
Route::post('/ventas/crear', [SalesController::class, 'createSale']);
Route::get('/prueba', function () {
    return response()->json(['message' => 'API working']);
});
Route::post('/clientes/crear-o-verificar', [ClientController::class, 'createOrVerifyClient']);
Route::get('/producto-detalles', [ProductController::class, 'getProductDetails']);

// Routes for Vales
Route::prefix('vales')->group(function() {
    // 1) Create a vale
    Route::post('/', [ValeController::class, 'store']);
    
    // 2) Get details (and status) of a vale by correlativo
    Route::get('/detalle/{valCorrelativo}', [ValeController::class, 'show']);

    // 3) Update vale status
    Route::put('/{valCorrelativo}/status', [ValeController::class, 'updateStatus']);

    // 4) Endpoint for cron to list ONLY eCommerce vales that changed status or are in a particular state
    Route::get('/sync-ecom', [ValeController::class, 'getEcomValesForSync']);
});