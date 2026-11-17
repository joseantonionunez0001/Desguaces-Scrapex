# Documentación de las 21 vistas del sistema de gestión de desguaces

Este documento describe el frontend Blade del TFG: pantallas, rutas, controladores, permisos, pruebas
realizadas, problemas detectados y cómo integrar las cuatro ramas.

> **Estado de Git:** no se ha hecho ningún `commit`, `merge`, `rebase` ni `push`. Todo el trabajo está
> en el directorio de trabajo de cada rama, sin confirmar. Ver la sección 13.

## 1. Pantallas y estado de seguimiento

| Nº | Pantalla | Rama responsable | Ruta web | Nombre de ruta | Estado |
|---:|---|---|---|---|---|
| 1 | Página de inicio | Luis-Reyes | `GET /` | `home` | Implementada |
| 2 | Login | Luis-Reyes | `GET /login`, `POST /login` | `login` | Implementada |
| 3 | Register | Luis-Reyes | `GET /register`, `POST /register` | `register` | Implementada |
| 4 | Dashboard | Luis-Reyes | `GET /dashboard` | `dashboard` | Implementada |
| 5 | Gestión de usuarios | Luis-Reyes | `GET /admin/usuarios` | `admin.usuarios.index` | Implementada |
| 6 | Ficha de usuario y roles | Luis-Reyes | `GET /admin/usuarios/{user}`, `PUT/DELETE /admin/usuarios/{user}/rol` | `admin.usuarios.show`, `.rol.update`, `.rol.destroy` | Implementada |
| 7 | Listado de vehículos | Alvaro-Llorente | `GET /vehiculos` | `vehiculos.index` | Implementada |
| 8 | Ficha de vehículo | Alvaro-Llorente | `GET /vehiculos/{vehiculo}` | `vehiculos.show` | Implementada |
| 9 | Listado de piezas | Alvaro-Llorente | `GET /piezas` | `piezas.index` | Implementada |
| 10 | Ficha de pieza | Alvaro-Llorente | `GET /piezas/{pieza}` | `piezas.show` | Implementada |
| 11 | Historial de ubicaciones | Alvaro-Llorente | `GET /vehiculos/{vehiculo}/ubicaciones` | `vehiculos.ubicaciones.index` | Implementada |
| 12 | Listado de movimientos | Jose-Antonio | `GET /movimientos` | `movimientos.index` | Implementada |
| 13 | Registrar movimiento | Jose-Antonio | `GET /movimientos/crear`, `POST /movimientos` | `movimientos.create`, `.store` | Implementada |
| 14 | Gestión de categorías | Jose-Antonio | `GET /categorias` (+ modales) | `categorias.index/store/update/destroy` | Implementada |
| 15 | Gestión de tipos de pieza | Jose-Antonio | `GET /tipos-pieza` (+ modales) | `tipos-pieza.index/store/update/destroy` | Implementada |
| 16 | Gestión de desguaces | Jose-Antonio | `GET /desguaces` (+ modales) | `desguaces.index/store/update/destroy` | Implementada |
| 17 | Almacén interior | Antonio | `GET /almacen/interior` | `almacen.interior.index` (+ altas/ediciones/bajas) | Implementada |
| 18 | Almacén exterior | Antonio | `GET /almacen/exterior` | `almacen.exterior.index` (+ altas/ediciones/bajas) | Implementada |
| 19 | Asignación de ubicaciones | Antonio | `GET/POST /vehiculos/{vehiculo}/asignar-ubicacion` | `vehiculos.ubicaciones.asignar`, `.store` | Implementada (reglas de negocio pendientes de confirmar) |
| 20 | Roles y permisos | Antonio | `GET /admin/roles-permisos` | `admin.roles-permisos.index` | Parcial: matriz de solo lectura (ver 12) |
| 21 | Registros del sistema | Antonio | `GET /admin/registros` | `admin.registros.index` | Implementada (consulta); la tabla solo guarda usuario y fecha/hora |

Total: 6 + 5 + 5 + 5 = **21 pantallas**.

## 2. Archivos por rama

### Luis-Reyes (pantallas 1–6)

