# Documentación del backend — Sistema de gestión de desguaces

## 1. Descripción general

API REST en Laravel 13 (PHP 8.5) para gestionar un desguace: vehículos, piezas, almacenamiento interior y exterior, y el historial de ubicaciones de los vehículos.

El esquema sigue el diagrama `referencias/Desguace_EER.drawio`. Las decisiones y las inconsistencias detectadas están en `REVISION_EER.md`.

Fase actual: backend CRUD sin autenticación. Los roles y permisos se implementarán en la fase 2 con Spatie Laravel Permission.

## 2. Entidades implementadas

18 entidades del EER: Usuario, Registro_Sistema, Movimiento, Pieza, Desguace, Vehiculo, Categoria, Tipo_pieza, Modulo, Pasillo, Estanteria, Etiqueta, Patio, Sector, Fila, Posicion, Nivel y Ubicacion_Vehiculo.

## 3. Tablas

| Tabla | Clave primaria | Columnas principales | Claves foráneas |
|---|---|---|---|
| users | id | name, email, password, id_desguace, deleted_at | id_desguace → desguaces |
| desguaces | id_desguace | nombre, direccion, numero_telefono, ubicación | — |
| modulos | id_modulo | nombre_modulo | id_desguace → desguaces |
| pasillos | id_pasillo | numero_pasillo | id_modulo → modulos |
| estanterias | id_estanteria | numero_estanteria | id_pasillo → pasillos |
| etiquetas | id_etiqueta | numero_etiqueta | id_estanteria → estanterias |
| patios | id_patio | codigo, nombre | id_desguace → desguaces |
| sectores | id_sector | codigo, tipo_zona | id_patio → patios |
| filas | id_fila | numero_fila | id_sector → sectores |
| posiciones | id_posicion | numero_posicion, capacidad_maxima | id_fila → filas |
| niveles | id_nivel | numero_nivel | id_posicion → posiciones |
| vehiculos | id_vehiculo | matricula (única), marca, modelo, fecha_llegada, numero_bastidor (única), estado_tratamiento | id_desguace → desguaces |
| ubicaciones_vehiculo | id_ubicacion | fecha_inicio, fecha_fin | id_vehiculo → vehiculos, id_nivel → niveles |
| categorias | categoria_id | nombre | id_desguace, id_modulo, id_vehiculo (nullable) |
| tipo_piezas | id_pieza | nombre, fabricante, categoria | id_categoria → categorias |
| piezas | id_pieza | nombre, precio, estado, stock, stock_minimo | id_usuario → users, id_tipo_pieza → tipo_piezas |
| movimientos | id_movimiento | tipo_movimiento, fecha, cantidad | id_categoria → categorias |
| registro_sistema | id_registro | fecha_hora | id_usuario → users |

Todas las tablas de negocio tienen `deleted_at` (borrado lógico) y `created_at`/`updated_at`, salvo `registro_sistema`.

## 4. Relaciones

Las 21 relaciones del EER están implementadas en los modelos con claves foráneas explícitas:

- Usuario 1:N Registro_Sistema, Usuario 1:N Pieza, Usuario N:1 Desguace.
- Desguace 1:N Vehiculo, Desguace 1:N Categoria, Desguace 1:N Modulo, Desguace 1:N Patio.
- Modulo 1:N Pasillo → Pasillo 1:N Estanteria → Estanteria 1:N Etiqueta.
- Patio 1:N Sector → Sector 1:N Fila → Fila 1:N Posicion → Posicion 1:N Nivel → Nivel 1:N Ubicacion_Vehiculo.
- Vehiculo 1:N Ubicacion_Vehiculo (historial, ordenado por `fecha_inicio` descendente).
- Vehiculo 1:N Categoria, Categoria N:1 Modulo, Categoria 1:N Tipo_pieza, Categoria 1:N Movimiento.
- Pieza N:1 Tipo_pieza.

Nota: `TipoPieza::categoriaRelacionada()` se llama así porque `categoria` es el nombre de una columna de texto.

## 5. Modelos

Ubicados en `app/Models/`. Cada uno declara `$table`, `$primaryKey`, `$fillable`, casts de fecha cuando procede, y `SoftDeletes` salvo `RegistroSistema`.

`User` se ha adaptado (no se ha creado un modelo nuevo). Conserva `name`, `email`, `password` y la PK `id`. La contraseña se oculta en la serialización.

## 6. Controladores API

Ubicados en `app/Http/Controllers/Api/`. Cada uno implementa `index`, `show`, `store`, `update` y `destroy`, salvo los indicados.

Públicos (registrados en `routes/api.php`): DesguaceApiController, ModuloApiController, PasilloApiController, EstanteriaApiController, EtiquetaApiController, PatioApiController, SectorApiController, FilaApiController, PosicionApiController, NivelApiController, VehiculoApiController, UbicacionVehiculoApiController, CategoriaApiController, TipoPiezaApiController, PiezaApiController, MovimientoApiController.

