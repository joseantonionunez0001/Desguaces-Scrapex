# Autenticación y autorización con Spatie Laravel Permission

## 1. Versiones

- PHP 8.5.11.
- Laravel Framework 13.40.0.
- spatie/laravel-permission 8.5.0 (fijada).
- laravel/sanctum 4.3.3 (fijada).

## 2. Sistema de autenticación

Se usa el sistema nativo de Laravel, sin starter kits. Breeze y Fortify no se han instalado porque el objetivo es una implementación sencilla con Blade.

- Web: sesiones de Laravel, guard `web`.
- API: tokens personales de Sanctum, guard `sanctum`.

Ficheros:

- `app/Http/Controllers/Auth/AuthenticatedSessionController.php`: login y logout.
- `app/Http/Controllers/Auth/RegisteredUserController.php`: registro.
- `app/Http/Controllers/Api/ApiAuthController.php`: login por token, logout y datos del usuario.
- `app/Http/Controllers/DashboardController.php`: panel del usuario autenticado.
- `resources/views/welcome.blade.php`: muestra Login | Register.
- `resources/views/layouts/app.blade.php`, `auth/login.blade.php`, `auth/register.blade.php`, `dashboard.blade.php`.

### Login

`POST /login` valida correo y contraseña. Si las credenciales son correctas, regenera la sesión y redirige al dashboard. Si no, devuelve un mensaje genérico que no revela si el correo existe. Tiene límite de 5 intentos por minuto.

### Register

`POST /register` pide nombre, correo, contraseña y confirmación. El correo es único y la contraseña se cifra mediante el cast `hashed` del modelo. El formulario no acepta ningún campo de rol: la cuenta nueva no tiene roles y queda pendiente de autorización. Límite de 10 intentos por minuto.

### Logout

`POST /logout`. Solo acepta POST, protegido con CSRF. Invalida la sesión y regenera el token. Una petición GET devuelve 405.

## 3. Modelo User

`app/Models/User.php` usa los traits `HasApiTokens` (Sanctum), `HasRoles` (Spatie), `HasFactory`, `Notifiable` y `SoftDeletes`. Mantiene `name`, `email`, `password` y la PK `id`, necesarios para la autenticación de Laravel. La contraseña y `remember_token` están ocultos en la serialización.

## 4. Tablas creadas

Por Spatie (migración `create_permission_tables`):

- `roles`
- `permissions`
- `model_has_roles`
- `model_has_permissions`
- `role_has_permissions`

Por Sanctum (migración `create_personal_access_tokens_table`):

- `personal_access_tokens`

Ninguna tabla de roles se ha creado a mano. Están pendientes de aplicar en MySQL (ver sección 16).

## 5. Roles

| Rol | Descripción |
|---|---|
| administrador | Acceso completo, incluida la gestión de usuarios, roles y auditoría. |
| gerente | Consulta todo, crea y edita la operativa del desguace. No elimina ni gestiona usuarios. |
| operario | Consulta la información del desguace y puede registrar movimientos. |

Los roles se definen en `database/seeders/RoleSeeder.php`. No hay modelo Rol propio.

## 6. Permisos

Formato `recurso.accion`. Para cada uno de los 16 recursos de negocio hay cuatro permisos (`ver`, `crear`, `editar`, `eliminar`). Además hay permisos administrativos:

- `usuarios.ver`, `usuarios.crear`, `usuarios.editar`, `usuarios.eliminar`, `usuarios.asignar_roles`.
- `registro_sistema.ver`, `registro_sistema.crear`.

Total: 71 permisos. Se definen en `database/seeders/PermissionSeeder.php`.

## 7. Matriz de permisos

La asignación está centralizada en `database/seeders/RolePermissionSeeder.php`. Para cambiar un permiso basta con modificar esa clase.

Recursos de negocio: desguaces, modulos, pasillos, estanterias, etiquetas, patios, sectores, filas, posiciones, niveles, vehiculos, ubicaciones_vehiculo, categorias, tipo_piezas, piezas, movimientos.

