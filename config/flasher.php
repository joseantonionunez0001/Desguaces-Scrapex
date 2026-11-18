<?php

/*
 * PHPFlasher. Solo se sobrescribe lo necesario; el resto usa los valores por defecto del paquete.
 *
 * flash_bag: false desactiva la conversión automática de mensajes de sesión (success, error...).
 * El paquete no guarda esos avisos cuando la respuesta es una redirección, así que
 * resources/views/layouts/app.blade.php los convierte con flash() antes de mostrarlos.
 */
return [
    'flash_bag' => false,
];
