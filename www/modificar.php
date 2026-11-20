<?php
// Modificación de un registro buscado previamente:
//   1) sin ?id se muestra el buscador y la lista de resultados;
//   2) con ?id se muestra el formulario relleno con los datos actuales.
require_once __DIR__ . '/inc/funciones.php';
require_once __DIR__ . '/inc/componentes.php';

$tabla  = tabla_actual();
$op     = 'modificar';
$d      = def($tabla);
$titulo = 'Modificar ' . $d['titulo'];

$id = $_POST['id'] ?? $_GET['id'] ?? null;
$registro = null;
$errores = [];
$valores = [];

try {
    if ($id !== null) {
        $registro = buscar_por_id($tabla, $id);
    }

    if ($registro && $_SERVER['REQUEST_METHOD'] === 'POST') {
        [$datos, $valores, $errores] = leer_formulario($tabla);
        $errores += comprobar_unicos($tabla, $datos, (int) $registro['id']);

        $imagen = validar_imagen();
        if (is_string($imagen)) {
            $errores['imagen'] = $imagen;
        }

        if (!$errores) {
            $nueva = null;
            try {
                if (is_array($imagen)) {
                    $nueva = guardar_imagen($tabla, $imagen);
                    $datos[$d['imagen']] = $nueva;
                }
                actualizar($tabla, (int) $registro['id'], $datos);
                if ($nueva) {
                    borrar_imagen($registro[$d['imagen']]);
                }
                redirigir('consulta', ['t' => $tabla, 'id' => $registro['id'], 'ok' => 'modificar']);
            } catch (PDOException $ex) {
                borrar_imagen($nueva);
                $errores['_general'] = mensaje_error_bd($ex, $tabla);
            } catch (RuntimeException $ex) {
                $errores['_general'] = $ex->getMessage();
            }
        }
    } elseif ($registro) {
        $valores = $registro;
    }
} catch (PDOException $ex) {
    $error = $ex;
}

if ($registro) {
    $titulo = 'Modificar ' . articulo($tabla) . ' #' . $registro['id'];
}

require __DIR__ . '/inc/cabecera.php';
avisos($errores);

if (isset($error)) {
    error_conexion($error);
} elseif ($registro) {
    echo '<p><a class="link-secondary" href="' . e(url('modificar', ['t' => $tabla])) . '"><i class="bi bi-arrow-left me-1"></i>Buscar otro registro</a></p>';
    formulario($tabla, $valores, $errores, $registro, 'Guardar cambios');
} else {
    if ($id !== null) {
        echo '<div class="alert alert-warning">No existe ' . e(articulo($tabla, true)) . ' con el ID ' . e($id) . '.</div>';
    }
    echo '<p class="text-body-secondary">Busca primero ' . e(articulo($tabla)) . ' que quieres modificar.</p>';
    try {
        $filas = buscador($tabla, 'modificar');
        if ($filas !== null) {
            resultados_busqueda($tabla, $filas, 'modificar');
        }
    } catch (PDOException $ex) {
        error_conexion($ex);
    }
}

require __DIR__ . '/inc/pie.php';
