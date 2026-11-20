<?php
// Listado parametrizado: muestra los registros que cumplen las condiciones
// introducidas en el formulario. Usa GET para que la búsqueda se pueda
// guardar o compartir como enlace.
require_once __DIR__ . '/inc/funciones.php';
require_once __DIR__ . '/inc/componentes.php';

$tabla  = tabla_actual();
$op     = 'filtrar';
$d      = def($tabla);
$titulo = $d['titulo'] . ': listado con filtros';

$where = [];
$params = [];
$activos = 0;

foreach ($d['filtros'] as $nombre => $tipo) {
    $campo = $d['campos'][$nombre];
    $col = "t.$nombre";

    switch ($tipo) {
        case 'contiene':
            $v = trim((string) ($_GET[$nombre] ?? ''));
            if ($v !== '') {
                $where[] = "LOWER($col) LIKE LOWER(?)";
                $params[] = "%$v%";
            }
            break;

        case 'igual':
            $v = (string) ($_GET[$nombre] ?? '');
            if (ctype_digit($v)) {
                $where[] = "$col = ?";
                $params[] = (int) $v;
            } elseif ($v === 'nulo') {
                $where[] = "$col IS NULL";
            }
            break;

        case 'bool':
            $v = (string) ($_GET[$nombre] ?? '');
            if ($v === '0' || $v === '1') {
                $where[] = "$col = ?";
                $params[] = (int) $v;
            }
            break;

        case 'rango':
            $esFecha = in_array($campo['tipo'], ['fecha', 'fechahora'], true);
            $expr = $campo['tipo'] === 'fechahora' ? "CAST($col AS DATE)" : $col;
            foreach (['desde' => '>=', 'hasta' => '<='] as $sufijo => $operador) {
                $v = str_replace(',', '.', trim((string) ($_GET[$nombre . '_' . $sufijo] ?? '')));
                if ($v === '') {
                    continue;
                }
                $valido = $esFecha ? (bool) DateTime::createFromFormat('!Y-m-d', $v) : is_numeric($v);
                if ($valido) {
                    $where[] = "$expr $operador ?";
                    $params[] = $v;
                }
            }
            break;
    }
}
$activos = count($where);

try {
    $sql = sql_select($tabla) . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . orden($tabla);
    $st = conectar()->prepare($sql);
    $st->execute($params);
    $filas = $st->fetchAll();
} catch (PDOException $ex) {
    $error = $ex;
}

/** Valor enviado de un filtro, para repintar el formulario. */
function g(string $clave): string
{
    return (string) ($_GET[$clave] ?? '');
}

require __DIR__ . '/inc/cabecera.php';

if (isset($error)) {
    error_conexion($error);
    require __DIR__ . '/inc/pie.php';
    exit;
}
?>
<form method="get" action="filtrar.php" class="card card-body shadow-sm mb-4">
            <input type="hidden" name="t" value="<?= e($tabla) ?>">
            <h2 class="h6 mb-3"><i class="bi bi-funnel me-1"></i>Condiciones <span class="fw-normal text-body-secondary small">(se aplican todas a la vez; deja en blanco las que no quieras usar)</span></h2>
            <div class="row g-3">
            <?php foreach ($d['filtros'] as $nombre => $tipo): $campo = $d['campos'][$nombre]; $id = 'fl_' . $nombre; ?>
                <div class="col-sm-6 col-lg-4 col-xl-3">
                    <?php if ($tipo === 'contiene'): ?>
                        <label class="form-label small fw-semibold" for="<?= $id ?>"><?= e($campo['label']) ?> contiene</label>
                        <input class="form-control form-control-sm" type="search" id="<?= $id ?>" name="<?= $nombre ?>" value="<?= e(g($nombre)) ?>">

                    <?php elseif ($tipo === 'igual'): ?>
                        <label class="form-label small fw-semibold" for="<?= $id ?>"><?= e($campo['label']) ?></label>
                        <select class="form-select form-select-sm" id="<?= $id ?>" name="<?= $nombre ?>">
                            <option value="">Cualquiera</option>
                            <?php if (empty($campo['req'])): ?>
                                <option value="nulo"<?= g($nombre) === 'nulo' ? ' selected' : '' ?>><?= e($campo['vacio'] ?? 'Sin valor') ?></option>
                            <?php endif; ?>
                            <?php foreach (opciones($campo['tabla']) as $valor => $texto): ?>
                                <option value="<?= $valor ?>"<?= g($nombre) === (string) $valor ? ' selected' : '' ?>><?= e($texto) ?></option>
                            <?php endforeach; ?>
                        </select>

                    <?php elseif ($tipo === 'bool'): ?>
                        <span class="form-label small fw-semibold d-block"><?= e($campo['label']) ?></span>
                        <div class="btn-group btn-group-sm w-100" role="group" aria-label="<?= e($campo['label']) ?>">
                            <?php foreach (['' => 'Todos', '1' => 'Sí', '0' => 'No'] as $valor => $texto): $rid = $id . '_' . ($valor === '' ? 't' : $valor); ?>
                                <input type="radio" class="btn-check" name="<?= $nombre ?>" id="<?= $rid ?>" value="<?= $valor ?>"<?= g($nombre) === (string) $valor ? ' checked' : '' ?>>
                                <label class="btn btn-outline-success" for="<?= $rid ?>"><?= $texto ?></label>
                            <?php endforeach; ?>
                        </div>

                    <?php else: // rango
                        $tipoInput = in_array($campo['tipo'], ['fecha', 'fechahora'], true) ? 'date' : 'number';
                        $step = $campo['tipo'] === 'entero' ? '1' : '0.01'; ?>
                        <span class="form-label small fw-semibold d-block"><?= e($campo['label']) ?></span>
                        <div class="input-group input-group-sm">
                            <input class="form-control" type="<?= $tipoInput ?>" step="<?= $step ?>" name="<?= $nombre ?>_desde"
                                   value="<?= e(g($nombre . '_desde')) ?>" aria-label="<?= e($campo['label']) ?> desde" placeholder="Desde">
                            <input class="form-control" type="<?= $tipoInput ?>" step="<?= $step ?>" name="<?= $nombre ?>_hasta"
                                   value="<?= e(g($nombre . '_hasta')) ?>" aria-label="<?= e($campo['label']) ?> hasta" placeholder="Hasta">
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            </div>
            <div class="d-flex flex-wrap gap-2 mt-3 pt-3 border-top">
                <button class="btn btn-success" type="submit"><i class="bi bi-funnel me-1"></i>Aplicar filtros</button>
                <a class="btn btn-outline-secondary" href="<?= e(url('filtrar', ['t' => $tabla])) ?>">Quitar filtros</a>
            </div>
        </form>

        <p class="text-body-secondary">
            <?= count($filas) ?> registro<?= count($filas) === 1 ? '' : 's' ?>
            <?= $activos ? 'cumplen ' . ($activos === 1 ? 'la condición' : "las $activos condiciones") . '.' : 'en total (sin filtros).' ?>
        </p>
        <?php tabla_registros($tabla, $filas); ?>
<?php
require __DIR__ . '/inc/pie.php';
