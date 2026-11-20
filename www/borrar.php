<?php
// Borrado a partir de la clave primaria (ID) o de otro campo (DNI, nº de
// colegiado, nº de chip o nombre). Antes de borrar se pide confirmación y se
// avisa si hay registros de otras tablas que dependen de este.
require_once __DIR__ . '/inc/funciones.php';
require_once __DIR__ . '/inc/componentes.php';

$tabla  = tabla_actual();
$op     = 'borrar';
$d      = def($tabla);
$titulo = 'Borrar ' . $d['titulo'];
$alt    = $d['clave_alt'];

$registro = null;
$aviso = null;
$errores = [];

try {
    // 1) Localizar el registro
    if (isset($_POST['id']) || isset($_GET['id'])) {
        $id = $_POST['id'] ?? $_GET['id'];
        $registro = buscar_por_id($tabla, $id);
        if (!$registro) {
            $aviso = 'No existe ' . articulo($tabla, true) . ' con el ID ' . $id . '.';
        }
    } elseif ($alt && trim((string) ($_GET['valor'] ?? '')) !== '') {
        $valor = trim((string) $_GET['valor']);
        $st = conectar()->prepare(sql_select($tabla) . " WHERE LOWER(t.$alt) = LOWER(?)");
        $st->execute([$valor]);
        $registro = $st->fetch() ?: null;
        if (!$registro) {
            $aviso = 'No existe ' . articulo($tabla, true) . ' con ' . mb_strtolower($d['campos'][$alt]['label']) . ' «' . $valor . '».';
        }
    }

    // 2) Borrar tras la confirmación
    if ($registro && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar'])) {
        try {
            $st = conectar()->prepare("DELETE FROM $tabla WHERE id = ?");
            $st->execute([(int) $registro['id']]);
            borrar_imagen($registro[$d['imagen']]);
            redirigir('listado', ['t' => $tabla, 'ok' => 'borrar']);
        } catch (PDOException $ex) {
            $errores['_general'] = mensaje_error_bd($ex, $tabla);
        }
    }

    $dependencias = $registro ? dependencias($tabla, (int) $registro['id']) : [];
    $bloqueado = (bool) array_filter($dependencias, fn($x) => !$x['nulable']);
} catch (PDOException $ex) {
    $error = $ex;
}

require __DIR__ . '/inc/cabecera.php';
avisos($errores);

if (isset($error)) {
    error_conexion($error);
} elseif ($registro) { ?>
    <p><a class="link-secondary" href="<?= e(url('borrar', ['t' => $tabla])) ?>"><i class="bi bi-arrow-left me-1"></i>Elegir otro registro</a></p>

    <?php if ($bloqueado): ?>
        <div class="alert alert-warning">
            <h2 class="h6"><i class="bi bi-lock me-1"></i>No se puede borrar <?= e(articulo($tabla)) ?></h2>
            <p class="mb-2">Hay registros que dependen de él. Bórralos o cámbialos antes:</p>
            <ul class="mb-0">
                <?php foreach ($dependencias as $x): ?>
                    <li><a href="<?= e(url('filtrar', ['t' => $x['tabla'], $x['columna'] => $registro['id']])) ?>">
                        <?= $x['total'] ?> <?= e(mb_strtolower(TABLAS[$x['tabla']]['titulo'])) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php else: ?>
        <div class="alert alert-danger">
            <h2 class="h6 mb-1"><i class="bi bi-exclamation-octagon me-1"></i>¿Seguro que quieres borrar <?= e(articulo($tabla)) ?>?</h2>
            <p class="mb-0">Esta acción no se puede deshacer.
                <?php foreach ($dependencias as $x): ?>
                    Hay <?= $x['total'] ?> registro<?= $x['total'] === 1 ? '' : 's' ?> en «<?= e(TABLAS[$x['tabla']]['titulo']) ?>»
                    que se quedará<?= $x['total'] === 1 ? '' : 'n' ?> sin «<?= e(mb_strtolower(TABLAS[$x['tabla']]['campos'][$x['columna']]['label'])) ?>».
                <?php endforeach; ?>
            </p>
        </div>
    <?php endif; ?>

    <?php ficha($tabla, $registro, false); ?>

    <form method="post" action="borrar.php" class="d-flex flex-wrap gap-2 mt-3">
        <input type="hidden" name="t" value="<?= e($tabla) ?>">
        <input type="hidden" name="id" value="<?= (int) $registro['id'] ?>">
        <button type="submit" name="confirmar" value="1" class="btn btn-danger"<?= $bloqueado ? ' disabled' : '' ?>>
            <i class="bi bi-trash me-1"></i>Sí, borrar definitivamente</button>
        <a class="btn btn-outline-secondary" href="<?= e(url('consulta', ['t' => $tabla, 'id' => $registro['id']])) ?>">No, cancelar</a>
    </form>
<?php } else {
    if ($aviso) {
        echo '<div class="alert alert-warning"><i class="bi bi-question-circle me-2"></i>' . e($aviso) . '</div>';
    }
    ?>
    <p class="text-body-secondary">Indica qué registro quieres borrar. En el paso siguiente podrás revisarlo antes de confirmar.</p>
    <div class="row g-3">
        <div class="col-md-6<?= $alt ? ' col-lg-4' : '' ?>">
            <form method="get" action="borrar.php" class="card card-body h-100 shadow-sm">
                <input type="hidden" name="t" value="<?= e($tabla) ?>">
                <h2 class="h6"><i class="bi bi-hash me-1"></i>Por ID (clave primaria)</h2>
                <label class="form-label" for="b_id">ID</label>
                <input class="form-control mb-3" type="number" min="1" step="1" id="b_id" name="id" required>
                <button class="btn btn-outline-danger mt-auto" type="submit">Buscar para borrar</button>
            </form>
        </div>
        <?php if ($alt): ?>
            <div class="col-md-6 col-lg-4">
                <form method="get" action="borrar.php" class="card card-body h-100 shadow-sm">
                    <input type="hidden" name="t" value="<?= e($tabla) ?>">
                    <h2 class="h6"><i class="bi bi-key me-1"></i>Por <?= e(mb_strtolower($d['campos'][$alt]['label'])) ?></h2>
                    <label class="form-label" for="b_valor"><?= e($d['campos'][$alt]['label']) ?> exacto</label>
                    <input class="form-control mb-3" type="text" id="b_valor" name="valor" required>
                    <button class="btn btn-outline-danger mt-auto" type="submit">Buscar para borrar</button>
                </form>
            </div>
        <?php endif; ?>
        <div class="col-md-12<?= $alt ? ' col-lg-4' : ' col-md-6' ?>">
            <form method="get" action="borrar.php" class="card card-body h-100 shadow-sm">
                <input type="hidden" name="t" value="<?= e($tabla) ?>">
                <h2 class="h6"><i class="bi bi-list-ul me-1"></i>Eligiéndolo de la lista</h2>
                <label class="form-label" for="b_lista"><?= e(ucfirst($d['singular'])) ?></label>
                <select class="form-select mb-3" id="b_lista" name="id" required>
                    <option value="">Elige un registro</option>
                    <?php try { foreach (opciones($tabla) as $valor => $texto): ?>
                        <option value="<?= $valor ?>"><?= e($texto) ?></option>
                    <?php endforeach; } catch (PDOException $ex) {} ?>
                </select>
                <button class="btn btn-outline-danger mt-auto" type="submit">Buscar para borrar</button>
            </form>
        </div>
    </div>
<?php }

require __DIR__ . '/inc/pie.php';
