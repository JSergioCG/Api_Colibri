<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'intArticulo'; // Tabla base
    protected $primaryKey = 'artId'; // Clave primaria
    public $timestamps = false; // Deshabilitar timestamps si no están en la tabla

    // Campos que se pueden asignar masivamente
    protected $fillable = [
        'CODIGO_UNICO',
        'CODIGO_SKU',
        'VALOR_DE_ATRIBUTO_TALLAtemp',
        'VALOR_DE_ATRIBUTO_COLORtemp',
        'VALOR_DE_ATRIBUTO_MATERIALtemp',
        'TEMPORADAtemp',
        'TITULO',
        'TIPO_DE_PRODUCTO',
        'DESCRIPCION_CORTA',
        'CATEGORIA_NIVEL_1',
        'CATEGORIA_NIVEL_2',
        'CATEGORIA_NIVEL_3',
        'CATEGORIAS_CONCATENADAS',
        'TEMPORADA',
        'SALE',
        'NOMBRE_DE_ATRIBUTO_COLOR',
        'ATRIBUTO_VISIBLE_COLOR',
        'VALOR_DE_ATRIBUTO_COLOR',
        'ATRIBUTO_COLOR_ES_VARIABLE',
        'NOMBRE_DE_ATRIBUTO_TALLA',
        'ATRIBUTO_VISIBLE_TALLA',
        'VALOR_DE_ATRIBUTO_TALLA',
        'ATRIBUTO_TALLA_VARIABLE',
        'NOMBRE_DE_ATRIBUTO_TAMANIO',
        'ATRIBUTO_VISIBLE_TAMANIO',
        'VALOR_DE_ATRIBUTO_TAMANIO',
        'NOMBRE_DE_ATRIBUTO_TACO',
        'ATRIBUTO_VISIBLE_TACO',
        'VALOR_DE_ATRIBUTO_TACO',
        'NOMBRE_DE_ATRIBUTO_MODELO',
        'ATRIBUTO_VISIBLE_MODELO',
        'VALOR_DE_ATRIBUTO_MODELO',
        'NOMBRE_DE_ATRIBUTO_MATERIAL',
        'ATRIBUTO_VISIBLE_MATERIAL',
        'VALOR_DE_ATRIBUTO_MATERIAL',
        'NOMBRE_DE_ATRIBUTO_MARCA',
        'VALOR_DE_ATRIBUTO_MARCA',
        'PRECIO_NORMAL',
        'PRECIO_DESCUENTO',
        'STOCK',
        'STOCK_MINIMO',
        'IMAGEN_PRINCIPAL',
        'IMAGEN_SECUNDARIA',
        'OTRAS_IMAGENES',
        'COLOR_BASE',
    ];
}
