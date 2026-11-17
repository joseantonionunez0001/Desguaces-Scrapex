# Normas de código: Desguaces Scrapex

Estas normas se basan en el código que ya existe en el proyecto y en las reglas de `AGENTS.md`. Si algo de este documento contradice `AGENTS.md`, manda `AGENTS.md`.

## Stack

- Laravel 13 con PHP 8.4 en desarrollo. El servidor de producción usa PHP 8.5, así que no uses funciones que dependan de una versión concreta sin comprobarlas.
- Spatie Laravel Permission para roles y permisos (`RoleSeeder`, `RolePermissionSeeder`).
- Laravel Sanctum para la API (`ApiAuthController`, tokens personales).
- Blade para la web, con estilos inline en `resources/views/layouts/app.blade.php`.
- PHPUnit para los tests. Laravel Pint para el formato.

## Idioma

- Nombres de modelos, tablas, campos, rutas, mensajes de validación, respuestas de la API y comentarios: en español, como en el resto del proyecto (`Desguace`, `Pieza`, `id_desguace`, `asignarRol`, `prohibido`).
- Nombres de variables y métodos en camelCase, descriptivos (`$usuarioAutenticado`, `isRegisteredForDiscounts` en lugar de `discount()`).
- Mantén los nombres de las clases de Laravel en inglés cuando sean convención del framework (`Controller`, `Request`, `Migration`).

## PHP

- Llaves siempre, incluso en estructuras de una sola línea.
- Constructor property promotion en PHP 8. No dejes constructores vacíos salvo que sean privados.
- Tipos explícitos en parámetros y retornos (`string $id`, `JsonResponse`, `: void`).
- Usa PHPDoc en lugar de comentarios inline, y solo comenta lógica compleja. Los comentarios de ruta que ya existen en los controladores (`// GET /api/usuarios`) se pueden mantener, pero en métodos nuevos usa PHPDoc.
- Para formas de arrays en PHPDoc usa tipos con forma (`array{id: int, nombre: string}`).
- Enums con nombres en TitleCase (`Administrador`, `Operario`).

## Modelos y base de datos

- Los modelos que se pueden borrar usan `SoftDeletes`. No borres registros físicamente en la API; usa `delete()`.
- Excepción: `RegistroSistema` no tiene timestamps ni softDeletes porque es un registro de auditoría.
- Las migraciones se crean con `php artisan make:migration` y nunca se editan una vez subidas a producción. Para cambios nuevos, crea otra migración.
- Consulta `database-schema` antes de escribir migraciones o modelos.
- Crea factories y seeders para cada modelo nuevo.
- Evita consultas N+1: usa `with()` para cargar relaciones (`User::with(['desguace', 'roles'])`).

## Autenticación y roles

- Los roles se asignan con `syncRoles()` o `assignRole()` de Spatie. No se aceptan roles en `store()` ni `update()`; se asignan con un método dedicado (`asignarRol`).
- Nombres de roles y permisos desde las constantes de `RoleSeeder` (`RoleSeeder::ADMINISTRADOR`), nunca con cadenas sueltas en lógica nueva.
- Las contraseñas se guardan con el cast `hashed` del modelo `User`. No llames a `Hash::make()` manualmente si el cast ya lo hace.
- No devuelvas `password` ni `remember_token` en respuestas.
- No permitas que un usuario modifique su propia cuenta o roles (ver `prohibido()` en `UsuarioApiController`).

## API

- Respuestas JSON con el mismo formato que los controladores existentes: `['data' => ..., 'success' => 1]`, con código 200 (lectura o actualización), 201 (creación) o el error correspondiente.
- Valida con `$request->validate()` en el controlador, con reglas en formato de array cuando hay `Rule::` o reglas condicionales. Si una validación se repite en varios endpoints, extráela a un Form Request.
- Las rutas de API van en `routes/api.php`, protegidas con `auth:sanctum` y permisos de Spatie.
- Para recursos nuevos, usa API Resources si el endpoint devuelve estructuras complejas. Mantén el formato de envoltorio `data` para no romper a los clientes.

## Vistas y frontend

- Las pantallas Blade extienden `layouts.app` y usan `@section('content')`.
- Los estilos van en el bloque `<style>` del layout mientras no haya build de Vite para la web. Si se añade Tailwind a las vistas, sigue las reglas de `tailwindcss-development`.
- Accesibilidad: cada campo tiene `<label for="...">`, los errores van junto al campo con `@error`, y los botones y enlaces tienen foco visible.
- Textos visibles al usuario en español.

## Tests

- Escribe tests para cambios de lógica y para los fallos importantes. No añadas tests sin necesidad.
- Ubicación: `tests/Feature` para endpoints y flujos, `tests/Unit` para lógica aislada.
- Nombres descriptivos en español o inglés según el archivo existente (`AutorizacionApiTest`, `DesguaceApiTest`).
- Usa los factories y el helper `PreparaAutorizacion` para preparar usuarios con roles.
- Ejecuta solo los tests afectados con `php artisan test --filter=NombreDelTest`.

## Formato y herramientas

- Antes de terminar, ejecuta `vendor/bin/pint --dirty --format agent` sobre los archivos PHP modificados.
- `.editorconfig`: UTF-8, saltos de línea LF, 4 espacios de indentación (2 en YAML), final de línea y sin espacios al final.
- No cambies dependencias sin aprobación.

## Git

- No hagas push directo a `main` salvo que se pida explícitamente.
- Trabaja en la rama de cada desarrollador y sube los cambios a `Desarrollo` antes de pasar a `main`.
- Mensajes de commit cortos en español, en minúsculas (`estilos de login`, `añadir plantilla htaccess`).
- No subas `.env`, credenciales ni `node_modules`.

## Seguridad

- Valida toda entrada del usuario antes de guardarla.
- Nunca pongas secretos ni contraseñas en el código ni en el repositorio.
- Las rutas que modifican datos van con middleware de autenticación y de permisos.
- Ante cualquier cambio en producción (migraciones, roles, configuración), explica el impacto y pide confirmación antes de ejecutarlo.
