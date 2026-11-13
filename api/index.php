<?php
// Punto de entrada para Vercel: todas las peticiones llegan aquí (ver vercel.json)
// y este archivo ejecuta la página PHP correspondiente de la carpeta www/.
// En local, con Docker, no se usa: Apache sirve www/ directamente.

$base = realpath(__DIR__ . '/../www');

$ruta = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$ruta = trim(rawurldecode($ruta), '/');

if ($ruta === '') {
    $ruta = 'index.php';
}

$archivo = realpath($base . '/' . $ruta);

// Si piden una carpeta, se sirve su index.php
if ($archivo !== false && is_dir($archivo)) {
    $archivo = realpath($archivo . '/index.php');
}

$relativa = $archivo !== false ? substr($archivo, strlen($base) + 1) : '';

$permitido = $archivo !== false
    && str_starts_with($archivo, $base . DIRECTORY_SEPARATOR)   // no salir de www/
    && pathinfo($archivo, PATHINFO_EXTENSION) === 'php'         // solo páginas PHP
    && !str_starts_with($relativa, 'sql');                      // nunca la carpeta sql

if (!$permitido) {
    http_response_code(404);
    echo 'Página no encontrada';
    exit;
}

// Que la página crea que se está ejecutando desde su propia ruta
chdir(dirname($archivo));
$_SERVER['SCRIPT_FILENAME'] = $archivo;
$_SERVER['SCRIPT_NAME'] = '/' . str_replace(DIRECTORY_SEPARATOR, '/', $relativa);
$_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];

require $archivo;