- Controladores: `app/Http/Controllers/DashboardController.php` (modificado), `app/Http/Controllers/Web/UsuarioController.php`, `app/Http/Controllers/Web/UsuarioRolController.php`. Las pantallas 2 y 3 reutilizan `Auth\AuthenticatedSessionController` y `Auth\RegisteredUserController` sin cambios.
- Request: `app/Http/Requests/Web/AsignarRolRequest.php`.
- Rutas: `routes/web/usuarios.php`.
- Vistas: `welcome`, `auth/login`, `auth/register`, `dashboard` (modificadas), `usuarios/index`, `usuarios/show`.
- Pruebas: `tests/Feature/Web/InicioAutenticacionDashboardWebTest.php`, `tests/Feature/Web/UsuariosAdminWebTest.php`.

### Alvaro-Llorente (pantallas 7–11)

- Controladores: `Web/VehiculoController`, `Web/PiezaController`, `Web/VehiculoUbicacionController`.
- Requests: `Web/GuardarVehiculoRequest`, `Web/GuardarPiezaRequest`. Soporte: `app/Support/JerarquiaUbicacion.php`.
- Rutas: `routes/web/vehiculos.php`, `routes/web/piezas.php` (también `/vehiculos/crear`, `/vehiculos/{id}/editar`, `/piezas/crear`, `/piezas/{id}/editar`).
- Vistas: `vehiculos/{index,show,create,edit,_form,_ubicacion,ubicaciones}`, `piezas/{index,show,create,edit,_form,_disponibilidad}`.
- Pruebas: `VehiculosWebTest`, `VehiculoUbicacionesWebTest`, `PiezasWebTest`.

### Jose-Antonio (pantallas 12–16)

- Controladores: `Web/MovimientoController`, `Web/CategoriaController`, `Web/TipoPiezaController`, `Web/DesguaceController`.
- Requests: `MovimientoRequest`, `CategoriaRequest`, `TipoPiezaRequest`, `DesguaceRequest`. Soporte: `app/Support/OpcionesCategorias.php`.
- Rutas: `routes/web/movimientos.php`, `categorias.php`, `tipos-pieza.php`, `desguaces.php`.
- Vistas: `movimientos/{index,create,_detalle}`, `categorias/{index,_form}`, `tipos-pieza/{index,_form}`, `desguaces/{index,_form,_detalle}`.
- Pruebas: `MovimientosWebTest`, `CategoriasWebTest`, `TiposPiezaWebTest`, `DesguacesWebTest`.

### Antonio (pantallas 17–21)

- Controladores: `Web/AlmacenController` (base), `AlmacenInteriorController`, `AlmacenExteriorController`, `AsignacionUbicacionController`, `RolesPermisosController`, `RegistroSistemaController`.
- Servicios: `app/Services/{AlmacenJerarquia,AsignacionUbicacionVehiculo,EstructuraAlmacen,OcupacionAlmacen}.php`.
- Requests: `AsignarUbicacionRequest`, `FiltrarRegistrosRequest`, `GuardarElementoAlmacenRequest`, `Concerns/MensajesDeValidacion`.
- Rutas: `routes/web/almacen.php`, `ubicaciones.php`, `admin.php`.
- Vistas: `almacen/{interior,exterior}/index`, `almacen/partials/*`, `ubicaciones/asignar`, `admin/{roles-permisos,registros}`, `admin/partials/marca-permiso`.
- Pruebas: `AlmacenInteriorTest`, `AlmacenExteriorTest`, `AsignacionUbicacionTest`, `RolesPermisosTest`, `RegistrosSistemaTest`.

## 3. Base compartida (idéntica en las 4 ramas)

Estos archivos son byte a byte iguales en las cuatro ramas para que su integración no genere conflictos.
**No deben modificarse en una sola rama.**

