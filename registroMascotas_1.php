<?php
require __DIR__ . '/conexion.php';

function e($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

$campos  = ['nombre', 'especie', 'raza', 'nacimiento', 'sexo', 'propietario', 'telefono', 'email'];
$datos   = array_fill_keys($campos, '');
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($campos as $c) {
        $datos[$c] = trim($_POST[$c] ?? '');
    }

    if ($datos['nombre'] === '' || $datos['especie'] === '' || $datos['nacimiento'] === ''
        || $datos['sexo'] === '' || $datos['propietario'] === '' || $datos['telefono'] === '') {
        $errores[] = 'Rellena todos los campos obligatorios.';
    }
    if (!filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'El correo electrónico no es válido.';
    }

    if (!$errores) {
        try {
            $pdo->beginTransaction();

            // Si el propietario ya existe (mismo email) se reutiliza; si no, se crea
            $st = $pdo->prepare('SELECT id FROM propietarios WHERE email = ?');
            $st->execute([$datos['email']]);
            $propietarioId = $st->fetchColumn();

            if ($propietarioId === false) {
                $st = $pdo->prepare('INSERT INTO propietarios (nombre, telefono, email) VALUES (?, ?, ?)');
                $st->execute([$datos['propietario'], $datos['telefono'], $datos['email']]);
                $propietarioId = $pdo->lastInsertId();
            }

            $st = $pdo->prepare(
                'INSERT INTO mascotas (nombre, especie, raza, fecha_nacimiento, sexo, propietario_id)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $st->execute([
                $datos['nombre'],
                $datos['especie'],
                $datos['raza'] !== '' ? $datos['raza'] : null,
                $datos['nacimiento'],
                $datos['sexo'],
                $propietarioId,
            ]);

            $pdo->commit();
            header('Location: registroMascotas.php?ok=1');
            exit;
        } catch (PDOException $ex) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errores[] = 'No se pudo guardar el registro. Inténtalo de nuevo.';
        }
    }
}
?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registro de mascotas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Montserrat:wght@500;600;700&display=swap"
        rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-clinica" data-bs-theme="dark">
        <div class="container">
            <a class="navbar-brand" href="index.html">Clínica Veterinaria</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu"
                aria-controls="menu" aria-expanded="false" aria-label="Mostrar menú">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="menu">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                <a class="nav-link" href="index.html">Inicio</a>
              </li>
              <li class="nav-item">
                <a class="nav-link active"
                  aria-current="page" href="registroMascotas.php">registro de mascotas</a>
              </li>
              <li class="nav-item">
                <a
                  class="nav-link"
                  href="servicios.html"
                  >Servicios y tarifas</a
                >
              </li>
              <li class="nav-item">
                <a class="nav-link" href="cuidados.html">Cuidados</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="informacionycontacto.html"
                  >Información y contacto</a
                >
              </li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container my-5">

        <h1 class="h2 text-primary text-center mb-4">Registro de mascotas</h1>

        <?php if (($_GET['ok'] ?? '') === '1'): ?>
            <div class="alert alert-success col-lg-8 mx-auto" role="alert">Mascota registrada correctamente.</div>
        <?php endif; ?>

        <?php if ($errores): ?>
            <div class="alert alert-danger col-lg-8 mx-auto" role="alert">
                <ul class="mb-0">
                    <?php foreach ($errores as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form class="row justify-content-center" method="post" action="registroMascotas.php">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-success text-dark">Datos de la mascota y del propietario</div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="nombre" class="form-label">Nombre de la mascota</label>
                                <input type="text" id="nombre" name="nombre" class="form-control"
                                    value="<?= e($datos['nombre']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="especie" class="form-label">Especie</label>
                                <select id="especie" name="especie" class="form-select" required>
                                    <option value="" disabled <?= $datos['especie'] === '' ? 'selected' : '' ?>>Seleccione una opción</option>
                                    <?php foreach (['Perro', 'Gato', 'Conejo', 'Otra'] as $esp): ?>
                                        <option <?= $datos['especie'] === $esp ? 'selected' : '' ?>><?= $esp ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="raza" class="form-label">Raza</label>
                                <input type="text" id="raza" name="raza" class="form-control"
                                    value="<?= e($datos['raza']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="nacimiento" class="form-label">Fecha de nacimiento</label>
                                <input type="date" id="nacimiento" name="nacimiento" class="form-control"
                                    value="<?= e($datos['nacimiento']) ?>" required>
                            </div>
                            <div class="col-12">
                                <span class="form-label d-block">Sexo</span>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="sexo" id="macho" value="Macho"
                                        <?= $datos['sexo'] === 'Macho' ? 'checked' : '' ?> required>
                                    <label class="form-check-label" for="macho">Macho</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="sexo" id="hembra" value="Hembra"
                                        <?= $datos['sexo'] === 'Hembra' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="hembra">Hembra</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <label for="propietario" class="form-label">Nombre del propietario</label>
                                <input type="text" id="propietario" name="propietario" class="form-control"
                                    value="<?= e($datos['propietario']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="telefono" class="form-label">Teléfono</label>
                                <input type="tel" id="telefono" name="telefono" class="form-control"
                                    value="<?= e($datos['telefono']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Correo electrónico</label>
                                <input type="email" id="email" name="email" class="form-control"
                                    value="<?= e($datos['email']) ?>" required>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <button type="reset" class="btn btn-outline-primary">Limpiar</button>
                    <button type="submit" class="btn btn-primary">Registrar</button>
                </div>
            </div>
        </form>
    </main>
    <footer class="pie text-white py-4 mt-5">
        <div class="container text-center">
            <p class="mb-0">Hecho por <span class="fw-semibold">Alvaro Fernandez Montero</span></p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
</body>

</html>
