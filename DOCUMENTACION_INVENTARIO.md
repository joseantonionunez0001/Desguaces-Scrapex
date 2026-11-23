# Inventario de piezas: existencias por hueco y movimientos

Complementa a `DOCUMENTACION_ACCESO_POR_DESGUACE.md`. Todo lo que se describe aquí respeta el alcance por sede: cada usuario solo ve y modifica piezas, huecos, categorías y movimientos de su desguace.

## Modelo de datos

| Concepto | Dónde se guarda |
|---|---|
| Marca y modelo del vehículo | Catálogo común `marcas` y `modelos_vehiculo`. La pieza guarda `id_modelo_vehiculo` y la marca se obtiene a través del modelo. |
| Categoría | `categorias`, de la sede del usuario. El formulario solo pide el nombre y el servidor asigna `id_desguace`. `id_modulo` es opcional. |
| Tipo de pieza | `tipo_piezas`, vinculado a su categoría por `id_categoria`, que es la única fuente de esa relación. `fabricante` es opcional. |
| Existencias por hueco | `existencias_pieza` (pieza, etiqueta, cantidad). El hueco es una etiqueta del almacén interior: módulo, pasillo, estantería y etiqueta. Si la etiqueta es nula, el stock está **Pendiente de ubicar**. |
| Stock total | `piezas.stock`: la suma de las existencias. No se edita a mano. |
| Movimientos | `movimientos`, con pieza, usuario, origen, destino, cantidad, saldo anterior y posterior, motivo y `clave_envio`. |
| Estado físico | `piezas.estado` (Buen estado, Pendiente de revisar, Dañada). Es independiente de la disponibilidad. |

## Acciones de inventario

Todas pasan por `App\Services\InventarioPiezas`. Ese servicio guarda en la misma transacción:
- el movimiento;
- las existencias del hueco;
- el saldo de la pieza.

También bloquea las filas afectadas, para que dos operaciones simultáneas no descuenten las mismas unidades.

| Acción | Campos | Efecto |
|---|---|---|
| Entrada | cantidad, destino | Suma en el hueco de destino. |
| Compra | cantidad, destino | Suma en el hueco de destino. |
| Venta | cantidad, origen | Descuenta del hueco de origen. |
| Eliminar pieza | cantidad, origen y motivo; o «retirar la ficha completa» con motivo | Baja parcial: descuenta las unidades indicadas. Retirar la ficha registra una baja por cada hueco con unidades y archiva la pieza (borrado lógico), conservando su historial. Retirar la ficha exige `piezas.eliminar`. |
| Mover pieza | cantidad, origen, destino | Descuenta del origen y suma en el destino. El total no cambia. |

Reglas comunes:
- Se rechazan las cantidades no enteras o menores que 1.
- Se rechazan las salidas superiores a las unidades del hueco de origen.
- Se rechazan los traslados al mismo hueco.
- Se rechazan los huecos de otra sede.
- El origen puede ser «Pendiente de ubicar». El destino tiene que ser siempre un hueco.
- Cada formulario lleva una `clave_envio` (UUID). Si llega dos veces por un doble clic o un reenvío, la segunda no registra nada.
- El alta de una pieza registra su **Entrada inicial** con la cantidad recibida, el usuario y el hueco.
- Después del alta, el stock solo cambia con movimientos:
  - La edición de la pieza y la API rechazan el campo `stock`.
  - No hay borrado directo de piezas.
  - El historial no se edita ni se borra.

## Disponibilidad

Se calcula con el stock (`Pieza::disponibilidad()`):
- **Con stock**: hay unidades por encima del mínimo.
- **Stock bajo**: hay unidades, pero el saldo es igual o inferior al mínimo.
- **Agotada**: stock cero.

Se muestra en el listado, en la ficha y en el dashboard.

## Pantallas

- **Piezas**:
  - Listado con filtros combinables por nombre, marca, modelo, categoría, tipo, estado físico y disponibilidad. Al elegir una marca se ofrecen sus modelos, y al elegir una categoría, sus tipos. Los filtros se conservan al paginar y «Limpiar» los quita.
  - Alta con nombre, marca, modelo, categoría, tipo, estado físico, precio, stock mínimo, cantidad recibida y ubicación inicial.
  - Ficha con existencias por hueco, total e historial (fecha, usuario, acción, cantidad, saldo, origen, destino y motivo).