| Archivo | Función |
|---|---|
| `resources/views/layouts/app.blade.php` | Layout privado: menú lateral, barra superior, avisos flash, `@stack('scripts')`. Sustituye al layout anterior. |
| `resources/views/layouts/guest.blade.php` | Layout público (inicio, login, registro). |
| `resources/views/components/ui/*` | `alert`, `badge`, `breadcrumbs`, `button`, `card`, `delete-button`, `empty-state`, `filters`, `flash`, `icon`, `modal`, `page-header`, `pagination`, `stat-card`, `table`. |
| `resources/views/components/form/*` | `input`, `select`, `textarea`, `actions`. Muestran `old()` y errores de validación. |
| `resources/views/components/layout/*` | `sidebar`, `topbar`. |
| `resources/views/pagination/erp.blade.php` | Vista de paginación en español. |
| `resources/css/app.css`, `resources/js/app.js` | Estilos de componentes; menú móvil, modales `<dialog>`, confirmación de borrado e indicador de carga. |
| `config/navegacion.php`, `app/Support/Navegacion.php` | Menú lateral y accesos rápidos: solo aparece un elemento si su ruta existe y el usuario tiene el permiso. |
| `routes/web.php` | Mantiene las rutas de autenticación y carga automáticamente `routes/web/*.php`. |
| `tests/TestCase.php` | Llama a `withoutVite()` para que las pruebas no necesiten los assets compilados. |

Diseño: fondo gris muy claro, tarjetas blancas, azul oscuro (`blue-900/950`) como color principal, azul
(`blue-600`) como secundario, verde (confirmaciones), rojo (acciones destructivas y errores) y ámbar (avisos).
Tailwind v4 ya instalado; no se ha añadido ninguna dependencia.

Menú lateral (según permisos): Dashboard · Vehículos · Piezas · Movimientos · Categorías · Tipos de pieza ·
Desguaces · Almacén interior · Almacén exterior · Usuarios · Roles y permisos · Registros del sistema. En
pantallas pequeñas se contrae y se abre con el botón de la barra superior.

## 4. Permisos

Todas las rutas privadas llevan `auth` y el middleware `permission:` de Spatie (alias ya definidos en
`bootstrap/app.php`). Los botones se ocultan con `@can`, pero la protección real está en el servidor.

| Rol | Alcance real (según `RolePermissionSeeder`) |
|---|---|
| administrador | Todos los permisos. |
| gerente | Consulta de todo; crea y edita módulos, pasillos, estanterías, etiquetas, patios, sectores, filas, posiciones, niveles, ubicaciones, categorías, tipos de pieza, piezas, vehículos y movimientos. No elimina, no gestiona desguaces, usuarios, roles ni registros. |
| operario | Consulta (`.ver`) de los 16 recursos y `movimientos.crear`. No elimina vehículos ni piezas. |
| sin rol | Puede iniciar sesión y ve el dashboard con aviso de cuenta pendiente. Los módulos internos devuelven 403. |

Permisos por pantalla: 5 `usuarios.ver`; 6 `usuarios.ver` + `usuarios.asignar_roles` para modificar; 7–8 `vehiculos.ver`
(`crear`/`editar`/`eliminar` en las acciones); 9–10 `piezas.*`; 11 `vehiculos.ver` + `ubicaciones_vehiculo.ver`;
12–13 `movimientos.ver` / `movimientos.crear`; 14 `categorias.*`; 15 `tipo_piezas.*`; 16 `desguaces.*`;
17 `modulos.ver` (cada nivel usa su propio recurso); 18 `patios.ver`; 19 `ubicaciones_vehiculo.crear` y `.editar`;
20 `usuarios.asignar_roles`; 21 `registro_sistema.ver`.

Reglas de la gestión de roles (pantalla 6): se usan `syncRoles` y `removeRole` de Spatie; nadie puede
modificar sus propios privilegios (403 en servidor); no se puede quitar ni degradar al último administrador;
el registro público nunca acepta rol.

## 5. Dependencias del backend

- Modelos, migraciones, seeders, factories y API REST existentes: **sin cambios**. `routes/api.php` no se ha tocado.
- Las vistas usan las relaciones reales de los modelos. No existen relaciones Movimiento↔Pieza ni Movimiento↔Usuario, y no se han simulado.
- `Registro_Sistema` solo tiene `fecha_hora` e `id_usuario`: no hay columna de acción.
- El backend no restringe datos por desguace del usuario; la web tampoco.
- Los roles y permisos deben existir en la BD (`PermissionSeeder`, `RoleSeeder`, `RolePermissionSeeder`; `DatabaseSeeder` ya los llama).
- Las validaciones de los formularios web reproducen las de los controladores API (duplicación deliberada para no tocar la API). Una refactorización futura puede compartir FormRequests.

