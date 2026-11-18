<?php

/*
 * Menú lateral y accesos rápidos del dashboard.
 *
 * Cada elemento solo se muestra si:
 *   - la ruta nombrada existe (así cada rama de desarrollo puede integrarse por separado), y
 *   - el usuario tiene el permiso indicado (Spatie). 'permission' => null significa "cualquier usuario autenticado".
 *
 * Claves de cada elemento:
 *   label, descripcion, route, icon, permission, active (patrones para request()->routeIs()).
 */
return [

    'marca' => 'Desguaces Scrapex',

    'grupos' => [

        'Principal' => [
            [
                'label' => 'Dashboard',
                'descripcion' => 'Resumen general de la actividad.',
                'route' => 'dashboard',
                'icon' => 'home',
                'permission' => null,
                'active' => ['dashboard'],
            ],
        ],

        'Inventario' => [
            [
                'label' => 'Vehículos',
                'descripcion' => 'Vehículos recibidos y su ubicación.',
                'route' => 'vehiculos.index',
                'icon' => 'truck',
                'permission' => 'vehiculos.ver',
                'active' => ['vehiculos.*'],
            ],
            [
                'label' => 'Piezas',
                'descripcion' => 'Catálogo de piezas y existencias.',
                'route' => 'piezas.index',
                'icon' => 'cog',
                'permission' => 'piezas.ver',
                'active' => ['piezas.*'],
            ],
            [
                'label' => 'Movimientos',
                'descripcion' => 'Entradas y salidas de inventario.',
                'route' => 'movimientos.index',
                'icon' => 'arrows',
                'permission' => 'movimientos.ver',
                'active' => ['movimientos.*'],
            ],
            [
                'label' => 'Categorías',
                'descripcion' => 'Clasificación del inventario.',
                'route' => 'categorias.index',
                'icon' => 'tag',
                'permission' => 'categorias.ver',
                'active' => ['categorias.*'],
            ],
        ],

        'Desguaces y almacén' => [
            [
                'label' => 'Desguaces',
                'descripcion' => 'Instalaciones del grupo.',
                'route' => 'desguaces.index',
                'icon' => 'building',
                'permission' => 'desguaces.ver',
                'active' => ['desguaces.*'],
            ],
            [
                'label' => 'Almacén interior',
                'descripcion' => 'Módulos, pasillos, estanterías y etiquetas.',
                'route' => 'almacen.interior.index',
                'icon' => 'archive',
                'permission' => 'modulos.ver',
                'active' => ['almacen.interior.*'],
            ],
            [
                'label' => 'Almacén exterior',
                'descripcion' => 'Patios, sectores, filas, posiciones y niveles.',
                'route' => 'almacen.exterior.index',
                'icon' => 'map',
                'permission' => 'patios.ver',
                'active' => ['almacen.exterior.*'],
            ],
        ],

        'Administración' => [
            [
                'label' => 'Usuarios',
                'descripcion' => 'Cuentas registradas y asignación de roles.',
                'route' => 'admin.usuarios.index',
                'icon' => 'users',
                'permission' => 'usuarios.ver',
                'active' => ['admin.usuarios.*'],
            ],
            [
                'label' => 'Roles y permisos',
                'descripcion' => 'Matriz de permisos por rol.',
                'route' => 'admin.roles-permisos.index',
                'icon' => 'shield',
                'permission' => 'usuarios.asignar_roles',
                'active' => ['admin.roles-permisos.*'],
            ],
            [
                'label' => 'Registros del sistema',
                'descripcion' => 'Auditoría de accesos de usuarios.',
                'route' => 'admin.registros.index',
                'icon' => 'clipboard',
                'permission' => 'registro_sistema.ver',
                'active' => ['admin.registros.*'],
            ],
        ],
    ],
];
