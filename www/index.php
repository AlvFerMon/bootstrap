<?php
// Página principal: resumen de la clínica y accesos directos a cada tabla.
require_once __DIR__ . '/inc/funciones.php';
require_once __DIR__ . '/inc/componentes.php';

$titulo = 'Inicio';
$totales = [];
$proximas = [];

try {
    foreach (TABLAS as $tabla => $d) {
        $totales[$tabla] = contar($tabla);
    }
    // Próximas citas pendientes
    $st = conectar()->prepare(sql_select('citas') . ' WHERE t.atendida = 0 ORDER BY t.fecha_hora');
    $st->execute();
    $proximas = array_slice($st->fetchAll(), 0, 5);
} catch (PDOException $ex) {
    $error = $ex;
}

$tabla = null;   // la portada no pertenece a ninguna tabla
require __DIR__ . '/inc/cabecera.php';
?>
<section class="hero rounded-4 p-4 p-md-5 mb-4 text-white">
    <h1 class="display-6 fw-bold mb-2">Clínica veterinaria Carlitos &amp; Snoopy</h1>
    <p class="lead mb-4">Gestión de propietarios, mascotas, veterinarios y citas.</p>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-light" href="<?= e(url('alta', ['t' => 'citas'])) ?>"><i class="bi bi-calendar-plus me-1"></i>Nueva cita</a>
        <a class="btn btn-outline-light" href="<?= e(url('alta', ['t' => 'mascotas'])) ?>"><i class="bi bi-plus-circle me-1"></i>Nueva mascota</a>
        <a class="btn btn-outline-light" href="<?= e(url('consulta', ['t' => 'propietarios'])) ?>"><i class="bi bi-search me-1"></i>Buscar propietario</a>
    </div>
</section>

<?php if (isset($error)): error_conexion($error); else: ?>

<h2 class="h5 mb-3">Gestión</h2>
<div class="row g-3 mb-4">
    <?php foreach (TABLAS as $tabla => $d): if ($d['grupo'] !== 'principal') continue; ?>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 shadow-sm tarjeta-tabla">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-body-secondary mb-0"><?= e($d['titulo']) ?></p>
                            <p class="display-6 fw-semibold mb-0"><?= $totales[$tabla] ?></p>
                        </div>
                        <i class="bi <?= $d['icono'] ?> fs-1 text-success opacity-75" aria-hidden="true"></i>
                    </div>
                </div>
                <div class="list-group list-group-flush">
                    <?php foreach (OPERACIONES as $op_ => [$texto, $icono]): ?>
                        <a class="list-group-item list-group-item-action small" href="<?= e(url($op_, ['t' => $tabla])) ?>">
                            <i class="bi <?= $icono ?> me-2 text-success"></i><?= $texto ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <h2 class="h5 mb-3">Próximas citas pendientes</h2>
        <?php if (!$proximas): ?>
            <div class="alert alert-info">No hay citas pendientes.</div>
        <?php else: ?>
            <div class="list-group shadow-sm">
                <?php foreach ($proximas as $c): ?>
                    <a class="list-group-item list-group-item-action d-flex gap-3 align-items-center" href="<?= e(url('consulta', ['t' => 'citas', 'id' => $c['id']])) ?>">
                        <span class="fecha-cita text-center rounded bg-success-subtle text-success-emphasis px-2 py-1">
                            <span class="d-block fw-bold"><?= e(date_create($c['fecha_hora'])->format('d/m')) ?></span>
                            <span class="small"><?= e(date_create($c['fecha_hora'])->format('H:i')) ?></span>
                        </span>
                        <span class="flex-grow-1">
                            <span class="fw-semibold"><?= e($c['motivo']) ?></span><br>
                            <span class="small text-body-secondary"><?= e($c['mascota_id__txt']) ?> · <?= e($c['veterinario_id__txt']) ?></span>
                        </span>
                        <i class="bi bi-chevron-right text-body-secondary"></i>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <div class="col-lg-4">
        <h2 class="h5 mb-3">Catálogos</h2>
        <div class="list-group shadow-sm">
            <?php foreach (TABLAS as $tabla => $d): if ($d['grupo'] !== 'catalogo') continue; ?>
                <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" href="<?= e(url('listado', ['t' => $tabla])) ?>">
                    <span><i class="bi <?= $d['icono'] ?> me-2 text-success"></i><?= e($d['titulo']) ?></span>
                    <span class="badge text-bg-light border"><?= $totales[$tabla] ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php endif;
require __DIR__ . '/inc/pie.php';
