<?php

/**
 * Laravel - A PHP Framework For Web Artisans
 *
 * Este archivo permite emular la funcionalidad de mod_rewrite de Apache cuando usas
 * el servidor PHP incorporado.
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Si se solicita un archivo existente en public, el servidor PHP lo sirve directamente
if ($uri !== '/' && file_exists(__DIR__.'/public'.$uri)) {
    return false;
}

require_once __DIR__.'/public/index.php';
