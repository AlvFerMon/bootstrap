<?php
// Cabecera común: <head>, barra de navegación y, en las páginas de una tabla,
// las migas de pan y el submenú con sus operaciones.
//
// Variables que puede definir la página antes de incluirla:
//   $titulo  título de la página
//   $tabla   tabla con la que se trabaja (opcional)
//   $op      operación actual: listado, filtrar, consulta, alta, modificar, borrar

$tabla = $tabla ?? null;
$op    = $op ?? null;
$pagina_actual = basename($_SERVER['SCRIPT_NAME'] ?? '');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo ?? 'Inicio') ?> · Clínica Carlitos &amp; Snoopy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="css/app.css" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">
<a class="visually-hidden-focusable" href="#contenido">Saltar al contenido</a>

<nav class="navbar navbar-expand-lg bg-success sticky-top shadow-sm" data-bs-theme="dark">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php"><i class="bi bi-hospital me-1"></i>Carlitos &amp; Snoopy</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu"
                aria-controls="menu" aria-expanded="false" aria-label="Abrir menú">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="menu">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link<?= $pagina_actual === 'index.php' ? ' active' : '' ?>" href="index.php"
                       <?= $pagina_actual === 'index.php' ? 'aria-current="page"' : '' ?>><i class="bi bi-house-door me-1"></i>Inicio</a>
                </li>
                <?php foreach (TABLAS as $nombre => $dt): if ($dt['grupo'] !== 'principal') continue; ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle<?= $tabla === $nombre ? ' active' : '' ?>" href="#" role="button"
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi <?= $dt['icono'] ?> me-1"></i><?= e($dt['titulo']) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <?php foreach (OPERACIONES as $clave => [$texto, $icono]): ?>
                                <?php if ($clave === 'alta'): ?><li><hr class="dropdown-divider"></li><?php endif; ?>
                                <li><a class="dropdown-item<?= ($tabla === $nombre && $op === $clave) ? ' active' : '' ?>"
                                       href="<?= e(url($clave, ['t' => $nombre])) ?>"><i class="bi <?= $icono ?> me-2"></i><?= $texto ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                <?php endforeach; ?>
                <li class="nav-item dropdown">
                    <?php $en_catalogo = $tabla && TABLAS[$tabla]['grupo'] === 'catalogo'; ?>
                    <a class="nav-link dropdown-toggle<?= $en_catalogo ? ' active' : '' ?>" href="#" role="button"
                       data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-journal-bookmark me-1"></i>Catálogos</a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <?php foreach (TABLAS as $nombre => $dt): if ($dt['grupo'] !== 'catalogo') continue; ?>
                            <li><a class="dropdown-item<?= $tabla === $nombre ? ' active' : '' ?>" href="<?= e(url('listado', ['t' => $nombre])) ?>">
                                <i class="bi <?= $dt['icono'] ?> me-2"></i><?= e($dt['titulo']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<main id="contenido" class="container py-4 flex-grow-1">
<?php if ($tabla): $dt = TABLAS[$tabla]; ?>
    <nav aria-label="Ruta">
        <ol class="breadcrumb small mb-2">
            <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
            <?php if ($dt['grupo'] === 'catalogo'): ?><li class="breadcrumb-item">Catálogos</li><?php endif; ?>
            <li class="breadcrumb-item"><a href="<?= e(url('listado', ['t' => $tabla])) ?>"><?= e($dt['titulo']) ?></a></li>
            <?php if ($op): ?><li class="breadcrumb-item active" aria-current="page"><?= OPERACIONES[$op][0] ?></li><?php endif; ?>
        </ol>
    </nav>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <h1 class="h3 mb-0"><i class="bi <?= $dt['icono'] ?> text-success me-2"></i><?= e($titulo) ?></h1>
    </div>
    <ul class="nav nav-pills submenu mb-4 flex-nowrap overflow-auto">
        <?php foreach (OPERACIONES as $clave => [$texto, $icono]): ?>
            <li class="nav-item">
                <a class="nav-link text-nowrap<?= $op === $clave ? ' active' : '' ?>" href="<?= e(url($clave, ['t' => $tabla])) ?>"
                   <?= $op === $clave ? 'aria-current="page"' : '' ?>><i class="bi <?= $icono ?> me-1"></i><?= $texto ?></a>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