- **Movimientos**: historial de la sede con filtros, y alta en dos pasos (pieza y acción). Los campos se adaptan a la acción.
- **Categorías**: alta y listado, solo con el nombre.
- **Tipos de pieza**: pantalla propia con listado, alta y edición, cada tipo vinculado a su categoría.

Permisos:
- Registrar movimientos y dar de alta piezas: `movimientos.crear` (y `piezas.crear` para el alta).
- El operario solo consulta piezas y su ficha.

## Migraciones (incrementales, conservan los datos)

```
php artisan migrate
```

| Migración | Qué hace |
|---|---|
| `2026_11_23_191640_create_marcas_y_modelos_vehiculo_tables` | Crea el catálogo y lo carga con los pares marca y modelo que ya existían en vehículos y tipos de pieza. |
| `2026_11_23_191642_simplificar_categorias_y_tipos_de_pieza` | `categorias.id_modulo`, `tipo_piezas.fabricante` y `tipo_piezas.categoria` (texto) pasan a ser opcionales. Los valores anteriores se conservan. |
| `2026_11_23_191643_add_modelo_vehiculo_to_piezas_table` | Añade `piezas.id_modelo_vehiculo`, que admite nulos. Se rellena solo cuando el tipo de la pieza ya tenía marca y modelo registrados. |
| `2026_11_23_191645_create_existencias_pieza_table` | Crea las existencias y pasa el stock de cada pieza, incluidas las archivadas, a una fila «Pendiente de ubicar» con el mismo saldo. No asigna huecos por suposición. |
| `2026_11_23_191646_add_detalle_de_pieza_to_movimientos_table` | Añade las columnas nuevas, todas opcionales. Los movimientos anteriores se conservan como **históricos** de solo lectura. |

Las cinco tienen `down()`. He comprobado sobre una copia de la base local con datos que el saldo total se conserva (258 unidades antes y después), que se mantienen las piezas, movimientos, tipos y categorías, y que el rollback funciona.

Después de migrar, el equipo coloca el stock pendiente con **Mover pieza**, desde «Pendiente de ubicar» al hueco real.

## Pruebas

- `tests/Feature/Web/InventarioPiezasWebTest.php`:
  - Alta de 5 unidades en el hueco A con mínimo 2: se registra una sola Entrada.
  - Se mueven 2 unidades al hueco B y el total sigue en 5.
  - Se venden las 3 restantes de A: quedan 2 en B, aparece «Stock bajo» y el historial refleja las tres operaciones.
  - Se rechazan las salidas sin existencias suficientes, el doble envío, las cantidades inválidas, los traslados al mismo hueco y los huecos de otra sede.
  - Funcionan la baja parcial y la retirada de la ficha.
  - El stock antiguo pendiente de ubicar se conserva, y la migración mantiene el saldo de los registros antiguos.
- `PiezasWebTest` (filtros combinables, alta, edición), `MovimientosWebTest`, `CategoriasWebTest` y `TiposPiezaWebTest`.
- API: `AccesoPorDesguaceTest` y `AutorizacionApiTest`. Comprueban que el stock no se edita por PUT y que los movimientos van por `POST /api/movimientos`.

## API

- `POST /api/piezas`: `nombre`, `precio`, `estado`, `stock_minimo`, `id_tipo_pieza`, `id_modelo_vehiculo` (opcional), `cantidad`, `id_etiqueta` y `clave_envio` (opcional). Registra la Entrada inicial.
- `PUT /api/piezas/{id}`: solo datos descriptivos. Enviar `stock` da 422.
- `DELETE /api/piezas/{id}` con `{"motivo": "..."}`: retira la ficha completa.
- `POST /api/movimientos`: `id_pieza`, `accion`, `cantidad`, `origen` (id o `pendiente`), `destino`, `motivo`, `retirar_ficha` y `clave_envio`.
- `PUT` y `DELETE /api/movimientos/{id}`: responden 405.
