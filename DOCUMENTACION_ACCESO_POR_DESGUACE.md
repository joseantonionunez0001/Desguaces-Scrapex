# Acceso por roles y por desguace

Este documento sustituye a la sección de roles de `DOCUMENTACION_AUTENTICACION_SPATIE.md`. Las reglas valen igual para la web y para la API.

## Roles

| Rol | Sede | Qué puede hacer |
|---|---|---|
| `administrador_plataforma` | Ninguna | Crea, edita y elimina desguaces. Designa o sustituye al administrador de cada sede. Consulta todas las cuentas y cambia la sede de gerentes y operarios. **No tiene acceso al inventario.** |
| `administrador_desguace` | Una | Gestiona todo el inventario de su sede. Crea, edita y da de baja a los gerentes y operarios de su sede. Consulta los registros del sistema de su sede. |
| `gerente` | Una | Consulta todo el almacén de su sede. Crea y edita vehículos, piezas, stock, movimientos, categorías y ubicaciones. No elimina y no gestiona usuarios. |
| `operario` | Una | Solo lectura de los listados y fichas de vehículos (con su historial de ubicaciones) y piezas de su sede. No escribe, no cambia el stock y no entra en módulos de gestión (desguaces, almacén, categorías, movimientos, usuarios, registros). |
| `administrador` (anterior) | - | Se conserva **sin permisos**. Ver "Cuentas existentes". |

La matriz de permisos está en `database/seeders/RolePermissionSeeder.php` y se consulta en la web en **Roles y permisos**.

## Cómo se aplica en el servidor

1. **Permiso por rol**: middleware `permission:<recurso>.<accion>` en cada ruta de `routes/web/*.php` y `routes/api.php`. Se ejecuta antes de resolver los modelos de la URL, así que sin permiso la respuesta es siempre 403.
2. **Sede válida**: `App\Http\Middleware\ExigeDesguaceAsignado` protege toda la web (grupo `web`) y la API. A quien tiene un rol de sede pero ninguna sede válida (sin asignar o eliminada) le responde 403 con el aviso *"Tu cuenta no tiene un desguace asignado. Contacta con administración."*. Solo quedan fuera inicio, dashboard (que muestra el aviso) y logout.
3. **Alcance de los datos**: cada modelo de inventario usa el scope global `AlcanceDesguaceScope`, que se aplica a listados, búsquedas, filtros, fichas, desplegables de los formularios, contadores del dashboard y enlaces de ruta (`/vehiculos/{vehiculo}`...).
   - Calcula la sede en cada petición a partir del rol y de `users.id_desguace` (`App\Support\Acceso`).
   - Sigue las relaciones reales hasta `id_desguace`:
     - `Vehiculo`, `Modulo`, `Patio` y `Categoria` tienen `id_desguace` propio.
     - `Pasillo` → `Modulo`; `Estanteria` → `Pasillo`; `Etiqueta` → `Estanteria`.
     - `Sector` → `Patio`; `Fila` → `Sector`; `Posicion` → `Fila`; `Nivel` → `Posicion`.
     - `UbicacionVehiculo` → `Vehiculo`.
     - `TipoPieza` → `Categoria`; `Pieza` → `TipoPieza` → `Categoria`; `Movimiento` → `Categoria`.
     - `RegistroSistema` → usuario autor.
   - La pertenencia no cambia si un padre está archivado (borrado lógico): el historial conserva sus registros.
   - Es **denegado por defecto**: un usuario autenticado sin sede, sin rol o de plataforma no ve ningún registro de inventario.
   - Sin usuario autenticado (consola, seeders) no restringe.
   - Una ficha de otra sede responde **404** (también en edición, actualización y borrado), igual que si no existiera.
4. **Formularios**: la regla `App\Rules\ExisteEnMiSede` sustituye a `exists:` en todas las claves foráneas de los Form Requests web y de la API (`id_desguace`, `id_modulo`, `id_vehiculo`, `id_nivel`, `id_tipo_pieza`, `id_categoria`, el padre de cada elemento del almacén...). Un identificador de otra sede se rechaza con el mismo mensaje que si no existiera.
5. **Usuarios** (`Web\UsuarioController`, `Web\UsuarioRolController`, `Api\UsuarioApiController`):
   - El administrador de desguace solo ve y gestiona cuentas de su sede (las demás responden 404).
   - En el alta elige el rol (`gerente` u `operario`) y el servidor asigna la sede. `id_desguace` está prohibido en el formulario.
   - No puede tocar cuentas de administración ni la suya, ni cambiar a nadie de sede.
6. **Menús y botones**: el menú lateral, los accesos del dashboard y los botones de cada pantalla dependen del permiso. Una cuenta sin sede solo ve el dashboard con el aviso. Es solo presentación: la seguridad real es la de los puntos anteriores.