## 6. Pruebas realizadas

Entorno: PHP 8.4, Laravel 13.40, PHPUnit 12, SQLite en memoria (`phpunit.xml`). No se ha tocado MySQL.

| Comprobación | Resultado |
|---|---|
| Rama Luis-Reyes, suite completa | 85 pruebas, 85 correctas, 298 aserciones (50 previas + 35 nuevas) |
| Rama Alvaro-Llorente, suite completa | 118 pruebas, 118 correctas, 465 aserciones |
| Rama Jose-Antonio, suite completa | 116 pruebas, 116 correctas, 537 aserciones |
| Rama Antonio, suite completa | 147 pruebas, 147 correctas, 556 aserciones |
| Copia temporal con las cuatro ramas superpuestas | 316 pruebas: 314 correctas y 2 omitidas a propósito (pruebas que solo valen mientras la rama vecina no está integrada), 0 fallos |
| `npm run build` en esa copia | Correcto (CSS 57 kB, JS 1,8 kB) |
| Prueba de humo HTTP con servidor local y BD SQLite temporal sembrada | Las 22 URLs principales responden 200 con el administrador y enlazan el CSS compilado |
| `vendor/bin/pint --dirty` | Sin incidencias en las cuatro ramas |
| `php artisan route:list` en la copia integrada | 75 rutas web, sin nombres duplicados |

Las pruebas cubren, por pantalla: invitado → login, sin rol → 403, cada rol con datos reales, estados vacíos,
validaciones, creación/edición/borrado reales, permisos denegados (el operario no elimina, el gerente no gestiona
desguaces ni usuarios) y conservación del historial de ubicaciones.

**No verificado:** el aspecto visual en navegador (no hay herramienta de captura disponible), el comportamiento
real de JavaScript (modales, menú móvil, auto-envío de la cascada de ubicaciones: solo se ha comprobado que el JS
es sintácticamente válido y que las clases responsive existen en el CSS compilado), `lockForUpdate` sobre MySQL
(las pruebas usan SQLite) y el uso con la base de datos MySQL real.

## 7. Errores y problemas detectados

- `vendor/` estaba desactualizado (faltaban Sanctum, Spatie y Boost): se ejecutó `composer install` desde `composer.lock`, sin cambiar dependencias. No hay `.env` en el repositorio; se creó uno local (ignorado por Git).
- La integración de prueba descubrió 2 pruebas que asumían que la rama vecina aún no existía; se corrigieron para que valgan antes y después de integrar.
- Un agente de apoyo dejó un archivo vacío (`app/Support/JerarquiaUbicacion.php`) en el directorio de Luis-Reyes; se eliminó. La versión correcta pertenece a Alvaro-Llorente.
- Los mensajes de validación de Laravel son ingleses (`APP_LOCALE=en`, sin carpeta `lang/`). Los formularios nuevos de las otras ramas llevan mensajes en español en sus FormRequest; login y registro usan los de Laravel.
- La carpeta `Proyecto-TFG-ramas/_integracion_temporal` puede haber quedado vacía por bloqueo de Windows; se puede borrar a mano.

## 8. Funcionalidades pendientes y decisiones por confirmar

