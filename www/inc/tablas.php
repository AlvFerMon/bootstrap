<?php
// Definición de las tablas de la base de datos.
//
// Cada página (alta, consulta, modificar, borrar, listado y filtrar) lee esta
// configuración para saber qué campos tiene cada tabla, cómo se muestran en
// los formularios y qué búsquedas y filtros admite.
//
// Claves de cada tabla:
//   titulo / singular / genero  textos de la interfaz ('o' = masculino, 'a' = femenino)
//   icono       icono de Bootstrap Icons
//   grupo       'principal' (menú propio) o 'catalogo' (menú Catálogos)
//   etiqueta    expresión SQL (alias x) con el texto que se ve en los desplegables
//   corta       (opcional) texto más breve para las columnas de los listados
//   orden       ORDER BY de los listados
//   imagen      columna con la ruta de la imagen
//   campos      columnas editables (tipo: texto, textarea, email, tel, dni,
//               entero, decimal, bool, fecha, fechahora, fk); 'unico' => true
//               comprueba antes de guardar que el valor no está repetido
//   busqueda    campos por los que se puede consultar
//   clave_alt   campo único para borrar sin conocer el id
//   filtros     filtros del listado parametrizado (contiene, igual, bool, rango)

const TABLAS = [

    // ================================================================ PRINCIPALES

    'propietarios' => [
        'titulo'    => 'Propietarios',
        'singular'  => 'propietario',
        'genero'    => 'o',
        'icono'     => 'bi-person-vcard',
        'grupo'     => 'principal',
        'etiqueta'  => "CONCAT(x.nombre, ' ', x.apellidos, ' (', x.dni, ')')",
        'corta'     => "CONCAT(x.nombre, ' ', x.apellidos)",
        'orden'     => 'apellidos, nombre',
        'imagen'    => 'foto',
        'campos'    => [
            'nombre'            => ['label' => 'Nombre', 'tipo' => 'texto', 'req' => true, 'max' => 50, 'lista' => true, 'col' => 4],
            'apellidos'         => ['label' => 'Apellidos', 'tipo' => 'texto', 'req' => true, 'max' => 100, 'lista' => true, 'col' => 8],
            'dni'               => ['label' => 'DNI', 'tipo' => 'dni', 'req' => true, 'unico' => true, 'lista' => true, 'col' => 4, 'ayuda' => '8 números y la letra, p. ej. 12345678Z'],
            'telefono'          => ['label' => 'Teléfono', 'tipo' => 'tel', 'req' => true, 'max' => 15, 'lista' => true, 'col' => 4],
            'email'             => ['label' => 'Correo electrónico', 'tipo' => 'email', 'max' => 100, 'unico' => true, 'col' => 4],
            'direccion'         => ['label' => 'Dirección', 'tipo' => 'texto', 'max' => 150, 'col' => 12],
            'tipo_socio_id'     => ['label' => 'Tipo de socio', 'tipo' => 'fk', 'tabla' => 'tipos_socio', 'vacio' => 'No es socio', 'lista' => true, 'col' => 4],
            'saldo_pendiente'   => ['label' => 'Saldo pendiente (€)', 'tipo' => 'decimal', 'req' => true, 'min' => 0, 'max' => 999999.99, 'defecto' => '0', 'lista' => true, 'moneda' => true, 'col' => 4],
            'fecha_alta'        => ['label' => 'Fecha de alta', 'tipo' => 'fecha', 'req' => true, 'defecto' => 'hoy', 'col' => 4],
            'acepta_publicidad' => ['label' => 'Acepta recibir publicidad', 'tipo' => 'bool', 'lista' => true, 'col' => 12],
        ],
        'busqueda'  => [
            'dni'       => ['label' => 'DNI', 'sql' => 't.dni', 'modo' => 'like'],
            'apellidos' => ['label' => 'Apellidos', 'sql' => 't.apellidos', 'modo' => 'like'],
            'nombre'    => ['label' => 'Nombre', 'sql' => 't.nombre', 'modo' => 'like'],
            'telefono'  => ['label' => 'Teléfono', 'sql' => 't.telefono', 'modo' => 'like'],
        ],
        'clave_alt' => 'dni',
        'filtros'   => [
            'apellidos'         => 'contiene',
            'tipo_socio_id'     => 'igual',
            'acepta_publicidad' => 'bool',
            'saldo_pendiente'   => 'rango',
            'fecha_alta'        => 'rango',
        ],
    ],

    'mascotas' => [
        'titulo'    => 'Mascotas',
        'singular'  => 'mascota',
        'genero'    => 'a',
        'icono'     => 'bi-heart-pulse',
        'grupo'     => 'principal',
        'etiqueta'  => "CONCAT(x.nombre, ' - ', (SELECT e.nombre FROM especies e WHERE e.id = x.especie_id), ' de ', (SELECT p.nombre FROM propietarios p WHERE p.id = x.propietario_id), ' ', (SELECT p.apellidos FROM propietarios p WHERE p.id = x.propietario_id))",
        'corta'     => "CONCAT(x.nombre, ' (', (SELECT e.nombre FROM especies e WHERE e.id = x.especie_id), ')')",
        'orden'     => 'nombre',
        'imagen'    => 'foto',
        'campos'    => [
            'nombre'           => ['label' => 'Nombre', 'tipo' => 'texto', 'req' => true, 'max' => 50, 'lista' => true, 'col' => 4],
            'especie_id'       => ['label' => 'Especie', 'tipo' => 'fk', 'tabla' => 'especies', 'req' => true, 'lista' => true, 'col' => 4],
            'raza'             => ['label' => 'Raza', 'tipo' => 'texto', 'max' => 50, 'lista' => true, 'col' => 4],
            'propietario_id'   => ['label' => 'Propietario', 'tipo' => 'fk', 'tabla' => 'propietarios', 'req' => true, 'lista' => true, 'col' => 8],
            'num_chip'         => ['label' => 'Nº de microchip', 'tipo' => 'texto', 'unico' => true, 'max' => 15, 'patron' => '[0-9]{15}', 'ayuda' => '15 dígitos. Déjalo vacío si no tiene chip.', 'col' => 4],
            'peso_kg'          => ['label' => 'Peso (kg)', 'tipo' => 'decimal', 'req' => true, 'min' => 0.01, 'max' => 9999.99, 'lista' => true, 'col' => 4],
            'fecha_nacimiento' => ['label' => 'Fecha de nacimiento', 'tipo' => 'fecha', 'req' => true, 'no_futura' => true, 'lista' => true, 'col' => 4],
            'vacunada'         => ['label' => 'Vacunada', 'tipo' => 'bool', 'lista' => true, 'col' => 2],
            'esterilizada'     => ['label' => 'Esterilizada', 'tipo' => 'bool', 'lista' => true, 'col' => 2],
        ],
        'busqueda'  => [
            'nombre'      => ['label' => 'Nombre', 'sql' => 't.nombre', 'modo' => 'like'],
            'num_chip'    => ['label' => 'Nº de microchip', 'sql' => 't.num_chip', 'modo' => 'like'],
            'propietario' => ['label' => 'Apellidos del propietario', 'sql' => '(SELECT p.apellidos FROM propietarios p WHERE p.id = t.propietario_id)', 'modo' => 'like'],
        ],
        'clave_alt' => 'num_chip',
        'filtros'   => [
            'nombre'           => 'contiene',
            'especie_id'       => 'igual',
            'propietario_id'   => 'igual',
            'vacunada'         => 'bool',
            'esterilizada'     => 'bool',
            'peso_kg'          => 'rango',
            'fecha_nacimiento' => 'rango',
        ],
    ],

    'veterinarios' => [
        'titulo'    => 'Veterinarios',
        'singular'  => 'veterinario',
        'genero'    => 'o',
        'icono'     => 'bi-person-badge',
        'grupo'     => 'principal',
        'etiqueta'  => "CONCAT(x.nombre, ' ', x.apellidos, ' (', x.num_colegiado, ')')",
        'corta'     => "CONCAT(x.nombre, ' ', x.apellidos)",
        'orden'     => 'apellidos, nombre',
        'imagen'    => 'foto',
        'campos'    => [
            'nombre'             => ['label' => 'Nombre', 'tipo' => 'texto', 'req' => true, 'max' => 50, 'lista' => true, 'col' => 4],
            'apellidos'          => ['label' => 'Apellidos', 'tipo' => 'texto', 'req' => true, 'max' => 100, 'lista' => true, 'col' => 8],
            'num_colegiado'      => ['label' => 'Nº de colegiado', 'tipo' => 'texto', 'req' => true, 'unico' => true, 'max' => 20, 'lista' => true, 'col' => 4, 'ayuda' => 'Por ejemplo SE-1234'],
            'especialidad_id'    => ['label' => 'Especialidad', 'tipo' => 'fk', 'tabla' => 'especialidades', 'req' => true, 'lista' => true, 'col' => 8],
            'telefono'           => ['label' => 'Teléfono', 'tipo' => 'tel', 'max' => 15, 'lista' => true, 'col' => 4],
            'email'              => ['label' => 'Correo electrónico', 'tipo' => 'email', 'max' => 100, 'col' => 8],
            'anios_experiencia'  => ['label' => 'Años de experiencia', 'tipo' => 'entero', 'req' => true, 'min' => 0, 'max' => 60, 'defecto' => '0', 'lista' => true, 'col' => 4],
            'fecha_contratacion' => ['label' => 'Fecha de contratación', 'tipo' => 'fecha', 'req' => true, 'defecto' => 'hoy', 'col' => 4],
            'hace_urgencias'     => ['label' => 'Hace guardias de urgencias', 'tipo' => 'bool', 'lista' => true, 'col' => 4],
        ],
        'busqueda'  => [
            'apellidos'     => ['label' => 'Apellidos', 'sql' => 't.apellidos', 'modo' => 'like'],
            'num_colegiado' => ['label' => 'Nº de colegiado', 'sql' => 't.num_colegiado', 'modo' => 'like'],
            'nombre'        => ['label' => 'Nombre', 'sql' => 't.nombre', 'modo' => 'like'],
        ],
        'clave_alt' => 'num_colegiado',
        'filtros'   => [
            'apellidos'          => 'contiene',
            'especialidad_id'    => 'igual',
            'hace_urgencias'     => 'bool',
            'anios_experiencia'  => 'rango',
            'fecha_contratacion' => 'rango',
        ],
    ],

    'citas' => [
        'titulo'    => 'Citas',
        'singular'  => 'cita',
        'genero'    => 'a',
        'icono'     => 'bi-calendar-check',
        'grupo'     => 'principal',
        'etiqueta'  => "CONCAT('#', x.id, ' · ', x.fecha_hora, ' · ', (SELECT m.nombre FROM mascotas m WHERE m.id = x.mascota_id), ' · ', x.motivo)",
        'orden'     => 'fecha_hora DESC',
        'imagen'    => 'imagen',
        'campos'    => [
            'fecha_hora'     => ['label' => 'Fecha y hora', 'tipo' => 'fechahora', 'req' => true, 'lista' => true, 'col' => 4],
            'mascota_id'     => ['label' => 'Mascota', 'tipo' => 'fk', 'tabla' => 'mascotas', 'req' => true, 'lista' => true, 'col' => 8],
            'veterinario_id' => ['label' => 'Veterinario', 'tipo' => 'fk', 'tabla' => 'veterinarios', 'req' => true, 'lista' => true, 'col' => 6],
            'servicio_id'    => ['label' => 'Servicio', 'tipo' => 'fk', 'tabla' => 'servicios', 'req' => true, 'lista' => true, 'col' => 6],
            'motivo'         => ['label' => 'Motivo', 'tipo' => 'texto', 'req' => true, 'max' => 150, 'lista' => true, 'col' => 8],
            'precio_final'   => ['label' => 'Precio final (€)', 'tipo' => 'decimal', 'req' => true, 'min' => 0, 'max' => 99999.99, 'defecto' => '0', 'lista' => true, 'moneda' => true, 'col' => 4],
            'observaciones'  => ['label' => 'Observaciones', 'tipo' => 'textarea', 'max' => 2000, 'col' => 12],
            'atendida'       => ['label' => 'Cita ya atendida', 'tipo' => 'bool', 'lista' => true, 'col' => 12],
        ],
        'busqueda'  => [
            'fecha'   => ['label' => 'Fecha', 'sql' => 'CAST(t.fecha_hora AS DATE)', 'modo' => 'fecha'],
            'mascota' => ['label' => 'Nombre de la mascota', 'sql' => '(SELECT m.nombre FROM mascotas m WHERE m.id = t.mascota_id)', 'modo' => 'like'],
            'motivo'  => ['label' => 'Motivo', 'sql' => 't.motivo', 'modo' => 'like'],
        ],
        'clave_alt' => null,
        'filtros'   => [
            'motivo'         => 'contiene',
            'mascota_id'     => 'igual',
            'veterinario_id' => 'igual',
            'servicio_id'    => 'igual',
            'atendida'       => 'bool',
            'precio_final'   => 'rango',
            'fecha_hora'     => 'rango',
        ],
    ],

    // ================================================================ CATÁLOGOS

    'tipos_socio' => [
        'titulo'    => 'Tipos de socio',
        'singular'  => 'tipo de socio',
        'genero'    => 'o',
        'icono'     => 'bi-award',
        'grupo'     => 'catalogo',
        'etiqueta'  => 'x.nombre',
        'orden'     => 'cuota_mensual, nombre',
        'imagen'    => 'imagen',
        'campos'    => [
            'nombre'         => ['label' => 'Nombre', 'tipo' => 'texto', 'req' => true, 'unico' => true, 'max' => 40, 'lista' => true, 'col' => 6],
            'fecha_creacion' => ['label' => 'Fecha de creación', 'tipo' => 'fecha', 'req' => true, 'defecto' => 'hoy', 'col' => 6],
            'descripcion'    => ['label' => 'Descripción', 'tipo' => 'texto', 'max' => 255, 'lista' => true, 'col' => 12],
            'cuota_mensual'  => ['label' => 'Cuota mensual (€)', 'tipo' => 'decimal', 'req' => true, 'min' => 0, 'max' => 9999.99, 'defecto' => '0', 'lista' => true, 'moneda' => true, 'col' => 6],
            'descuento_pct'  => ['label' => 'Descuento (%)', 'tipo' => 'decimal', 'req' => true, 'min' => 0, 'max' => 100, 'defecto' => '0', 'lista' => true, 'col' => 6],
            'activo'         => ['label' => 'Plan activo', 'tipo' => 'bool', 'defecto' => '1', 'lista' => true, 'col' => 12],
        ],
        'busqueda'  => ['nombre' => ['label' => 'Nombre', 'sql' => 't.nombre', 'modo' => 'like']],
        'clave_alt' => 'nombre',
        'filtros'   => ['nombre' => 'contiene', 'activo' => 'bool', 'cuota_mensual' => 'rango', 'descuento_pct' => 'rango'],
    ],

    'especies' => [
        'titulo'    => 'Especies',
        'singular'  => 'especie',
        'genero'    => 'a',
        'icono'     => 'bi-tags',
        'grupo'     => 'catalogo',
        'etiqueta'  => 'x.nombre',
        'orden'     => 'nombre',
        'imagen'    => 'imagen',
        'campos'    => [
            'nombre'               => ['label' => 'Nombre', 'tipo' => 'texto', 'req' => true, 'unico' => true, 'max' => 40, 'lista' => true, 'col' => 6],
            'esperanza_vida_anios' => ['label' => 'Esperanza de vida (años)', 'tipo' => 'entero', 'req' => true, 'min' => 0, 'max' => 255, 'lista' => true, 'col' => 6],
            'fecha_alta'           => ['label' => 'Fecha de alta', 'tipo' => 'fecha', 'req' => true, 'defecto' => 'hoy', 'lista' => true, 'col' => 6],
            'es_exotica'           => ['label' => 'Es una especie exótica', 'tipo' => 'bool', 'lista' => true, 'col' => 6],
        ],
        'busqueda'  => ['nombre' => ['label' => 'Nombre', 'sql' => 't.nombre', 'modo' => 'like']],
        'clave_alt' => 'nombre',
        'filtros'   => ['nombre' => 'contiene', 'es_exotica' => 'bool', 'esperanza_vida_anios' => 'rango'],
    ],

    'especialidades' => [
        'titulo'    => 'Especialidades',
        'singular'  => 'especialidad',
        'genero'    => 'a',
        'icono'     => 'bi-clipboard2-pulse',
        'grupo'     => 'catalogo',
        'etiqueta'  => 'x.nombre',
        'orden'     => 'nombre',
        'imagen'    => 'imagen',
        'campos'    => [
            'nombre'             => ['label' => 'Nombre', 'tipo' => 'texto', 'req' => true, 'unico' => true, 'max' => 60, 'lista' => true, 'col' => 6],
            'fecha_alta'         => ['label' => 'Fecha de alta', 'tipo' => 'fecha', 'req' => true, 'defecto' => 'hoy', 'col' => 6],
            'descripcion'        => ['label' => 'Descripción', 'tipo' => 'texto', 'max' => 255, 'lista' => true, 'col' => 12],
            'recargo_consulta'   => ['label' => 'Recargo por consulta (€)', 'tipo' => 'decimal', 'req' => true, 'min' => 0, 'max' => 9999.99, 'defecto' => '0', 'lista' => true, 'moneda' => true, 'col' => 6],
            'requiere_quirofano' => ['label' => 'Requiere quirófano', 'tipo' => 'bool', 'lista' => true, 'col' => 6],
        ],
        'busqueda'  => ['nombre' => ['label' => 'Nombre', 'sql' => 't.nombre', 'modo' => 'like']],
        'clave_alt' => 'nombre',
        'filtros'   => ['nombre' => 'contiene', 'requiere_quirofano' => 'bool', 'recargo_consulta' => 'rango'],
    ],

    'servicios' => [
        'titulo'    => 'Servicios',
        'singular'  => 'servicio',
        'genero'    => 'o',
        'icono'     => 'bi-bandaid',
        'grupo'     => 'catalogo',
        'etiqueta'  => "CONCAT(x.nombre, ' (', x.precio_base, ' €)')",
        'corta'     => 'x.nombre',
        'orden'     => 'nombre',
        'imagen'    => 'imagen',
        'campos'    => [
            'nombre'         => ['label' => 'Nombre', 'tipo' => 'texto', 'req' => true, 'unico' => true, 'max' => 80, 'lista' => true, 'col' => 6],
            'fecha_alta'     => ['label' => 'Fecha de alta', 'tipo' => 'fecha', 'req' => true, 'defecto' => 'hoy', 'col' => 6],
            'descripcion'    => ['label' => 'Descripción', 'tipo' => 'texto', 'max' => 255, 'lista' => true, 'col' => 12],
            'precio_base'    => ['label' => 'Precio base (€)', 'tipo' => 'decimal', 'req' => true, 'min' => 0, 'max' => 99999.99, 'lista' => true, 'moneda' => true, 'col' => 4],
            'duracion_min'   => ['label' => 'Duración (minutos)', 'tipo' => 'entero', 'req' => true, 'min' => 1, 'max' => 1440, 'lista' => true, 'col' => 4],
            'requiere_ayuno' => ['label' => 'Requiere ayuno', 'tipo' => 'bool', 'lista' => true, 'col' => 4],
        ],
        'busqueda'  => ['nombre' => ['label' => 'Nombre', 'sql' => 't.nombre', 'modo' => 'like']],
        'clave_alt' => 'nombre',
        'filtros'   => ['nombre' => 'contiene', 'requiere_ayuno' => 'bool', 'precio_base' => 'rango', 'duracion_min' => 'rango'],
    ],
];

// Operaciones que ofrece cada tabla (submenú y navbar)
const OPERACIONES = [
    'listado'   => ['Listado completo', 'bi-list-ul'],
    'filtrar'   => ['Listado con filtros', 'bi-funnel'],
    'consulta'  => ['Consultar', 'bi-search'],
    'alta'      => ['Nueva alta', 'bi-plus-circle'],
    'modificar' => ['Modificar', 'bi-pencil-square'],
    'borrar'    => ['Borrar', 'bi-trash'],
];
