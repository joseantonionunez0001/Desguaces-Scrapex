# Patrón oficial de CRUD API: entidad Desguace

Este documento describe el CRUD de referencia de la entidad `Desguace`. Las IA de los compañeros deben leerlo y replicar la misma estructura para sus entidades.

Documentos a consultar antes de empezar: `docs/ESQUEMA_EER_KIRO.md` y `docs/ESQUEMA_EER_DESGUACE.drawio`.

## 1. Organización

Cada entidad tiene estos archivos, todos con el mismo nombre base (singular en el modelo, plural en la tabla):

| Archivo | Ejemplo para Desguace |
|---|---|
| Migración | `database/migrations/2026_11_03_120000_create_desguaces_table.php` |
| Modelo | `app/Models/Desguace.php` |
| Factoría | `database/factories/DesguaceFactory.php` |
| Seeder | `database/seeders/DesguaceSeeder.php` |
| Controlador API | `app/Http/Controllers/DesguaceApiController.php` |
| Rutas | `routes/api.php` (compartido, ver sección 8) |
| Pruebas | `tests/Feature/DesguaceApiTest.php` |

Convenciones de nombres:

- Modelo en singular y PascalCase: `Desguace`, `Patio`, `Vehiculo`.
- Tabla en plural y minúsculas: `desguaces`, `patios`.
- Clave primaria `id`. Claves foráneas `<entidad_singular>_id`, por ejemplo `desguace_id`.
- Columnas en minúsculas, sin tildes ni espacios: `numero_telefono`, no `número_teléfono`. Por eso el atributo `ubicación` del EER se guarda como `ubicacion`.
- Controlador `<Entidad>ApiController`, por ejemplo `DesguaceApiController`.
- Textos de la API y comentarios en español.

## 2. Migraciones

- Nombre con marca de tiempo: `YYYY_MM_DD_HHMMSS_create_<tabla>_table.php`.
- Usar `$table->id()` y `$table->timestamps()`. No se usan `softDeletes` porque el esquema no los define.
- Tipos: texto corto con `string(longitud)`. Los teléfonos son `string(20)`, nunca numéricos, para no perder ceros iniciales ni el prefijo `+`.
- Los campos del EER van como `NOT NULL` salvo que el esquema indique lo contrario.
- Las claves foráneas se añaden con `foreignId('<entidad>_id')->constrained()` en una migración posterior a la tabla padre. Ejemplo: `patios` después de `desguaces`.
- Nunca editar una migración ya subida a GitHub. Para cambios, crear una migración nueva.
- Nunca usar `migrate:fresh` ni `db:wipe` en la base de datos compartida.

## 3. Modelos

Estructura obligatoria:

```php
class Desguace extends Model
{
    use HasFactory;

    protected $table = 'desguaces';
    protected $primaryKey = 'id';

    protected $fillable = [
        'nombre',
        'direccion',
        'numero_telefono',
        'ubicacion'
    ];
}
```

- `$table` y `$primaryKey` siempre explícitos.
- `$fillable` solo con columnas de negocio. Nunca incluir `id`, `created_at` ni `updated_at`.
- `use HasFactory` siempre.
- Las relaciones se definen en el modelo que tiene la clave foránea (ver sección 9).

## 4. Factorías

- Un archivo por modelo, en `database/factories/<Entidad>Factory.php`.
- Usar `fake()` y generar valores coherentes con la validación del controlador (misma longitud máxima, mismo formato).
- No insertar datos en la factoría; solo devolver el array de `definition()`.

## 5. Seeders

- Un archivo por entidad: `database/seeders/<Entidad>Seeder.php`.
- Dentro de `run()` usar la factoría: `Desguace::factory()->count(5)->create();`.
- Un seeder solo debe poder ejecutarse después de las migraciones de sus tablas padre.
- No modificar `DatabaseSeeder.php` desde una rama individual. Ver sección 10.

## 6. Controladores

Un controlador por entidad, extendiendo `Controller`, con los cinco métodos del CRUD:

| Método | Ruta | Código de respuesta |
|---|---|---|
| `index()` | GET `/api/desguaces` | 200 |
| `show(string $id)` | GET `/api/desguaces/{id}` | 200 o 404 |
| `store(Request $request)` | POST `/api/desguaces` | 201 |
| `update(Request $request, string $id)` | PUT/PATCH `/api/desguaces/{id}` | 200 |
| `destroy(string $id)` | DELETE `/api/desguaces/{id}` | 200 |

Reglas de estilo:

- Cada método lleva un comentario con su verbo y ruta, por ejemplo `// GET /api/desguaces/1`.
- Validar con `$request->validate([...])`. En `store` todos los campos son `required`. En `update` se usa `sometimes|required` para permitir PUT y PATCH con la misma lógica.
- Asignar campos y guardar con `$modelo->save()`, o `fill()` en `update`.
- Buscar con `findOrFail($id)`, que devuelve 404 automáticamente.
- Todas las respuestas tienen esta forma:

```php
return response()->json([
    'data' => $desguace,
    'success' => 1
], 200);
```

- En `destroy`, `data` es el texto `'Desguace eliminado'`.
- No añadir lógica de negocio compleja en el controlador. Si una operación implica varias tablas, usar una transacción con `DB::transaction()`.

## 7. Pruebas

- Archivo en `tests/Feature/<Entidad>ApiTest.php`.
- Usar `DatabaseTransactions` y no `RefreshDatabase`, para no borrar tablas ni datos de la base compartida.
- Cubrir como mínimo: index, show, store (éxito y validación), update, patch, destroy y 404.

## 8. Rutas API

El archivo `routes/api.php` es compartido por todas las ramas. Para cada entidad se añade una sola línea:

```php
Route::apiResource('desguaces', DesguaceApiController::class);
```

`apiResource` genera las rutas GET, POST, GET por id, PUT/PATCH y DELETE. El archivo se registra en `bootstrap/app.php` mediante `api: __DIR__.'/../routes/api.php'`.

Regla para evitar conflictos: añadir al final del archivo la línea `apiResource` de la entidad, y su `use` del controlador en la cabecera. No reordenar ni borrar líneas de otras entidades.

Para comprobar las rutas: `php artisan route:list --path=api`.

## 9. Relaciones entre entidades

Según el EER, la cadena de almacenamiento de vehículos es `Desguace (1) → Patio (N) → Sector (N) → Fila (N) → Posicion (N) → Nivel (N)`.

Convención para declarar relaciones:

- En el modelo padre: `hasMany` (`public function patios() { return $this->hasMany(Patio::class); }`).
- En el modelo hijo: `belongsTo` (`public function desguace() { return $this->belongsTo(Desguace::class); }`).
- La clave foránea `desguace_id` va en la migración del hijo, con `->constrained()`.

Estado actual: `Desguace` no tiene relaciones todavía. `Patio` aún no existe. Cuando se cree, su integrante añadirá el `hasMany` en `Desguace` y el `belongsTo` en `Patio`, y lo coordinará antes de hacer merge.

## 10. Evitar conflictos entre ramas

Archivos compartidos que nadie debe modificar sin coordinación:

- `routes/api.php`: solo añadir una línea `apiResource` al final.
- `database/seeders/DatabaseSeeder.php`: la integración de seeders se hace al final, en la rama de integración.
- `bootstrap/app.php` y `config/*`: no tocar salvo acuerdo del equipo.
- `docs/ESQUEMA_EER_KIRO.md` y el `.drawio`: no modificar el esquema sin consultar.

Otras reglas:

- Cada entidad tiene su propia migración con timestamp distinto. Si dos ramas crean migraciones, el orden se decide en la integración.
- No modificar `Desguace`, `DesguaceApiController` ni sus archivos desde otras ramas.
- No ejecutar migraciones contra la base compartida sin avisar al equipo.

## 11. Comprobación rápida de una entidad nueva

1. `php artisan migrate:status` muestra la migración como pendiente antes de ejecutarla.
2. `php artisan route:list --path=api` muestra las cinco rutas nuevas.
3. `php artisan db:seed --class=<Entidad>Seeder` inserta registros de prueba.
4. Probar las peticiones desde RapidAPI Client con `Accept: application/json`.
5. `php artisan test --filter=<Entidad>ApiTest` (requiere base de datos de pruebas separada).

## 12. Peticiones de prueba para Desguace

| Método | URL | Cuerpo JSON | Resultado esperado |
|---|---|---|---|
| GET | `/api/desguaces` | - | 200 con `data` (lista) |
| GET | `/api/desguaces/1` | - | 200 con un desguace |
| POST | `/api/desguaces` | `{"nombre":"Desguace Norte","direccion":"Calle Mayor 1","numero_telefono":"600111222","ubicacion":"Sevilla"}` | 201 con el registro creado |
| PUT | `/api/desguaces/1` | Los cuatro campos | 200 con el registro actualizado |
| PATCH | `/api/desguaces/1` | `{"ubicacion":"Madrid"}` | 200 solo con ese campo cambiado |
| DELETE | `/api/desguaces/1` | - | 200 con `"Desguace eliminado"` |
| GET | `/api/desguaces/999999` | - | 404 |
| POST | `/api/desguaces` | `{}` | 422 con errores de validación |

Cabecera recomendada en RapidAPI Client: `Accept: application/json` y `Content-Type: application/json`.
