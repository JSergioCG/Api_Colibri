<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    private $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Devuelve todos los productos.
     * Retorna un JSON con un array puro de productos.
     */
    public function index(): JsonResponse
    {
        // getProducts() puede retornar un array o una colección Eloquent.
        $products = $this->productService->getProducts();

        // Si fuese una colección, la convertimos a array. Si ya es array, lo dejamos.
        if (is_object($products) && method_exists($products, 'toArray')) {
            $products = $products->toArray();
        }

        // Devolver directamente el array en la respuesta JSON.
        return response()->json($products);
    }

    /**
     * Devuelve un máximo de 50 registros para pruebas (array puro).
     */
    public function test(Request $request): JsonResponse
    {
        $offset = $request->query('offset', 0);
        $limit = $request->query('limit', 500);
    
        $products = $this->productService->getProducts($offset, $limit);
    
        return response()->json($products);
    }
    
    /**
     * Endpoint para obtener precio, precio descuento y stock de un producto por su código único.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getProductDetails(Request $request): JsonResponse
    {
        $codigoUnico = $request->query('codigo_unico'); // Leer el parámetro de la URL

        if (!$codigoUnico) {
            return response()->json(['error' => 'El parámetro codigo_unico es requerido'], 400);
        }

        try {
            // Llamar al servicio para obtener los datos del producto
            $product = $this->productService->getProductDetailsByCodigoUnico($codigoUnico);

            if (empty($product)) {
                return response()->json(['message' => 'Producto no encontrado'], 404);
            }

            return response()->json($product, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
