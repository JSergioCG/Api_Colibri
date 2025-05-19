<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class ProductService
{

       /**
     * Devuelve todos los productos de la base de datos con todos los atributos.
     *
     * @return array
     */
    public function getProducts(int $offset = 0, int $limit = 500): array
    {
    //     $query = <<<SQL
    //     SELECT 
    //         art.artId AS CODIGO_UNICO,
    //         -- CAMBIO: Se agrega el valor del color para segmentar el SKU por cada color
    //         CONCAT(art.artNombre, '-', maf.mafiD, '-', UPPER(cof.cofNombre)) AS CODIGO_SKU,
    //         -- TITULO se forma ahora con SUBCATEGORIA, MATERIAL y COLOR
    //         CONCAT(
    //             UPPER(sub.subNombre), ' ',
    //             UPPER(maf.mafNombre), ' ',
    //             UPPER(cof.cofNombre)
    //         ) AS TITULO,
    //         CASE 
    //             WHEN (
    //                 SELECT COUNT(*) 
    //                 FROM intArticulo 
    //                 WHERE intArticulo.artNombre = art.artNombre
    //             ) > 1 THEN 'variable'
    //             ELSE 'simple'
    //         END AS TIPO_DE_PRODUCTO,
    //         '' AS DESCRIPCION_CORTA,
    //         cat.catNombre AS CATEGORIA_NIVEL_1,
    //         sub.subNombre AS CATEGORIA_NIVEL_2,
    //         '' AS CATEGORIA_NIVEL_3,
    //         CONCAT(cat.catNombre, '>', sub.subNombre) AS CATEGORIAS_CONCATENADAS,
    //         (
    //             SELECT TOP 1 CONCAT(stem.temNombre, ' ', sped.pedGestion)
    //             FROM cmtPedidoTxn sped
    //             INNER JOIN cmtPedidoDetalleTxn spedet ON spedet.pedId = sped.pedid
    //             INNER JOIN gntTemporada stem ON stem.temId = sped.temId
    //             INNER JOIN intArticulo sart ON spedet.artId = sart.artId
    //             WHERE sart.artId = art.artId
    //             ORDER BY sped.pedfechatxn DESC
    //         ) AS TEMPORADA,
    //         -- Atributo COLOR: Visible
    //         'COLOR' AS NOMBRE_DE_ATRIBUTO_COLOR,
    //         'yes' AS ATRIBUTO_VISIBLE_COLOR,
    //         cof.cofNombre AS VALOR_DE_ATRIBUTO_COLOR,
    //         -- Atributo COLORBASE (invisible)
    //         'COLORBASE' AS NOMBRE_DE_ATRIBUTO_COLORBASE,
    //         'no' AS ATRIBUTO_VISIBLE_COLORBASE,
    //         col.colNombre AS VALOR_DE_ATRIBUTO_COLORBASE,
    //         -- Atributo TALLA: No visible en el producto padre pero se usa en variaciones
    //         'TALLA' AS NOMBRE_DE_ATRIBUTO_TALLA,
    //         'no' AS ATRIBUTO_VISIBLE_TALLA,
    //         'yes' AS ATRIBUTO_TALLA_ES_VARIABLE,  -- Forzamos que se use para variaciones.
    //         tam.tamNombre AS VALOR_DE_ATRIBUTO_TALLA,
    //         -- Atributo TAMAÑO: No visible
    //         'TAMAÑO' AS NOMBRE_DE_ATRIBUTO_TAMANIO,
    //         'no' AS ATRIBUTO_VISIBLE_TAMANIO,
    //         tam.tamNombre AS VALOR_DE_ATRIBUTO_TAMANIO,
    //         -- Atributo TACO: Visible solo para productos que lo tengan
    //         'TACO' AS NOMBRE_DE_ATRIBUTO_TACO,
    //         CASE 
    //           WHEN tac.tacId = 0 THEN 'no'
    //           ELSE 'yes'
    //         END AS ATRIBUTO_VISIBLE_TACO,
    //         CASE 
    //           WHEN tac.tacId = 0 THEN ''
    //           WHEN tac.tacNombre = 'SB' THEN 'BAJO'
    //           WHEN tac.tacNombre = 'SM' THEN 'MEDIO'
    //           WHEN tac.tacNombre = 'SA' THEN 'ALTO'
    //           ELSE tac.tacNombre
    //         END AS VALOR_DE_ATRIBUTO_TACO,
    //         -- Atributo MODELO: No visible
    //         'MODELO' AS NOMBRE_DE_ATRIBUTO_MODELO,
    //         'no' AS ATRIBUTO_VISIBLE_MODELO,
    //         mode.modNombre AS VALOR_DE_ATRIBUTO_MODELO,
    //         -- Atributo MATERIAL: Visible
    //         'MATERIAL' AS NOMBRE_DE_ATRIBUTO_MATERIAL,
    //         'yes' AS ATRIBUTO_VISIBLE_MATERIAL,
    //         maf.mafNombre AS VALOR_DE_ATRIBUTO_MATERIAL,
    //         -- Atributo MARCA: Visible
    //         'MARCA' AS NOMBRE_DE_ATRIBUTO_MARCA,
    //         'yes' AS ATRIBUTO_VISIBLE_MARCA,
    //         pro.pveNombre AS VALOR_DE_ATRIBUTO_MARCA,
    //         (
    //             SELECT ROUND((subc_art.caaPrecio * subtc.tcavalor), 0)
    //             FROM dbo.intArticulo AS subart
    //             INNER JOIN dbo.vntCampañaArticulo AS subc_art 
    //                 ON subart.artId = subc_art.artId 
    //                 AND subart.artId = art.artId 
    //                 AND subc_art.camId = 1
    //             LEFT OUTER JOIN dbo.gntTipoCambio AS subtc 
    //             ON subtc.tcafecha = CONVERT(date, GETDATE())
    //             GROUP BY subart.artId, subtc.tcavalor, subc_art.caaPrecio
    //         ) AS PRECIO_NORMAL,
    //         (
    //             SELECT ROUND((subc_oferta.caaPrecio * subtc.tcavalor), 0)
    //             FROM dbo.intArticulo AS subart
    //             LEFT OUTER JOIN dbo.vntCampañaArticulo AS subc_oferta 
    //                 ON subart.artId = subc_oferta.artId
    //                 AND subc_oferta.camId = (
    //                     SELECT TOP (1) camId
    //                     FROM dbo.vntCampaña 
    //                     WHERE camEstado = 'A' AND camId NOT IN (1,2,10144)
    //                 )
    //             LEFT OUTER JOIN dbo.gntTipoCambio AS subtc 
    //             ON subtc.tcafecha = CONVERT(date, GETDATE())
    //             WHERE subart.artId = art.artId
    //             GROUP BY subart.artId, subc_oferta.caaPrecio, subtc.tcavalor
    //         ) AS PRECIO_DESCUENTO,
    //         (
    //             SELECT SUM(subexi.extCantidad + subexi.exiprestamo + subexi.extenviados)
    //             FROM dbo.intArticulo AS subart
    //             INNER JOIN dbo.intExistencia AS subexi 
    //                 ON subexi.artId = subart.artId
    //             WHERE subart.artId = art.artId
    //             GROUP BY subart.artId
    //         ) AS STOCK,
    //         1 AS STOCK_MINIMO,
    //         CONCAT(
    //             art.artNombre, '-', 
    //             UPPER(dbo.EliminarCaracteresEspeciales(maf.mafnombre)), '-',
    //             UPPER(dbo.EliminarCaracteresEspeciales(cof.cofNombre)), '-1.jpg'
    //         ) AS IMAGEN_PRINCIPAL,
    //         CONCAT(
    //             art.artNombre, '-', 
    //             UPPER(dbo.EliminarCaracteresEspeciales(maf.mafnombre)), '-',
    //             UPPER(dbo.EliminarCaracteresEspeciales(cof.cofNombre)), '-2.jpg'
    //         ) AS IMAGEN_SECUNDARIA,
    //         CONCAT(
    //             art.artNombre, '-', 
    //             UPPER(dbo.EliminarCaracteresEspeciales(maf.mafnombre)), '-', 
    //             UPPER(dbo.EliminarCaracteresEspeciales(cof.cofNombre)), '-3.jpg|',
    //             art.artNombre, '-', 
    //             UPPER(dbo.EliminarCaracteresEspeciales(maf.mafnombre)), '-', 
    //             UPPER(dbo.EliminarCaracteresEspeciales(cof.cofNombre)), '-4.jpg|',
    //             art.artNombre, '-', 
    //             UPPER(dbo.EliminarCaracteresEspeciales(maf.mafnombre)), '-', 
    //             UPPER(dbo.EliminarCaracteresEspeciales(cof.cofNombre)), '-5.jpg|',
    //             art.artNombre, '-', 
    //             UPPER(dbo.EliminarCaracteresEspeciales(maf.mafnombre)), '-', 
    //             UPPER(dbo.EliminarCaracteresEspeciales(cof.cofNombre)), '-6.jpg|'
    //         ) AS OTRAS_IMAGENES,
    //         col.colNombre AS COLOR_BASE
    
    //     FROM dbo.intArticulo AS art
    //     LEFT OUTER JOIN dbo.intModelo AS mode ON mode.modId = art.modId
    //     LEFT OUTER JOIN dbo.intCategoria AS cat 
    //         ON cat.catId = mode.catId 
    //         AND cat.catId NOT IN (6,8,9,11,12,99)
    //     LEFT OUTER JOIN dbo.intSubCategoria AS sub 
    //         ON sub.subId = mode.subId 
    //         AND sub.subId NOT IN (
    //             0,7,13,14,18,50,51,55,57,58,59,60,61,65,66,67,69,70,71,72,
    //             73,74,75,76,77,78,80,81,82,84,85,87,88,89,91,92,93,94,95,96,97,98,99,100,103
    //         )
    //     LEFT OUTER JOIN dbo.intColorFantasia AS cof ON cof.cofId = art.cofId
    //     LEFT OUTER JOIN dbo.intColor AS col ON col.colId = cof.colId
    //     LEFT OUTER JOIN dbo.intMaterialFantasia AS maf ON maf.mafId = art.mafId
    //     LEFT OUTER JOIN dbo.intMaterial AS mat ON mat.matId = maf.matId
    //     LEFT OUTER JOIN dbo.intTamaño AS tam ON tam.tamId = art.tamId
    //     LEFT OUTER JOIN dbo.gntProveedor AS pro ON pro.pveId = art.pveId
    //     LEFT OUTER JOIN dbo.intTaco AS tac ON tac.tacId = art.tacId
    //     WHERE (
    //         SELECT SUM(subexi.extCantidad + subexi.exiprestamo + subexi.extenviados)
    //         FROM dbo.intArticulo AS subart
    //         INNER JOIN dbo.intExistencia AS subexi 
    //             ON subexi.artId = subart.artId
    //         WHERE subart.artId = art.artId
    //         GROUP BY subart.artId
    //     ) > 0
    //     ORDER BY CODIGO_SKU, VALOR_DE_ATRIBUTO_MATERIAL, VALOR_DE_ATRIBUTO_TALLA
    //     OFFSET :offset ROWS FETCH NEXT :limit ROWS ONLY;
    // SQL;

        $query = "EXEC sp_GetProductsForExportAPI @Offset = :offset, @Limit = :limit";
        return DB::select($query, ['offset' => $offset, 'limit' => $limit]);
    }
    


   

    /**
 * Devuelve la información de un producto basado en su código único.
 *
 * @param string $codigoUnico
 * @return JsonResponse
 */
public function getProductDetailsByCodigoUnico($codigoUnico)
{
    try {
        $query = <<<SQL
            SELECT 
                (
                    SELECT ROUND((subc_art.caaPrecio * subtc.tcavalor), 0) AS PrecioVentaBS
                    FROM dbo.intArticulo AS subart
                    INNER JOIN dbo.vntCampañaArticulo AS subc_art ON subart.artId = subc_art.artId
                        AND subart.artId = art.artId
                        AND subc_art.camId = 1
                    LEFT OUTER JOIN dbo.vntCampañaArticulo AS subc_oferta ON subart.artId = subc_oferta.artId
                        AND subc_oferta.camId = (
                            SELECT TOP (1) camId
                            FROM dbo.vntCampaña AS c
                            WHERE (camEstado = 'A')
                                AND (camId NOT IN (1,2,10144))
                        )
                    LEFT OUTER JOIN dbo.gntTipoCambio AS subtc ON subtc.tcafecha = CONVERT(date, GETDATE())
                    GROUP BY subc_art.caaPrecio, subtc.tcavalor
                ) AS PRECIO_NORMAL,
                (
                    SELECT ROUND((subc_oferta.caaPrecio * subtc.tcavalor), 0) AS PrecioOfertaBS
                    FROM dbo.intArticulo AS subart
                    INNER JOIN dbo.vntCampañaArticulo AS subc_art ON subart.artId = subc_art.artId
                        AND subart.artId = art.artId
                        AND subc_art.camId = 1
                    LEFT OUTER JOIN dbo.vntCampañaArticulo AS subc_oferta ON subart.artId = subc_oferta.artId
                        AND subc_oferta.camId = (
                            SELECT TOP (1) camId
                            FROM dbo.vntCampaña AS c
                            WHERE (camEstado = 'A')
                                AND (camId NOT IN (1,2,10144))
                        )
                    LEFT OUTER JOIN dbo.gntTipoCambio AS subtc ON subtc.tcafecha = CONVERT(date, GETDATE())
                    GROUP BY subc_oferta.caaPrecio, subtc.tcavalor
                ) AS PRECIO_DESCUENTO,
                (
                    SELECT SUM(subexi.extCantidad + subexi.exiprestamo + subexi.extenviados)
                    FROM dbo.intArticulo AS subart
                    INNER JOIN dbo.intExistencia AS subexi ON subexi.artId = subart.artId
                    WHERE subart.artId = art.artId
                    GROUP BY subart.artId
                ) AS STOCK
            FROM dbo.intArticulo AS art
            WHERE art.artId = :codigoUnico;
        SQL;

        $product = DB::select($query, ['codigoUnico' => $codigoUnico]);

        return $product ? $product[0] : null; // Devolver el primer resultado si existe
    } catch (\Exception $e) {
        throw $e; // Propagar la excepción al controlador
    }
}

    
}