## Pantallas y endpoints

| Web | API | Quién | Para qué |
|---|---|---|---|
| Desguaces → **Administrador** | `PUT /api/desguaces/{id}/administrador` | Plataforma | Designa o sustituye al administrador. En la web: correo de una cuenta existente, o correo, nombre y contraseña para una nueva. En la API: `{"id_usuario": 7}` o `{"name","email","password"}`. El anterior pierde el rol y sus sesiones. |
| Usuarios → ficha → **Cambiar desguace** | `PUT /api/usuarios/{id}/desguace` | Plataforma | Procedimiento autorizado para cambiar la sede de un gerente, operario o cuenta sin rol. Cierra sus sesiones y tokens; el acceso a la sede anterior desaparece en la siguiente petición. |
| Usuarios → **Nueva cuenta** / **Editar cuenta** / eliminar | `POST`, `PUT`, `DELETE /api/usuarios` | Administrador de desguace | Equipo de su sede. Se envían nombre, correo, contraseña y rol. No se envía sede. |

## Migraciones

**No hacen falta migraciones de esquema.** `users.id_desguace` y las tablas de Spatie ya existen. Los roles y permisos nuevos se crean con los seeders, que no tocan usuarios:

```
php artisan scrapex:sincronizar-permisos
```

El comando es idempotente y se ejecuta tras desplegar esta versión.

## Habilitar al primer administrador de plataforma

Solo desde la consola del servidor, nunca desde la web ni la API. La cuenta no debe pertenecer a ningún desguace.

Con una cuenta ya registrada (en `/register`):

```
php artisan scrapex:sincronizar-permisos
php artisan scrapex:habilitar-plataforma correo@ejemplo.com
```

O con la cuenta configurada en el `.env` (`ADMIN_EMAIL` y `ADMIN_PASSWORD`), que se crea si no existe:

```
php artisan db:seed --class=UsuarioAdministradorSeeder
```

Ese seeder **no** se ejecuta con `db:seed`: hay que lanzarlo de forma explícita. Si la cuenta pertenece a un desguace, avisa y no la habilita.

Después, esa cuenta entra en la web, crea los desguaces y designa a sus administradores en **Desguaces → Administrador**.

## Cuentas existentes

Nada se promociona ni se asigna a una sede de forma automática.

- Las cuentas con el rol anterior `administrador` conservan ese rol, pero **sin permisos**: no acceden a nada hasta que se reasignen.
- Las cuentas con `gerente` u `operario` y sin `id_desguace` quedan bloqueadas con el aviso de contactar con administración.

Para asignar sede y rol a una cuenta existente, decide caso por caso. Desde la web, la plataforma puede designar administradores (Desguaces) y cambiar la sede (ficha del usuario); después el administrador de la sede le asigna el rol. Desde la consola:

```
php artisan scrapex:asignar-usuario correo@ejemplo.com <id_desguace> <rol>
```

`rol` es `administrador_desguace`, `gerente` u `operario`. Con `administrador_desguace` el comando sustituye al administrador anterior de esa sede.

## Pruebas

Con dos sedes (A y B) e inventario completo en cada una:

- `tests/Feature/Web/AccesoPorDesguaceWebTest.php` y `tests/Feature/Api/AccesoPorDesguaceTest.php`: fichas de la otra sede por URL directa (404), formularios con identificadores de la otra sede (rechazados), listados y búsquedas sin datos ajenos, operario de solo lectura y menús por rol.
- `tests/Feature/Web/DesguacesWebTest.php`: la plataforma crea dos desguaces y designa o sustituye a sus administradores; el anterior pierde el acceso; el borrado no ignora datos que la plataforma no ve.
- `tests/Feature/Web/UsuariosAdminWebTest.php`: el administrador local crea, edita y da de baja a su equipo; no puede elegir otra sede, conceder roles de administración ni tocar cuentas de otra sede; al cambiar la sede por el procedimiento autorizado se revoca el acceso a la anterior; una cuenta sin sede ve el aviso y queda bloqueada.

Por defecto, las factories de inventario crean los datos en el primer desguace existente (`DesguaceFactory::idPorDefecto()`), y `usuarioConRol()` asigna esa misma sede a los roles de sede. Para probar otra sede hay que crearla y pasarla de forma explícita.

Ejecutar todo con `php artisan test`. Si no existe `.env`, define antes `APP_KEY` (`$env:APP_KEY = (php artisan key:generate --show)` en PowerShell).

## Limitaciones conocidas

- `matricula` y `numero_bastidor` siguen siendo únicos en toda la plataforma. Un error por duplicado puede revelar que esa matrícula existe en otra sede.
