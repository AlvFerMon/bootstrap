<?php
// Listado sin parametrizar: muestra todos los registros de la tabla.
require_once __DIR__ . '/inc/funciones.php';
require_once __DIR__ . '/inc/componentes.php';

$tabla  = tabla_actual();
$op     = 'listado';
$titulo = def($tabla)['titulo'];

try {
    $filas = todos($tabla);
} catch (PDOException $ex) {
    $error = $ex;
}

require __DIR__ . '/inc/cabecera.php';
avisos();

if (isset($error)) {
    error_conexion($error);
} else { ?>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <p class="mb-0 text-body-secondary"><?= count($filas) ?> registro<?= count($filas) === 1 ? '' : 's' ?> en total.</p>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-success" href="<?= e(url('filtrar', ['t' => $tabla])) ?>"><i class="bi bi-funnel me-1"></i>Filtrar</a>
            <a class="btn btn-success" href="<?= e(url('alta', ['t' => $tabla])) ?>"><i class="bi bi-plus-lg me-1"></i>Nueva alta</a>
        </div>
    </div>
    <?php tabla_registros($tabla, $filas);
}

require __DIR__ . '/inc/pie.php';
