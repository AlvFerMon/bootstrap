<?php
// Configuración de la conexión a la base de datos.
//
// - En local (Docker) se usa MySQL con los valores del .env (MYSQL_*).
// - En Vercel se usa PostgreSQL de Supabase con las variables DB_*
//   configuradas en el panel de Vercel (DB_DRIVER=pgsql).

function env(string $nombre, string $porDefecto = ''): string
{
    $valor = getenv($nombre);
    return ($valor === false || $valor === '') ? $porDefecto : $valor;
}

define('DB_DRIVER', env('DB_DRIVER', 'mysql'));          // mysql o pgsql
define('DB_HOST',   env('DB_HOST', env('MYSQL_HOST', 'localhost')));
define('DB_PORT',   env('DB_PORT', DB_DRIVER === 'pgsql' ? '5432' : '3306'));
define('DB_NAME',   env('DB_NAME', env('MYSQL_DATABASE', 'clinica_veterinaria')));
define('DB_USER',   env('DB_USER', env('MYSQL_USER', 'clinica')));
define('DB_PASS',   env('DB_PASSWORD', env('MYSQL_PASSWORD', 'clinica')));

function conectar(): PDO
{
    $opciones = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    if (DB_DRIVER === 'pgsql') {
        // Supabase exige SSL. El pooler en modo transacción (puerto 6543)
        // no admite sentencias preparadas en el servidor, así que PDO las emula.
        $dsn = 'pgsql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';sslmode=require';
        $opciones[PDO::ATTR_EMULATE_PREPARES] = true;
    } else {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    }

    return new PDO($dsn, DB_USER, DB_PASS, $opciones);
}
