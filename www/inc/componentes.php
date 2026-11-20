<?php
// Bloques de HTML que se repiten en varias páginas.

/** Avisos de resultado (?ok=...) y de error. */
function avisos(array $errores = []): void
{
    $mensajes = [
        'alta'      => 'Registro dado de alta correctamente.',
        'modificar' => 'Cambios guardados correctamente.',
        'borrar'    => 'Registro borrado correctamente.',
    ];
    $ok = $_GET['ok'] ?? '';
    if (isset($mensajes[$ok])) {
        echo '<div class="alert alert-success alert-dismissible fade show" role="status"><i class="bi bi-check-circle me-2"></i>'
           . $mensajes[$ok] . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button></div>';
    }
    if (isset($errores['_general'])) {
        echo '<div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-triangle me-2"></i>' . e($errores['_general']) . '</div>';
    } elseif ($errores) {
        echo '<div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-triangle me-2"></i>'
           . 'Revisa los campos marcados en rojo (' . count($errores) . ').</div>';
    }
}

/** Tabla con los registros (listado completo, listado con filtros y resultados). */
function tabla_registros(string $tabla, array $filas): void
{
    $d = def($tabla);
    $columnas = array_filter($d['campos'], fn($c) => !empty($c['lista']));

    if (!$filas) {
        echo '<div class="alert alert-info"><i class="bi bi-info-circle me-2"></i>No hay ningún registro que mostrar.</div>';
        return;
    }
    ?>
    <div class="table-responsive border rounded bg-body">
        <table class="table table-hover table-striped align-middle mb-0">
            <thead class="table-light">
            <tr>
                <th scope="col" class="text-end">ID</th>
                <th scope="col">Imagen</th>
                <?php foreach ($columnas as $campo): ?>
                    <th scope="col" class="<?= in_array($campo['tipo'], ['decimal', 'entero'], true) ? 'text-end' : '' ?> text-nowrap"><?= e($campo['label']) ?></th>
                <?php endforeach; ?>
                <th scope="col" class="text-end">Acciones</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($filas as $f): ?>
                <tr>
                    <td class="text-end text-body-secondary"><?= (int) $f['id'] ?></td>
                    <td><img src="<?= e(url_imagen($f[$d['imagen']])) ?>" alt="" class="miniatura rounded" loading="lazy"></td>
                    <?php foreach ($columnas as $nombre => $campo): ?>
                        <td class="<?= in_array($campo['tipo'], ['decimal', 'entero'], true) ? 'text-end text-nowrap' : '' ?>">
                            <?= mostrar_valor($campo, $f, $nombre) ?></td>
                    <?php endforeach; ?>
                    <td class="text-end text-nowrap">
                        <div class="btn-group btn-group-sm" role="group" aria-label="Acciones">
                            <a class="btn btn-outline-secondary" href="<?= e(url('consulta', ['t' => $tabla, 'id' => $f['id']])) ?>" title="Ver ficha"><i class="bi bi-eye"></i><span class="visually-hidden">Ver</span></a>
                            <a class="btn btn-outline-primary" href="<?= e(url('modificar', ['t' => $tabla, 'id' => $f['id']])) ?>" title="Modificar"><i class="bi bi-pencil"></i><span class="visually-hidden">Modificar</span></a>
                            <a class="btn btn-outline-danger" href="<?= e(url('borrar', ['t' => $tabla, 'id' => $f['id']])) ?>" title="Borrar"><i class="bi bi-trash"></i><span class="visually-hidden">Borrar</span></a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

/** Ficha con todos los datos de un registro. */
function ficha(string $tabla, array $fila, bool $con_botones = true): void
{
    $d = def($tabla);
    ?>
    <div class="card shadow-sm">
        <div class="row g-0">
            <div class="col-md-4 col-lg-3 bg-body-tertiary d-flex align-items-center justify-content-center p-3">
                <img src="<?= e(url_imagen($fila[$d['imagen']])) ?>" alt="Imagen de <?= e(articulo($tabla)) ?>" class="img-fluid rounded ficha-imagen">
            </div>
            <div class="col-md-8 col-lg-9">
                <div class="card-body">
                    <p class="text-body-secondary small mb-1"><?= e(ucfirst($d['singular'])) ?> · ID <?= (int) $fila['id'] ?></p>
                    <dl class="row mb-0">
                        <?php foreach ($d['campos'] as $nombre => $campo): ?>
                            <dt class="col-sm-4 col-xl-3 fw-semibold"><?= e($campo['label']) ?></dt>
                            <dd class="col-sm-8 col-xl-9"><?= mostrar_valor($campo, $fila, $nombre) ?></dd>
                        <?php endforeach; ?>
                    </dl>
                </div>
                <?php if ($con_botones): ?>
                    <div class="card-footer bg-transparent d-flex flex-wrap gap-2">
                        <a class="btn btn-primary" href="<?= e(url('modificar', ['t' => $tabla, 'id' => $fila['id']])) ?>"><i class="bi bi-pencil me-1"></i>Modificar</a>
                        <a class="btn btn-outline-danger" href="<?= e(url('borrar', ['t' => $tabla, 'id' => $fila['id']])) ?>"><i class="bi bi-trash me-1"></i>Borrar</a>
                        <?php foreach (tablas_hijas($tabla) as [$hija, $col]): ?>
                            <a class="btn btn-outline-secondary" href="<?= e(url('filtrar', ['t' => $hija, $col => $fila['id']])) ?>">
                                <i class="bi <?= TABLAS[$hija]['icono'] ?> me-1"></i>Ver <?= e(mb_strtolower(TABLAS[$hija]['titulo'])) ?></a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Formulario de búsqueda por un campo relevante.
 * Devuelve los registros encontrados (o null si todavía no se ha buscado).
 */
function buscador(string $tabla, string $pagina): ?array
{
    $d = def($tabla);
    $campos = $d['busqueda'];
    $por = $_GET['por'] ?? array_key_first($campos);
    if (!isset($campos[$por])) {
        $por = array_key_first($campos);
    }
    $q = trim((string) ($_GET['q'] ?? ''));
    $tipo_input = $campos[$por]['modo'] === 'fecha' ? 'date' : 'search';
    ?>
    <form class="card card-body shadow-sm mb-4" method="get" action="<?= e($pagina) ?>.php" role="search">
        <input type="hidden" name="t" value="<?= e($tabla) ?>">
        <div class="row g-2 align-items-end">
            <div class="col-sm-4 col-lg-3">
                <label class="form-label" for="por">Buscar por</label>
                <select class="form-select" id="por" name="por" data-cambia-tipo>
                    <?php foreach ($campos as $clave => $c): ?>
                        <option value="<?= e($clave) ?>" data-modo="<?= e($c['modo']) ?>"<?= $clave === $por ? ' selected' : '' ?>><?= e($c['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-8 col-lg-6">
                <label class="form-label" for="q">Texto a buscar</label>
                <input class="form-control" type="<?= $tipo_input ?>" id="q" name="q" value="<?= e($q) ?>" required autofocus
                       placeholder="Escribe todo o parte del valor">
            </div>
            <div class="col-lg-3 d-grid">
                <button class="btn btn-success" type="submit"><i class="bi bi-search me-1"></i>Buscar</button>
            </div>
        </div>
    </form>
    <?php
    if ($q === '') {
        return null;
    }
    $c = $campos[$por];
    if ($c['modo'] === 'fecha') {
        $where = $c['sql'] . ' = ?';
        $param = $q;
    } else {
        $where = 'LOWER(' . $c['sql'] . ') LIKE LOWER(?)';
        $param = '%' . $q . '%';
    }
    $st = conectar()->prepare(sql_select($tabla) . ' WHERE ' . $where . orden($tabla));
    $st->execute([$param]);
    return $st->fetchAll();
}

/** Lista compacta de resultados de una búsqueda, con un botón de acción. */
function resultados_busqueda(string $tabla, array $filas, string $accion): void
{
    $d = def($tabla);
    $acciones = [
        'consulta'  => ['Ver ficha', 'btn-outline-secondary', 'bi-eye'],
        'modificar' => ['Modificar', 'btn-primary', 'bi-pencil'],
        'borrar'    => ['Borrar', 'btn-danger', 'bi-trash'],
    ];
    [$texto, $clase, $icono] = $acciones[$accion];
    if (!$filas) {
        echo '<div class="alert alert-warning"><i class="bi bi-emoji-frown me-2"></i>No se ha encontrado ningún registro. Prueba con otro texto o con otro campo.</div>';
        return;
    }
    $etiquetas = opciones($tabla);
    echo '<p class="text-body-secondary">' . count($filas) . ' resultado' . (count($filas) === 1 ? '' : 's') . '. Elige el registro:</p>';
    echo '<div class="list-group shadow-sm">';
    foreach ($filas as $f) {
        echo '<a class="list-group-item list-group-item-action d-flex align-items-center gap-3" href="'
           . e(url($accion, ['t' => $tabla, 'id' => $f['id']])) . '">'
           . '<img src="' . e(url_imagen($f[$d['imagen']])) . '" alt="" class="miniatura rounded">'
           . '<span class="flex-grow-1">' . e($etiquetas[$f['id']] ?? ('#' . $f['id'])) . '</span>'
           . '<span class="btn btn-sm ' . $clase . '"><i class="bi ' . $icono . ' me-1"></i>' . $texto . '</span></a>';
    }
    echo '</div>';
}

/** Formulario de alta / modificación. */
function formulario(string $tabla, array $valores, array $errores, ?array $registro, string $texto_boton): void
{
    $d = def($tabla);
    ?>
    <form method="post" enctype="multipart/form-data" class="card card-body shadow-sm" novalidate data-valida>
        <input type="hidden" name="t" value="<?= e($tabla) ?>">
        <?php if ($registro): ?><input type="hidden" name="id" value="<?= (int) $registro['id'] ?>"><?php endif; ?>
        <p class="small text-body-secondary">Los campos con <span class="text-danger">*</span> son obligatorios.</p>
        <div class="row g-3">
            <?php foreach ($d['campos'] as $nombre => $campo): ?>
                <?= campo_html($nombre, $campo, $valores[$nombre] ?? '', $errores[$nombre] ?? null) ?>
            <?php endforeach; ?>
            <?= campo_imagen($registro[$d['imagen']] ?? null, $errores['imagen'] ?? null, $registro === null) ?>
        </div>
        <div class="d-flex flex-wrap gap-2 mt-4 pt-3 border-top">
            <button type="submit" class="btn btn-success"><i class="bi bi-check-lg me-1"></i><?= e($texto_boton) ?></button>
            <a class="btn btn-outline-secondary" href="<?= e($registro ? url('consulta', ['t' => $tabla, 'id' => $registro['id']]) : url('listado', ['t' => $tabla])) ?>">Cancelar</a>
        </div>
    </form>
    <?php
}

/** Página de error de conexión. */
function error_conexion(PDOException $ex): void
{
    echo '<div class="alert alert-danger"><h2 class="h5"><i class="bi bi-database-x me-2"></i>No se ha podido conectar con la base de datos</h2>'
       . '<p class="mb-0 small">' . e($ex->getMessage()) . '</p></div>';
}
