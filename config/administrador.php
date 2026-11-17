<?php

/*
 * Cuenta de administrador que crea UsuarioAdministradorSeeder.
 * La contraseña se define en el .env (ADMIN_PASSWORD) y nunca se guarda en el código.
 */
return [
    'email' => env('ADMIN_EMAIL', 'javi.ruiz@davante.org'),
    'password' => env('ADMIN_PASSWORD'),
];
