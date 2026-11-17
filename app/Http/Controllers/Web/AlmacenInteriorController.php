<?php

namespace App\Http\Controllers\Web;

use App\Services\EstructuraAlmacen;

/**
 * Pantalla 17: gestión del almacén interior (Desguace → Módulo → Pasillo → Estantería → Etiqueta).
 */
class AlmacenInteriorController extends AlmacenController
{
    protected function zona(): string
    {
        return EstructuraAlmacen::INTERIOR;
    }
}
