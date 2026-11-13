<?php
// Configuración de la conexión a la base de datos.
// Con Docker los valores vienen del .env; en el alojamiento web se usan los de respaldo.
define('DB_HOST', getenv('MYSQL_HOST') ?: 'localhost');
define('DB_NAME', getenv('MYSQL_DATABASE') ?: 'clinica_veterinaria');
define('DB_USER', getenv('MYSQL_USER') ?: 'clinica');
define('DB_PASS', getenv('MYSQL_PASSWORD') ?: 'clinica');

function conectar(): PDO
{
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    return new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}
