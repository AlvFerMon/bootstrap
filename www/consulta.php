<?php
// Consulta: se busca un registro por un campo relevante y se muestra su ficha.
require_once __DIR__ . '/inc/funciones.php';
require_once __DIR__ . '/inc/componentes.php';

$tabla  = tabla_actual();
$op     = 'consulta';
$d      = def($tabla);
$titulo = 'Consultar ' . $d['titulo'];

$registro = null;
if (isset($_GET['id'])) {
    try {
        $registro = buscar_por_id($tabla, $_GET['id']);
    } catch (PDOException $ex) {
        $error = $ex;
    }
}

require __DIR__ . '/inc/cabecera.php';
avisos();

if (isset($error)) {
    error_conexion($error);
} elseif ($registro) {
    echo '<p><a class="link-secondary" href="' . e(url('consulta', ['t' => $tabla])) . '"><i class="bi bi-arrow-left me-1"></i>Hacer otra consulta</a></p>';
    ficha($tabla, $registro);
} else {
    if (isset($_GET['id'])) {
        echo '<div class="alert alert-warning">No existe ' . e(articulo($tabla, true)) . ' con el ID ' . e($_GET['id']) . '.</div>';
    }
    try {
        $filas = buscador($tabla, 'consulta');
        if ($filas !== null) {
            if (count($filas) === 1) {
                ficha($tabla, $filas[0]);
            } else {
                resultados_busqueda($tabla, $filas, 'consulta');
            }
        }
    } catch (PDOException $ex) {
        error_conexion($ex);
    }
}

require __DIR__ . '/inc/pie.php';
