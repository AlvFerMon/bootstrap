<?php
require_once __DIR__ . '/config.php';

// Número de registros de cada tabla, para comprobar la conexión
$tablas = [
    'propietarios' => 'Propietarios',
    'mascotas'     => 'Mascotas',
    'veterinarios' => 'Veterinarios',
    'citas'        => 'Citas',
];
$totales = [];
$error = null;

try {
    $db = conectar();
    foreach ($tablas as $tabla => $titulo) {
        $totales[$tabla] = (int) $db->query("SELECT COUNT(*) FROM $tabla")->fetchColumn();
    }
} catch (PDOException $e) {
    $error = $e->getMessage();
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Clínica veterinaria Carlitos &amp; Snoopy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
</head>
<body>
    <nav class="navbar navbar-expand-lg bg-success" data-bs-theme="dark">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">Clínica Carlitos &amp; Snoopy</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu"
                    aria-controls="menu" aria-expanded="false" aria-label="Abrir menú">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="menu">
                <ul class="navbar-nav ms-auto">
                    <?php foreach ($tablas as $tabla => $titulo): ?>
                        <li class="nav-item"><a class="nav-link" href="#"><?= $titulo ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container py-5">
        <h1 class="mb-4">Panel de la clínica</h1>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                No se ha podido conectar con la base de datos: <?= htmlspecialchars($error) ?>
            </div>
        <?php else: ?>
            <div class="alert alert-success">Conexión con la base de datos <strong><?= DB_NAME ?></strong> correcta.</div>
            <div class="row g-3">
                <?php foreach ($tablas as $tabla => $titulo): ?>
                    <div class="col-6 col-lg-3">
                        <div class="card text-center h-100">
                            <div class="card-body">
                                <p class="display-6 mb-0"><?= $totales[$tabla] ?></p>
                                <p class="text-muted mb-0"><?= $titulo ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>
</html>
