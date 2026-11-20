<?php
// Configuración de la conexión a la base de datos.
//
// - En local (Docker / XAMPP) se usa MySQL con los valores del .env (MYSQL_*)
//   o, si no existen, con los valores por defecto de abajo.
// - En Vercel se usa PostgreSQL de Supabase con las variables DB_*
//   configuradas en el panel de Vercel (DB_DRIVER=pgsql).

date_default_timezone_set('Europe/Madrid');

function env(string $nombre, string $porDefecto = ''): string
{
    $valor = getenv($nombre);
    return ($valor === false || $valor === '') ? $porDefecto : $valor;
}

// La integración de Supabase con Vercel crea POSTGRES_URL (pooler de Supabase,
// puerto 6543). Si existe y no se han definido las DB_*, se usa esa conexión.
$pg = env('POSTGRES_URL') !== '' && env('DB_HOST') === '' ? parse_url(env('POSTGRES_URL')) : null;

define('DB_DRIVER', $pg ? 'pgsql' : env('DB_DRIVER', 'mysql'));          // mysql o pgsql
define('DB_HOST',   $pg['host'] ?? env('DB_HOST', env('MYSQL_HOST', 'localhost')));
define('DB_PORT',   (string) ($pg['port'] ?? env('DB_PORT', DB_DRIVER === 'pgsql' ? '5432' : '3306')));
define('DB_NAME',   isset($pg['path']) ? ltrim($pg['path'], '/') : env('DB_NAME', env('MYSQL_DATABASE', 'clinica_veterinaria')));
define('DB_USER',   isset($pg['user']) ? urldecode($pg['user']) : env('DB_USER', env('MYSQL_USER', 'clinica')));
define('DB_PASS',   isset($pg['pass']) ? urldecode($pg['pass']) : env('DB_PASSWORD', env('MYSQL_PASSWORD', 'clinica')));

// Imágenes subidas desde los formularios.
// - Sin SUPABASE_URL se guardan en la carpeta img/subidas/ del proyecto.
// - En Vercel el disco es de solo lectura, así que se suben a Supabase Storage
//   (bucket público) usando la clave service_role, que solo vive en el servidor.
define('SUPABASE_URL',    rtrim(env('SUPABASE_URL'), '/'));
define('SUPABASE_KEY',    env('SUPABASE_SERVICE_KEY', env('SUPABASE_SECRET_KEY', env('SUPABASE_SERVICE_ROLE_KEY'))));
define('SUPABASE_BUCKET', env('SUPABASE_BUCKET', 'imagenes'));
define('IMG_MAX_BYTES',   2 * 1024 * 1024);              // 2 MB

function conectar(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $opciones = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    if (DB_DRIVER === 'pgsql') {
        // Supabase exige SSL. El pooler en modo transacción (puerto 6543)
        // no admite sentencias preparadas en el servidor, así que PDO las emula.
        $dsn = 'pgsql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';sslmode=' . env('DB_SSLMODE', 'require');
        $opciones[PDO::ATTR_EMULATE_PREPARES] = true;
    } else {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    }

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $opciones);
    return $pdo;
}
