<?php
// Alta de registros. Las claves foráneas se eligen en desplegables cargados
// desde la base de datos y cada registro lleva su imagen.
require_once __DIR__ . '/inc/funciones.php';
require_once __DIR__ . '/inc/componentes.php';

$tabla  = tabla_actual();
$op     = 'alta';
$d      = def($tabla);
$titulo = 'Alta de ' . articulo($tabla, true);

$errores = [];
$valores = valores_por_defecto($tabla);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    [$datos, $valores, $errores] = leer_formulario($tabla);
    $errores += comprobar_unicos($tabla, $datos, null);

    $imagen = validar_imagen();
    if ($imagen === null) {
        $errores['imagen'] = 'Elige una imagen para ' . articulo($tabla) . '.';
    } elseif (is_string($imagen)) {
        $errores['imagen'] = $imagen;
    }

    if (!$errores) {
        $ruta = null;
        try {
            $ruta = guardar_imagen($tabla, $imagen);
            $datos[$d['imagen']] = $ruta;
            $id = insertar($tabla, $datos);
            redirigir('consulta', ['t' => $tabla, 'id' => $id, 'ok' => 'alta']);
        } catch (PDOException $ex) {
            borrar_imagen($ruta);
            $errores['_general'] = mensaje_error_bd($ex, $tabla);
        } catch (RuntimeException $ex) {
            $errores['_general'] = $ex->getMessage();
        }
    }
}

require __DIR__ . '/inc/cabecera.php';
avisos($errores);

try {
    formulario($tabla, $valores, $errores, null, 'Dar de alta');
} catch (PDOException $ex) {
    error_conexion($ex);
}

require __DIR__ . '/inc/pie.php';