| Permiso | administrador | gerente | operario |
|---|---|---|---|
| `*.ver` (todos los recursos de negocio) | Sí | Sí | Sí |
| `*.crear` y `*.editar` en modulos, pasillos, estanterias, etiquetas, patios, sectores, filas, posiciones, niveles, ubicaciones_vehiculo, categorias, tipo_piezas, piezas, vehiculos, movimientos | Sí | Sí | No |
| `movimientos.crear` | Sí | Sí | Sí |
| `*.crear`, `*.editar` en desguaces | Sí | No | No |
| `*.eliminar` (todos los recursos de negocio) | Sí | No | No |
| `usuarios.*` (ver, crear, editar, eliminar, asignar_roles) | Sí | No | No |
| `registro_sistema.ver`, `registro_sistema.crear` | Sí | No | No |

Decisiones tomadas:

- El gerente no elimina registros, porque en el enunciado solo se le atribuyen tareas de gestión.
- El operario solo consulta, salvo el registro de movimientos.
- Desguaces: el gerente solo consulta. El enunciado no le atribuye gestión sobre desguaces.

## 8. Asignación de roles

Los usuarios nuevos no reciben ningún rol al registrarse.

### Primer administrador

Se designa desde la consola del servidor, nunca desde la API. La cuenta debe estar registrada previamente.

```
php artisan db:seed --class=RoleSeeder
php artisan desguace:asignar-administrador correo@ejemplo.com
```

El comando no crea cuentas ni contraseñas. Si el correo no existe, termina con error.

### Gestión desde la API (requiere permiso `usuarios.asignar_roles`)

- `PUT /api/usuarios/{id}/rol` con `{"rol": "gerente"}`: asigna o cambia el rol. Sustituye el rol anterior.
- `DELETE /api/usuarios/{id}/roles/{rol}`: retira un rol concreto.

Salvaguardas:

- Nadie puede modificar sus propios roles (403).
- No se puede retirar el último administrador (422).
- Solo se aceptan los tres roles definidos (422).

## 9. Protección de rutas

### Web

- `/login`, `/register`: solo invitados (`guest`).
- `/dashboard`, `/logout`: requieren sesión (`auth`). Sin sesión, redirige a `/login`.

### API

Todas las rutas excepto `POST /api/login` requieren un token válido (`auth:sanctum`). Sin token, o con token inválido, devuelve 401.

Cada operación de los CRUD exige su permiso mediante el middleware `permission:<recurso>.<accion>`. Sin el permiso devuelve 403.

| Método y ruta | Permiso |
|---|---|
| GET /api/{recurso} y GET /api/{recurso}/{id} | `<recurso>.ver` |
| POST /api/{recurso} | `<recurso>.crear` |
| PUT/PATCH /api/{recurso}/{id} | `<recurso>.editar` |
| DELETE /api/{recurso}/{id} | `<recurso>.eliminar` |
| /api/usuarios (CRUD) | `usuarios.*` |
| /api/usuarios/{id}/rol y /roles/{rol} | `usuarios.asignar_roles` |
| /api/registro-sistema (GET, POST) | `registro_sistema.ver` / `.crear` |

Los permisos no se comprueban solo en el cliente. Se aplican en el servidor, en cada ruta.

Los alias de middleware de Spatie se registran en `bootstrap/app.php`: `role`, `permission` y `role_or_permission`.

## 10. Autenticación de la API

Login:

```
POST /api/login
{"email": "correo@ejemplo.com", "password": "..."}
```

Respuesta con `data.token`. Las peticiones posteriores llevan la cabecera `Authorization: Bearer <token>`.

Logout (revoca solo el token usado):

```
POST /api/logout
```

Datos del usuario y roles:

```
GET /api/user
```

Límite de 5 intentos de login por minuto. El mensaje de error es el mismo para correo inexistente y contraseña incorrecta.

