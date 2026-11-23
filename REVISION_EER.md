# Revisión del EER y decisiones técnicas

Fuente: `referencias/Desguace_EER.drawio` (18 entidades, 21 relaciones). Este documento recoge las inconsistencias detectadas y las decisiones tomadas al implementarlo. Lo que no se ha cambiado del modelo de negocio se deja documentado para revisión.

## 1. Inconsistencias del diagrama (sin cambios de modelo)

### 1.1 Relación Vehiculo–Categoria (1:N)
El diagrama une Vehiculo con Categoria, que es una agrupación de piezas. Un vehículo no es una categoría de piezas, así que la relación no se entiende bien.
- Implementación: `categorias.id_vehiculo` como clave foránea nullable.
- Propuesta: revisar si la relación debe existir. Si no, se elimina la columna.

### 1.2 Relación Desguace–Categoria (1:N)
Hay una relación directa entre Desguace y Categoria, además de la cadena Desguace > Modulo > Categoria.
- Implementación: `categorias.id_desguace` y `categorias.id_modulo` como FK independientes.
- Riesgo: una categoría puede apuntar a un desguace distinto del de su módulo. El seeder las mantiene coherentes, pero la base de datos no lo impide.
- Propuesta: eliminar `id_desguace` de categorias y obtener el desguace a través del módulo.

### 1.3 Relación Movimiento–Categoria (N:1)
Un movimiento de stock debería referirse a una pieza, no a una categoría.
- Implementación: `movimientos.id_categoria` según el diagrama.
- Propuesta: cambiar la relación a Movimiento N:1 Pieza. Es un cambio de modelo, así que no se ha aplicado.

### 1.4 Atributo `categoria` en Tipo_pieza y relación con Categoria
Tipo_pieza tiene un atributo de texto `categoria` y además una relación con Categoria.
- Implementación: se conservan ambos. El atributo es texto libre y la relación es `id_categoria`.
- Propuesta: decidir si el texto es redundante. Si lo es, se elimina.

### 1.5 Relación Pieza–Tipo_pieza
Pieza tiene N:1 con Tipo_pieza. Tipo_pieza ya se relaciona con Categoria. La cadena es coherente, pero el atributo `estado` y `stock` de Pieza no están vinculados a ubicaciones físicas. Esto no es un error, pero conviene tenerlo en cuenta en las consultas de stock.

### 1.6 Claves foráneas no dibujadas
El diagrama no mostraba varias claves foráneas que sí corresponden a relaciones 1:N. Se han añadido siguiendo la regla estándar de EER a relacional (la FK va en el lado N):
- `users.id_desguace`, `piezas.id_usuario`, `piezas.id_tipo_pieza`, `registro_sistema.id_usuario`, `vehiculos.id_desguace`, `categorias.id_desguace`, `categorias.id_modulo`, `categorias.id_vehiculo`, `tipo_piezas.id_categoria`, `movimientos.id_categoria`, `modulos.id_desguace`, `pasillos.id_modulo`, `estanterias.id_pasillo`, `etiquetas.id_estanteria`.

## 2. Claves primarias

| Entidad | Tabla | Clave primaria | Comentario |
|---|---|---|---|
| Usuario | users | `id` | Se mantiene la PK estándar de Laravel por compatibilidad con la autenticación de la fase 2. |
| Registro_Sistema | registro_sistema | `id_registro` | |
| Movimiento | movimientos | `id_movimiento` | |
| Pieza | piezas | `id_pieza` | |
| Desguace | desguaces | `id_desguace` | |
| Vehiculo | vehiculos | `id_vehiculo` | Atributo añadido al diagrama. `matricula` no es PK, se trata como única. |
| Categoria | categorias | `categoria_id` | Nombre no estándar del EER. Se mantiene. |
| Tipo_pieza | tipo_piezas | `id_pieza` | Mismo nombre que la PK de Pieza. Se mantiene por fidelidad al EER. La FK desde Pieza se llama `id_tipo_pieza`. |
| Modulo | modulos | `id_modulo` | |
| Pasillo | pasillos | `id_pasillo` | |
| Estanteria | estanterias | `id_estanteria` | |
| Etiqueta | etiquetas | `id_etiqueta` | |
| Patio | patios | `id_patio` | |
| Sector | sectores | `id_sector` | |
| Fila | filas | `id_fila` | |
| Posicion | posiciones | `id_posicion` | |
| Nivel | niveles | `id_nivel` | |
| Ubicacion_Vehiculo | ubicaciones_vehiculo | `id_ubicacion` | |

## 3. Decisiones técnicas

- Nombre de columna `ubicación` con tilde, tal como aparece en el diagrama. MySQL lo acepta con charset UTF-8. Si algún cliente tuviera problemas con el acento, se puede renombrar a `ubicacion`.
- Borrado lógico (`SoftDeletes`) en todas las entidades de negocio, incluida la tabla `users`. El registro de auditoría `registro_sistema` no tiene borrado ni timestamps.
- Ninguna clave foránea usa `cascadeOnDelete`. Se usa `restrictOnDelete` para no perder historial por accidente.
- `ubicaciones_vehiculo` no tiene restricción única en `id_nivel` ni en `id_vehiculo`. Una posición puede usarse varias veces a lo largo del tiempo. Se añade un índice por `(id_nivel, fecha_inicio)`.
- `matricula` y `numero_bastidor` son únicos. El diagrama no lo indica, pero evita duplicar vehículos.
- `users.id_desguace` es nullable porque un usuario puede no estar asignado a un desguace concreto.
- Los roles y permisos no se implementan. Se usará Spatie Laravel Permission en la fase 2. No existe entidad Rol ni columna `id_rol`.

## 4. Puntos pendientes de decisión

1. ¿La relación Movimiento–Categoria debe pasar a Movimiento–Pieza? (1.3)
2. ¿Vehiculo–Categoria es necesaria? (1.1)
3. ¿Desguace–Categoria debe eliminarse y obtenerse a través del módulo? (1.2)
4. ¿El atributo `categoria` de Tipo_pieza debe eliminarse? (1.4)
