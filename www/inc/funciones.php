<?php
// Funciones comunes a todas las páginas.

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/tablas.php';

// ------------------------------------------------------------------ utilidades

/** Escapa un valor para mostrarlo en HTML. */
function e($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

/** Tabla pedida en la URL (?t=...). Si no existe, vuelve al inicio. */
function tabla_actual(): string
{
    $t = $_GET['t'] ?? $_POST['t'] ?? '';
    if (!isset(TABLAS[$t])) {
        header('Location: index.php');
        exit;
    }
    return $t;
}

function def(string $tabla): array
{
    return TABLAS[$tabla];
}

/** "el propietario" / "la mascota" / "un propietario" / "una mascota" */
function articulo(string $tabla, bool $indeterminado = false): string
{
    $f = def($tabla)['genero'] === 'a';
    if ($indeterminado) {
        return ($f ? 'una ' : 'un ') . def($tabla)['singular'];
    }
    return ($f ? 'la ' : 'el ') . def($tabla)['singular'];
}

/** Construye una URL de la aplicación con sus parámetros. */
function url(string $pagina, array $parametros = []): string
{
    $parametros = array_filter($parametros, fn($v) => $v !== null && $v !== '');
    return $pagina . '.php' . ($parametros ? '?' . http_build_query($parametros) : '');
}

function redirigir(string $pagina, array $parametros = []): void
{
    header('Location: ' . url($pagina, $parametros));
    exit;
}

/** Ruta pública de una imagen (local o de Supabase Storage). */
function url_imagen(?string $ruta): string
{
    if ($ruta === null || $ruta === '') {
        return 'img/sin-imagen.png';
    }
    return $ruta;   // ruta relativa (img/...) o URL completa de Supabase Storage
}

// ------------------------------------------------------------------ formato

function fmt_fecha(?string $v): string
{
    if (!$v) {
        return '';
    }
    $d = date_create($v);
    return $d ? $d->format('d/m/Y') : $v;
}

function fmt_fechahora(?string $v): string
{
    if (!$v) {
        return '';
    }
    $d = date_create($v);
    return $d ? $d->format('d/m/Y H:i') : $v;
}

function fmt_numero($v, int $decimales = 2): string
{
    return number_format((float) $v, $decimales, ',', '.');
}

/** Valor de un campo listo para mostrarlo (HTML ya escapado). */
function mostrar_valor(array $campo, array $fila, string $nombre): string
{
    $v = $fila[$nombre] ?? null;

    switch ($campo['tipo']) {
        case 'bool':
            return (int) $v === 1
                ? '<span class="badge text-bg-success">Sí</span>'
                : '<span class="badge text-bg-secondary">No</span>';
        case 'fk':
            $texto = $fila[$nombre . '__txt'] ?? null;
            if ($v === null || $texto === null) {
                return '<span class="text-body-secondary">-</span>';
            }
            return '<a class="link-body-emphasis" href="' . e(url('consulta', ['t' => $campo['tabla'], 'id' => $v])) . '">' . e($texto) . '</a>';
        case 'fecha':
            return e(fmt_fecha($v));
        case 'fechahora':
            return e(fmt_fechahora($v));
        case 'decimal':
            if ($v === null) {
                return '';
            }
            return e(fmt_numero($v)) . (!empty($campo['moneda']) ? '&nbsp;€' : '');
        case 'entero':
            return $v === null ? '' : e(fmt_numero($v, 0));
        case 'email':
            return $v ? '<a href="mailto:' . e($v) . '">' . e($v) . '</a>' : '<span class="text-body-secondary">-</span>';
        case 'textarea':
            return $v ? nl2br(e($v)) : '<span class="text-body-secondary">-</span>';
        default:
            return ($v === null || $v === '') ? '<span class="text-body-secondary">-</span>' : e($v);
    }
}

// ------------------------------------------------------------------ consultas

/** SELECT con los textos de las claves foráneas (columna__txt). */
function sql_select(string $tabla): string
{
    $partes = ['t.*'];
    foreach (def($tabla)['campos'] as $nombre => $campo) {
        if ($campo['tipo'] === 'fk') {
            $ref = $campo['tabla'];
            $partes[] = '(SELECT ' . (def($ref)['corta'] ?? def($ref)['etiqueta']) . " FROM $ref x WHERE x.id = t.$nombre) AS {$nombre}__txt";
        }
    }
    return 'SELECT ' . implode(', ', $partes) . " FROM $tabla t";
}

function orden(string $tabla): string
{
    $cols = array_map(fn($c) => 't.' . trim($c), explode(',', def($tabla)['orden']));
    return ' ORDER BY ' . implode(', ', $cols) . ', t.id';
}

function buscar_por_id(string $tabla, $id): ?array
{
    if (!ctype_digit((string) $id)) {
        return null;
    }
    $st = conectar()->prepare(sql_select($tabla) . ' WHERE t.id = ?');
    $st->execute([(int) $id]);
    return $st->fetch() ?: null;
}

function todos(string $tabla): array
{
    return conectar()->query(sql_select($tabla) . orden($tabla))->fetchAll();
}

/** Opciones [id => texto] para los desplegables, cargadas de la base de datos. */
function opciones(string $tabla): array
{
    static $cache = [];
    if (!isset($cache[$tabla])) {
        $sql = 'SELECT x.id, ' . def($tabla)['etiqueta'] . " AS texto FROM $tabla x ORDER BY "
             . implode(', ', array_map(fn($c) => 'x.' . trim($c), explode(',', def($tabla)['orden'])));
        $cache[$tabla] = conectar()->query($sql)->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    return $cache[$tabla];
}

function contar(string $tabla): int
{
    return (int) conectar()->query("SELECT COUNT(*) FROM $tabla")->fetchColumn();
}

/** Tablas que apuntan a $tabla con una clave foránea: [[tabla_hija, columna], ...] */
function tablas_hijas(string $tabla): array
{
    $hijas = [];
    foreach (TABLAS as $nombre => $d) {
        foreach ($d['campos'] as $col => $campo) {
            if ($campo['tipo'] === 'fk' && $campo['tabla'] === $tabla) {
                $hijas[] = [$nombre, $col];
            }
        }
    }
    return $hijas;
}

/** Cuántos registros de otras tablas dependen de este. */
function dependencias(string $tabla, int $id): array
{
    $res = [];
    foreach (tablas_hijas($tabla) as [$hija, $col]) {
        $st = conectar()->prepare("SELECT COUNT(*) FROM $hija WHERE $col = ?");
        $st->execute([$id]);
        $n = (int) $st->fetchColumn();
        if ($n > 0) {
            $res[] = ['tabla' => $hija, 'columna' => $col, 'total' => $n,
                      'nulable' => empty(def($hija)['campos'][$col]['req'])];
        }
    }
    return $res;
}

/** Traduce los errores de la base de datos a mensajes comprensibles. */
function mensaje_error_bd(PDOException $ex, string $tabla): string
{
    $estado = $ex->getCode();
    $texto  = $ex->getMessage();

    // Valor duplicado en un campo único (MySQL 1062 / PostgreSQL 23505)
    if ($estado === '23505' || str_contains($texto, '1062')) {
        foreach (def($tabla)['campos'] as $nombre => $campo) {
            if (str_contains($texto, $nombre)) {
                return "Ya existe otro registro con ese valor en «{$campo['label']}». Ese dato no se puede repetir.";
            }
        }
        return 'Ya existe otro registro con alguno de esos datos, que no se pueden repetir.';
    }
    // Clave foránea (MySQL 1451/1452 / PostgreSQL 23503)
    if ($estado === '23503' || str_contains($texto, '1451')) {
        return 'No se puede borrar porque hay otros registros que dependen de él.';
    }
    if (str_contains($texto, '1452')) {
        return 'Alguno de los valores elegidos en los desplegables ya no existe.';
    }
    // Restricciones CHECK (MySQL 3819 / PostgreSQL 23514)
    if ($estado === '23514' || str_contains($texto, '3819') || str_contains($texto, '4025')) {
        return 'Alguno de los valores está fuera del rango permitido.';
    }
    return 'Error de la base de datos: ' . $texto;
}

// ------------------------------------------------------------------ formularios

/** Valor inicial de los campos en un alta. */
function valores_por_defecto(string $tabla): array
{
    $datos = [];
    foreach (def($tabla)['campos'] as $nombre => $campo) {
        $d = $campo['defecto'] ?? '';
        $datos[$nombre] = $d === 'hoy' ? date('Y-m-d') : $d;
    }
    return $datos;
}

/**
 * Lee y valida los campos enviados por POST.
 * Devuelve [datos para guardar, valores tal cual para repintar el formulario, errores].
 */
function leer_formulario(string $tabla): array
{
    $datos = $crudos = $errores = [];

    foreach (def($tabla)['campos'] as $nombre => $campo) {
        $label = $campo['label'];

        if ($campo['tipo'] === 'bool') {
            $crudos[$nombre] = $datos[$nombre] = isset($_POST[$nombre]) ? 1 : 0;
            continue;
        }

        $v = trim((string) ($_POST[$nombre] ?? ''));
        $crudos[$nombre] = $v;

        if ($v === '') {
            if (!empty($campo['req'])) {
                $errores[$nombre] = "«{$label}» es obligatorio.";
            }
            $datos[$nombre] = null;
            continue;
        }

        if (isset($campo['max']) && in_array($campo['tipo'], ['texto', 'textarea', 'email', 'tel'], true)
            && mb_strlen($v) > $campo['max']) {
            $errores[$nombre] = "«{$label}» admite como máximo {$campo['max']} caracteres.";
            continue;
        }

        switch ($campo['tipo']) {
            case 'email':
                if (!filter_var($v, FILTER_VALIDATE_EMAIL)) {
                    $errores[$nombre] = "«{$label}» no es un correo válido.";
                }
                break;

            case 'tel':
                if (!preg_match('/^\+?[0-9 ]{9,15}$/', $v)) {
                    $errores[$nombre] = "«{$label}» debe tener al menos 9 cifras.";
                }
                $v = str_replace(' ', '', $v);
                break;

            case 'dni':
                $v = strtoupper(str_replace([' ', '-'], '', $v));
                if (!preg_match('/^([0-9]{8})([A-Z])$/', $v, $m)) {
                    $errores[$nombre] = "«{$label}» debe tener 8 números y una letra.";
                } elseif ('TRWAGMYFPDXBNJZSQVHLCKE'[((int) $m[1]) % 23] !== $m[2]) {
                    $errores[$nombre] = "La letra del DNI no es correcta.";
                }
                break;

            case 'entero':
            case 'decimal':
                $v = str_replace(',', '.', $v);
                $ok = $campo['tipo'] === 'entero'
                    ? preg_match('/^-?[0-9]+$/', $v)
                    : is_numeric($v);
                if (!$ok) {
                    $errores[$nombre] = "«{$label}» debe ser un número" . ($campo['tipo'] === 'entero' ? ' entero.' : '.');
                } elseif (isset($campo['min']) && $v < $campo['min']) {
                    $errores[$nombre] = "«{$label}» no puede ser menor que " . str_replace('.', ',', (string) $campo['min']) . '.';
                } elseif (isset($campo['max']) && $v > $campo['max']) {
                    $errores[$nombre] = "«{$label}» no puede ser mayor que " . str_replace('.', ',', (string) $campo['max']) . '.';
                }
                break;

            case 'fecha':
                $d = DateTime::createFromFormat('!Y-m-d', $v);
                if (!$d || $d->format('Y-m-d') !== $v) {
                    $errores[$nombre] = "«{$label}» no es una fecha válida.";
                } elseif (!empty($campo['no_futura']) && $v > date('Y-m-d')) {
                    $errores[$nombre] = "«{$label}» no puede ser una fecha futura.";
                }
                break;

            case 'fechahora':
                $v = str_replace('T', ' ', $v);
                if (strlen($v) === 16) {
                    $v .= ':00';
                }
                $d = DateTime::createFromFormat('Y-m-d H:i:s', $v);
                if (!$d || $d->format('Y-m-d H:i:s') !== $v) {
                    $errores[$nombre] = "«{$label}» no es una fecha y hora válida.";
                }
                break;

            case 'fk':
                if (!ctype_digit($v) || !array_key_exists((int) $v, opciones($campo['tabla']))) {
                    $errores[$nombre] = "Elige una opción válida en «{$label}».";
                }
                $v = (int) $v;
                break;

            default:
                if (!empty($campo['patron']) && !preg_match('/^' . $campo['patron'] . '$/u', $v)) {
                    $errores[$nombre] = "«{$label}» no tiene el formato correcto." . (isset($campo['ayuda']) ? ' ' . $campo['ayuda'] : '');
                }
        }

        $datos[$nombre] = $v;
    }

    return [$datos, $crudos, $errores];
}

/** Comprueba que los campos únicos no se repiten en otro registro. */
function comprobar_unicos(string $tabla, array $datos, ?int $id = null): array
{
    $errores = [];
    foreach (def($tabla)['campos'] as $nombre => $campo) {
        if (empty($campo['unico']) || ($datos[$nombre] ?? null) === null) {
            continue;
        }
        $st = conectar()->prepare("SELECT COUNT(*) FROM $tabla WHERE LOWER($nombre) = LOWER(?) AND id <> ?");
        $st->execute([$datos[$nombre], $id ?? 0]);
        if ((int) $st->fetchColumn() > 0) {
            $errores[$nombre] = "Ya existe otro registro con ese valor en «{$campo['label']}».";
        }
    }
    return $errores;
}

/** Pinta el control de formulario de un campo. */
function campo_html(string $nombre, array $campo, $valor, ?string $error): string
{
    $id    = 'f_' . $nombre;
    $req   = !empty($campo['req']);
    $clase = $error ? ' is-invalid' : '';
    $attr  = ' id="' . $id . '" name="' . $nombre . '"' . ($req ? ' required' : '');
    $label = '<label class="form-label" for="' . $id . '">' . e($campo['label'])
           . ($req ? ' <span class="text-danger" title="Obligatorio">*</span>' : '') . '</label>';
    $ayuda = isset($campo['ayuda']) ? '<div class="form-text">' . e($campo['ayuda']) . '</div>' : '';
    $err   = $error ? '<div class="invalid-feedback">' . e($error) . '</div>' : '';
    $col   = 'col-md-' . ($campo['col'] ?? 6);

    switch ($campo['tipo']) {
        case 'bool':
            return '<div class="' . $col . '"><div class="form-check form-switch mt-md-2">'
                 . '<input class="form-check-input" type="checkbox" role="switch" value="1"' . $attr
                 . ((int) $valor === 1 ? ' checked' : '') . '>'
                 . '<label class="form-check-label" for="' . $id . '">' . e($campo['label']) . '</label></div></div>';

        case 'fk':
            $html = '<select class="form-select' . $clase . '"' . $attr . '>';
            $html .= '<option value="">' . e($campo['vacio'] ?? 'Elige una opción') . '</option>';
            foreach (opciones($campo['tabla']) as $op => $texto) {
                $html .= '<option value="' . $op . '"' . ((string) $op === (string) $valor ? ' selected' : '') . '>' . e($texto) . '</option>';
            }
            $html .= '</select>';
            break;

        case 'textarea':
            $html = '<textarea class="form-control' . $clase . '" rows="3"' . $attr
                  . (isset($campo['max']) ? ' maxlength="' . $campo['max'] . '"' : '') . '>' . e($valor) . '</textarea>';
            break;

        case 'entero':
        case 'decimal':
            $step = $campo['tipo'] === 'entero' ? '1' : '0.01';
            $html = '<input type="number" inputmode="decimal" class="form-control' . $clase . '" step="' . $step . '"' . $attr
                  . (isset($campo['min']) ? ' min="' . $campo['min'] . '"' : '')
                  . (isset($campo['max']) ? ' max="' . $campo['max'] . '"' : '')
                  . ' value="' . e($valor) . '">';
            break;

        case 'fecha':
            $html = '<input type="date" class="form-control' . $clase . '"' . $attr
                  . (!empty($campo['no_futura']) ? ' max="' . date('Y-m-d') . '"' : '')
                  . ' value="' . e($valor) . '">';
            break;

        case 'fechahora':
            $v = $valor ? substr(str_replace(' ', 'T', (string) $valor), 0, 16) : '';
            $html = '<input type="datetime-local" class="form-control' . $clase . '"' . $attr . ' value="' . e($v) . '">';
            break;

        default:
            $tipo = ['email' => 'email', 'tel' => 'tel'][$campo['tipo']] ?? 'text';
            $html = '<input type="' . $tipo . '" class="form-control' . $clase . '"' . $attr
                  . (isset($campo['max']) ? ' maxlength="' . $campo['max'] . '"' : '')
                  . ($campo['tipo'] === 'dni' ? ' maxlength="9" pattern="[0-9]{8}[A-Za-z]" style="text-transform:uppercase"' : '')
                  . (!empty($campo['patron']) ? ' pattern="' . e($campo['patron']) . '"' : '')
                  . ' value="' . e($valor) . '">';
    }

    return '<div class="' . $col . '">' . $label . $html . $ayuda . $err . '</div>';
}

/** Control para subir la imagen, con vista previa. */
function campo_imagen(?string $actual, ?string $error, bool $obligatoria): string
{
    $html = '<div class="col-12"><label class="form-label" for="f_imagen">Imagen'
          . ($obligatoria ? ' <span class="text-danger">*</span>' : '') . '</label>'
          . '<div class="d-flex gap-3 align-items-start">'
          . '<img id="vista_previa" src="' . e(url_imagen($actual)) . '" alt="Vista previa" class="miniatura-grande rounded border">'
          . '<div class="flex-grow-1">'
          . '<input type="file" class="form-control' . ($error ? ' is-invalid' : '') . '" id="f_imagen" name="imagen"'
          . ' accept="image/jpeg,image/png,image/webp,image/gif"' . ($obligatoria ? ' required' : '') . '>'
          . '<div class="form-text">JPG, PNG, WEBP o GIF de hasta 2 MB.'
          . ($obligatoria ? '' : ' Si no eliges ninguna se conserva la imagen actual.') . '</div>'
          . ($error ? '<div class="invalid-feedback">' . e($error) . '</div>' : '')
          . '</div></div></div>';
    return $html;
}

// ------------------------------------------------------------------ imágenes

const TIPOS_IMAGEN = [
    IMAGETYPE_JPEG => ['jpg', 'image/jpeg'],
    IMAGETYPE_PNG  => ['png', 'image/png'],
    IMAGETYPE_WEBP => ['webp', 'image/webp'],
    IMAGETYPE_GIF  => ['gif', 'image/gif'],
];

/**
 * Comprueba la imagen subida en $_FILES['imagen'].
 * Devuelve [archivo temporal, extensión, mime] o un mensaje de error (string).
 * Si no se ha subido nada devuelve null.
 */
function validar_imagen()
{
    $f = $_FILES['imagen'] ?? null;
    if (!$f || $f['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($f['error'] === UPLOAD_ERR_INI_SIZE || $f['error'] === UPLOAD_ERR_FORM_SIZE || $f['size'] > IMG_MAX_BYTES) {
        return 'La imagen pesa más de 2 MB.';
    }
    if ($f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
        return 'No se ha podido subir la imagen. Inténtalo de nuevo.';
    }
    $info = @getimagesize($f['tmp_name']);
    if (!$info || !isset(TIPOS_IMAGEN[$info[2]])) {
        return 'El archivo no es una imagen válida (JPG, PNG, WEBP o GIF).';
    }
    [$ext, $mime] = TIPOS_IMAGEN[$info[2]];
    return [$f['tmp_name'], $ext, $mime];
}

/** Cabeceras para la API de Supabase Storage (claves antiguas JWT o nuevas sb_secret_). */
function cabeceras_supabase(array $extra = []): array
{
    $h = ['apikey: ' . SUPABASE_KEY];
    if (!str_starts_with(SUPABASE_KEY, 'sb_')) {
        $h[] = 'Authorization: Bearer ' . SUPABASE_KEY;
    }
    return array_merge($h, $extra);
}

/** Guarda la imagen y devuelve la ruta que se almacena en la base de datos. */
function guardar_imagen(string $tabla, array $imagen): string
{
    [$tmp, $ext, $mime] = $imagen;
    $nombre = $tabla . '/' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;

    if (SUPABASE_URL !== '' && SUPABASE_KEY !== '') {
        $ch = curl_init(SUPABASE_URL . '/storage/v1/object/' . SUPABASE_BUCKET . '/' . $nombre);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => file_get_contents($tmp),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_HTTPHEADER     => cabeceras_supabase(['Content-Type: ' . $mime, 'x-upsert: true']),
        ]);
        $respuesta = curl_exec($ch);
        $codigo = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($codigo < 200 || $codigo >= 300) {
            throw new RuntimeException('No se ha podido guardar la imagen en Supabase Storage (' . $codigo . '): ' . $respuesta);
        }
        return SUPABASE_URL . '/storage/v1/object/public/' . SUPABASE_BUCKET . '/' . $nombre;
    }

    $destino = __DIR__ . '/../img/subidas/' . $nombre;
    if (!is_dir(dirname($destino)) && !mkdir(dirname($destino), 0775, true)) {
        throw new RuntimeException('No se puede crear la carpeta img/subidas/.');
    }
    if (!move_uploaded_file($tmp, $destino)) {
        throw new RuntimeException('No se ha podido guardar la imagen en el servidor.');
    }
    return 'img/subidas/' . $nombre;
}

/** Borra una imagen subida por la aplicación (las de ejemplo no se tocan). */
function borrar_imagen(?string $ruta): void
{
    if (!$ruta) {
        return;
    }
    $prefijo = SUPABASE_URL . '/storage/v1/object/public/' . SUPABASE_BUCKET . '/';
    if (SUPABASE_URL !== '' && str_starts_with($ruta, $prefijo)) {
        $ch = curl_init(SUPABASE_URL . '/storage/v1/object/' . SUPABASE_BUCKET . '/' . substr($ruta, strlen($prefijo)));
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => 'DELETE',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_HTTPHEADER     => cabeceras_supabase(),
        ]);
        curl_exec($ch);
        curl_close($ch);
        return;
    }
    if (str_starts_with($ruta, 'img/subidas/') && !str_contains($ruta, '..')) {
        @unlink(__DIR__ . '/../' . $ruta);
    }
}

// ------------------------------------------------------------------ guardar

/** INSERT y devuelve el id nuevo. */
function insertar(string $tabla, array $datos): int
{
    $cols = array_keys($datos);
    $sql  = "INSERT INTO $tabla (" . implode(', ', $cols) . ') VALUES (' . implode(', ', array_fill(0, count($cols), '?')) . ')';
    $db   = conectar();
    if (DB_DRIVER === 'pgsql') {
        $st = $db->prepare($sql . ' RETURNING id');
        $st->execute(array_values($datos));
        return (int) $st->fetchColumn();
    }
    $st = $db->prepare($sql);
    $st->execute(array_values($datos));
    return (int) $db->lastInsertId();
}

function actualizar(string $tabla, int $id, array $datos): void
{
    $sets = implode(', ', array_map(fn($c) => "$c = ?", array_keys($datos)));
    $st = conectar()->prepare("UPDATE $tabla SET $sets WHERE id = ?");
    $st->execute([...array_values($datos), $id]);
}