## 11. Registro de auditoría

`POST /api/registro-sistema` toma el autor del token autenticado. El cliente no puede indicar otro usuario. Los registros no se modifican ni se borran (no hay PUT ni DELETE).

## 12. Dashboard

`GET /dashboard` muestra el nombre, el correo, los roles y los permisos agrupados por recurso. Si el usuario no tiene roles, muestra el aviso de cuenta pendiente de autorización.

## 13. Pruebas realizadas

Ejecutadas con `php artisan test` sobre SQLite en memoria. Resultado: 50 pruebas, 136 aserciones, todas correctas.

- `AutenticacionWebTest` (12 pruebas): Login y Register visibles, registro sin rol, contraseña cifrada, correo duplicado rechazado, rol no aceptado desde el formulario, confirmación de contraseña, login correcto e incorrecto, dashboard protegido, logout solo por POST, dashboard con rol y aviso sin rol.
- `AutorizacionApiTest` (18 pruebas): 401 sin token y con token inválido, login y logout por token, usuario sin rol bloqueado, permisos de operario, gerente y administrador, gestión de roles, protección de los propios roles, último administrador, auditoría con el usuario autenticado.
- `SpatieSeedersTest` (8 pruebas): roles y permisos creados, administrador con todos los permisos, matriz de gerente y operario, seeders idempotentes, comando de primer administrador.
- `DesguaceApiTest` (10 pruebas): seeder, CRUD con permiso de administrador, validación, 404, claves foráneas, historial de ubicaciones, reutilización de posiciones, campo `ubicación`, no exposición de contraseñas.
- Dos pruebas de ejemplo que trae Laravel por defecto.

Compilación: `npm run build` (Vite) correcto.

## 14. Comandos para instalar el proyecto en otro equipo

Requisitos: PHP 8.3 o superior, Composer, Node.js, MySQL (XAMPP o similar).

```
git clone <url-del-repositorio>
cd Scrapexv2
composer install
copy .env.example .env
php artisan key:generate
```

Configurar en `.env` la conexión MySQL: `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, con una base de datos local creada previamente.

```
php artisan migrate
php artisan db:seed
php artisan desguace:asignar-administrador correo@ejemplo.com
npm install
npm run build
php artisan serve
```

Notas:

- `db:seed` crea roles, permisos y datos de prueba. Los usuarios generados por las factories tienen la contraseña `password`, solo para desarrollo.
- Para el primer administrador, primero hay que registrarse en la aplicación y después ejecutar el comando.
- No usar `migrate:fresh` si hay datos que conservar.

No incluir en el repositorio el fichero `.env` ni credenciales reales.

## 15. Decisiones y limitaciones

- Sin Breeze ni Fortify. Autenticación nativa con Blade.
- Sanctum se usa solo para la API. La sesión web no depende de él.
- No hay políticas (Policies) por registro. El enunciado pide que un usuario solo gestione su propio desguace, pero el modelo de permisos actual es global. Hace falta decidir si se implementa antes de continuar.
- Los endpoints de usuarios y registro estaban previstos como no públicos en la fase 1. Ahora están registrados pero protegidos por permisos.
- Las vistas web de gestión del desguace no existen. La gestión se hace por API. El dashboard solo muestra los accesos.
- Con `APP_DEBUG=true` los errores pueden mostrar detalles internos. Revisar antes de desplegar.

## 16. Pendientes

1. Arrancar MySQL en XAMPP y ejecutar `php artisan migrate` para crear las tablas de Spatie y Sanctum en `local_desguace`. Hasta entonces no se han verificado sobre MySQL. El último intento falló con error 2002 (conexión denegada).
2. Ejecutar `php artisan db:seed` tras la migración.
3. Designar el primer administrador con `desguace:asignar-administrador`.
4. Decidir si se añaden Policies por desguace.
5. Revisar las decisiones del EER pendientes en `REVISION_EER.md`.