1. **Registros del sistema (21):** nada escribe en `registro_sistema` (solo `POST /api/registro-sistema`). La pantalla funcionará, pero estará vacía hasta que se decida qué eventos se registran (por ejemplo, el login).
2. **Roles y permisos (20):** solo lectura. La matriz de referencia es `RolePermissionSeeder`; editarla desde la web se pisaría al volver a sembrar.
3. **Reglas de asignación de ubicación (19), supuestos a confirmar:** un vehículo tiene como máximo una ubicación vigente; fechas sin solapamiento (la anterior se cierra el día previo a la nueva); `fecha_fin ≥ fecha_inicio`; el nivel debe ser del mismo desguace que el vehículo; no se supera `capacidad_maxima` de la posición; un nivel aloja un vehículo vigente a la vez; no se repite el nivel vigente.
4. **Bloqueos de borrado añadidos en la web** (la API hace borrado lógico simple): categoría con tipos o movimientos, tipo de pieza con piezas, desguace con dependientes (rama Jose-Antonio); elementos de almacén con hijos activos y niveles con vehículos vigentes (rama Antonio). Confirmar que es el comportamiento deseado.
5. Los tipos de movimiento y los estados de vehículo/pieza son texto libre (como en la API); los formularios sugieren los valores existentes.
6. Registrar un movimiento **no** modifica el stock de piezas (no hay relación en el EER).
7. Despliegue: las vistas usan Vite. `public/build` está en `.gitignore`, así que el servidor debe ejecutar `npm ci && npm run build`, o subir la carpeta compilada. Sin ello las páginas se muestran sin estilos.
8. Traducción global de validaciones a español (`lang/es`) si se desea uniformidad.
9. En la pantalla de categorías cada fila lleva un modal con todos los desplegables; con muchos vehículos puede pesar.

## 9. Instrucciones para integrar las cuatro ramas

Ubicación de los directorios de trabajo (worktrees):

```text
Proyecto-TFG\                              rama Luis-Reyes
Proyecto-TFG-ramas\Alvaro-Llorente\        rama Alvaro-Llorente
Proyecto-TFG-ramas\Jose-Antonio\           rama Jose-Antonio
Proyecto-TFG-ramas\Antonio\                rama Antonio
```

Las tres ramas de `Proyecto-TFG-ramas` son ramas locales creadas desde `origin/Desarrollo` (`f8efb46`).

1. En cada directorio, revisar `git status` y confirmar los cambios (nadie ha hecho commit todavía). Añadir los archivos por carpeta, no con `git add .`.
2. Integrar primero `Luis-Reyes` (contiene el merge de `Desarrollo` y la base compartida), después el resto en cualquier orden. Como los archivos compartidos son idénticos, Git los fusiona sin conflicto.
3. Tras integrar: `composer install`, `npm ci`, `npm run build`, `php artisan test`.
4. Las pruebas que asumen ausencia de otra rama se omiten solas al integrarse.
5. Para retirar los directorios de trabajo: `git worktree remove <ruta>` desde el repositorio principal.

Detalles de ramas remotas a tener en cuenta:

- `Alvaro-Llorente` remota es antecesora de `Desarrollo`: el push de la rama local es un avance rápido.
- `Jose-Antonio` remota tiene una historia **sin relación** (commit raíz «Subida del proyecto Scrapex»). El push de la rama local será rechazado sin un merge con `--allow-unrelated-histories` o una decisión del equipo. Su árbol de archivos solo difiere del de `Desarrollo` en `.htaccess` (ausente), `AGENTS.md`, `CLAUDE.md`, `package-lock.json`, `layouts/app.blade.php` y `welcome.blade.php` (versiones anteriores), por lo que se tomó `Desarrollo` como base.
- La rama `Antonio` no existe en remoto (hay `Antonio-Castaño` y `Antonio-Castaño-v2`, esta última igual a `Desarrollo`). Se creó `Antonio` nueva. Si el equipo prefiere `Antonio-Castaño-v2`, basta con `git branch -m`.

## 10. Conflictos potenciales entre compañeros

- Cualquier edición individual de los archivos de la sección 3 provoca conflicto. Los cambios en la base compartida deben hacerse a la vez en las cuatro ramas.
- `config/navegacion.php`: si alguien añade una opción de menú, el cambio debe pasar por una sola rama.
- Nombres de ruta, de clase y de archivo de rutas son únicos por rama (verificado con la copia integrada: 0 colisiones y 0 nombres duplicados).
- Si alguien más modifica `layouts/app.blade.php` o añade vistas que dependan del layout anterior (estilo naranja con cabecera), tendrán que adaptarlas al nuevo.
- `routes/web/admin.php` pertenece a Antonio y `routes/web/usuarios.php` a Luis-Reyes; no deben reutilizar el nombre del otro.