No registrados (pendientes de proteger en la fase 2):
- UsuarioApiController: CRUD completo. Valida email único y contraseña mínima de 8 caracteres.
- RegistroSistemaApiController: solo `index`, `show` y `store`. No tiene update ni destroy, para no modificar auditoría.

## 7. Rutas API

Base: `/api`. Para cada recurso se generan los verbos GET (index y show), POST, PUT/PATCH y DELETE.

| Recurso | Ruta |
|---|---|
| Desguaces | /api/desguaces |
| Módulos | /api/modulos |
| Pasillos | /api/pasillos |
| Estanterías | /api/estanterias |
| Etiquetas | /api/etiquetas |
| Patios | /api/patios |
| Sectores | /api/sectores |
| Filas | /api/filas |
| Posiciones | /api/posiciones |
| Niveles | /api/niveles |
| Vehículos | /api/vehiculos |
| Ubicaciones de vehículos | /api/ubicaciones-vehiculo |
| Categorías | /api/categorias |
| Tipos de pieza | /api/tipo-piezas |
| Piezas | /api/piezas |
| Movimientos | /api/movimientos |

Comprobar con `php artisan route:list --path=api`.

## 8. Formato de respuestas

- Éxito: `{"data": ..., "success": 1}`. Código 200 en consulta y actualización, 201 en creación.
- Eliminación: 200 con `{"data": "<mensaje>", "success": 1}`.
- Validación: 422 (formato estándar de Laravel con `message` y `errors`).
- No encontrado: 404.

## 9. Factories y seeders

Factories en `database/factories/` para las 16 entidades de almacenamiento, vehículos, categorías, tipos de pieza, piezas y movimientos, además de UserFactory. Las matrículas y los números de bastidor son únicos.

`DatabaseSeeder` crea, en este orden: 2 desguaces, 3 usuarios, almacenamiento interior, almacenamiento exterior, 8 vehículos, categorías, tipos de pieza, piezas, movimientos e historial de ubicaciones (dos registros por vehículo, uno cerrado y otro abierto).

Usuarios generados con la contraseña `password`. Solo para desarrollo.

## 10. Comandos

Instalación de dependencias:

```
composer install
```

Migraciones (requiere MySQL arrancado en XAMPP). No usar `migrate:fresh` si hay datos que conservar:

```
php artisan migrate
php artisan migrate:status
```

Seeders (añade datos de prueba cada vez que se ejecuta):

```
php artisan db:seed
```

Pruebas (usan SQLite en memoria, no tocan MySQL):

```
php artisan test
```

## 11. Ejemplos de peticiones

Crear un desguace:

```
POST /api/desguaces
{
  "nombre": "Desguace Central",
  "direccion": "Calle Mayor 1",
  "numero_telefono": "912345678",
  "ubicación": "Polígono Industrial"
}
```

Crear un vehículo:

```
POST /api/vehiculos
{
  "id_desguace": 1,
  "matricula": "1234ABC",
  "marca": "SEAT",
  "modelo": "Ibiza",
  "fecha_llegada": "2026-01-15",
  "numero_bastidor": "VSSZZZ6JZ1R000001",
  "estado_tratamiento": "Pendiente"
}
```

Registrar una ubicación de vehículo:

```
POST /api/ubicaciones-vehiculo
{
  "id_vehiculo": 1,
  "id_nivel": 3,
  "fecha_inicio": "2026-02-01",
  "fecha_fin": null
}
```

## 12. Pruebas realizadas

Suite `tests/Feature/Api/DesguaceApiTest.php` (PHPUnit, SQLite en memoria):

- El seeder crea 2 desguaces, 8 vehículos y 16 ubicaciones.
- CRUD completo de vehículos con borrado lógico verificado.
- Validación devuelve 422 con datos vacíos.
- Registro inexistente devuelve 404.
- Clave foránea inexistente se rechaza con 422.
- El historial de ubicaciones de un vehículo es consultable.
- Una misma posición puede usarse en varios registros.
- El campo `ubicación` se guarda con tilde.
- Usuarios y registro de sistema no están expuestos.
- La contraseña no aparece en la serialización de User.

Resultado: 12 pruebas, 26 aserciones, todas correctas.

## 13. Errores y pendientes

- Migraciones y seeders no ejecutados todavía contra MySQL: el servicio MySQL de XAMPP no estaba arrancado (error 2002). Pendiente de comprobar con `php artisan migrate` cuando esté activo.
- Usuarios y registro de sistema sin rutas públicas. Pendiente de protección con autenticación en la fase 2.
- No hay autenticación ni autorización. Pendiente de la fase 2 (Spatie Laravel Permission).
- Con `APP_DEBUG=true`, los errores 404 y 500 pueden mostrar detalles internos. Revisar antes de desplegar.
- Relaciones dudosas del EER: ver `REVISION_EER.md`.
