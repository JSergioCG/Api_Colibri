<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para hacer esta solicitud.
     */
    public function authorize()
    {
        return true; // Cambiar a false si necesitas una lógica de autorización específica.
    }

    /**
     * Reglas de validación para la solicitud.
     */
    public function rules()
    {
        return [
            'sku' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'category' => 'nullable|string|max:255',
        ];
    }
}
