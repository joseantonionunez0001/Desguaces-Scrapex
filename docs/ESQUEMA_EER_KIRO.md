# Esquema entidad–relación del desguace — versión legible para Kiro

> Generado a partir de `Desguace_EER_Patios_Estilo_Original.drawio` del proyecto. Documento de consulta para agentes de Kiro IDE; el archivo `.drawio` sigue siendo el diagrama visual de referencia.

## IMPORTANTE: antes de implementar

- **Los atributos listados son exactamente los del diagrama**, no una especificación completa de las columnas SQL: el EER no recoge todos los campos de relación ni los tipos de datos.
- No crear migraciones o modificar tablas existentes de manera automática solo a partir de este fichero. Consultar también `CONTRATO_BASE_DE_DATOS.md`, `AGENTS.md` y las migraciones existentes.
- **Convención del proyecto Laravel:** `id` como PK, tablas pluralizadas en minúsculas, FKs `<entidad>_id`. Los `id_xxx` de este documento son las etiquetas originales del EER conceptual, no necesariamente nombres físicos de columnas.
- El esquema presenta nomenclatura ambigua en `Categoria` (`categoria_id` como atributo) y `Tipo_pieza` (`id_pieza` en lugar de `id_tipo_pieza`): tratar como aspectos por resolver con el coordinador.
- `Vehiculo` ya existe en Laravel y el diagrama incorpora atributos nuevos; evitar recrear su tabla. `Pieza` también existe: crear migraciones nuevas para ampliar.

## Entidades y atributos exactos del EER

### Rol

Campos dibujados: `id_rol`, `nombre`.

### Usuario

Campos dibujados: `id_usuario`, `nombre`, `correo`, `contraseña`.

### Registro_Sistema

Campos dibujados: `id_registro`, `fecha_hora`.

### Movimiento

Campos dibujados: `id_movimiento`, `tipo_movimiento`, `fecha`, `cantidad`.

### Pieza

Campos dibujados: `id_pieza`, `nombre`, `precio`, `estado`, `stock`, `stock_minimo`.

### Desguace

Campos dibujados: `id_desguace`, `nombre`, `direccion`, `numero_telefono`, `ubicación`.

### Vehiculo

Campos dibujados: `id_vehiculo`, `matricula`, `marca`, `modelo`, `fecha_llegada`, `numero_bastidor`, `estado_tratamiento`.

### Categoria

Campos dibujados: `categoria_id`, `nombre`.

### Tipo_pieza

Campos dibujados: `id_pieza`, `nombre`, `categoria`, `fabricante`.

### Modulo

Campos dibujados: `id_modulo`, `nombre_modulo`.

### Pasillo

Campos dibujados: `id_pasillo`, `numero_pasillo`.

### Estanteria

Campos dibujados: `id_estanteria`, `numero_estanteria`.

### Etiqueta

Campos dibujados: `id_etiqueta`, `numero_etiqueta`.

### Patio

Campos dibujados: `id_patio`, `id_desguace`, `codigo`, `nombre`.

### Sector

Campos dibujados: `id_sector`, `id_patio`, `codigo`, `tipo_zona`.

### Fila

Campos dibujados: `id_fila`, `id_sector`, `numero_fila`.

### Posicion

Campos dibujados: `id_posicion`, `id_fila`, `numero_posicion`, `capacidad_maxima`.

### Nivel

Campos dibujados: `id_nivel`, `id_posicion`, `numero_nivel`.

### Ubicacion_Vehiculo

Campos dibujados: `id_ubicacion`, `id_vehiculo`, `id_nivel`, `fecha_inicio`, `fecha_fin`.

## Relaciones de almacenamiento que deben respetarse

- **Almacén de piezas:** `Modulo` (1) → `Pasillo` (N) → `Estanteria` (N) → `Etiqueta` (N).
- **Patios exteriores:** `Desguace` (1) → `Patio` (N) → `Sector` (N) → `Fila` (N) → `Posicion` (N) → `Nivel` (N).
- **Historial:** `Vehiculo` (1) → `Ubicacion_Vehiculo` (N) y `Nivel` (1) → `Ubicacion_Vehiculo` (N); cada registro de ubicación tiene `fecha_inicio` y `fecha_fin` opcional.
- Hay que garantizar mediante validación/índices y transacciones que no haya dos ubicaciones activas para el mismo vehículo ni dos vehículos distintos en un mismo nivel simultáneamente.
- **Vehículos y piezas:** el proyecto actual contiene una tabla pivote `pieza_vehiculo` de relación N:M. Conservarla hasta que el equipo decida su semántica; no asumir que representa el coche de procedencia de cada unidad física.

## Convenciones de trabajo en Kiro Crew

1. Cada integrante trabaja en su propia rama, solo sobre sus entidades asignadas.
2. Para seguir el patrón de CRUD, inspeccionar el ejemplo existente `app/Http/Controllers/DesguaceApiController.php`, `app/Models/Desguace.php`, su migración, factory y seeder antes de programar.
3. No tocar `routes/api.php` ni `database/seeders/DatabaseSeeder.php` desde varias ramas; dejar instrucciones de integración al coordinador.
4. No pisar implementaciones ya existentes de `Vehiculo`, `Pieza`, `Usuario`/`users` ni datos reales.
5. Revisar restricciones de claves foráneas y orden de migraciones antes de fusionar.

## Archivos que los agentes deben revisar en el mismo workspace

- `AGENTS.md`
- `CONTRATO_BASE_DE_DATOS.md`
- `PLAN_EQUIPO_LARAVEL.md`
- `PROMPTS_KIRO_CREW.md`
- `ESQUEMA_EER_KIRO.md` (este documento)
- `app/Models/`, `database/migrations/`, `database/factories/`, `database/seeders/`, `app/Http/Controllers/`
