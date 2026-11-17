<?php

namespace App\Support;

use App\Models\Categoria;

/**
 * Opciones de categorías para los desplegables de los formularios y filtros.
 */
class OpcionesCategorias
{
    /**
     * Categorías existentes con el formato [categoria_id => "Nombre · Desguace"].
     * Se incluye el desguace porque varios desguaces pueden tener categorías con el mismo nombre.
     *
     * @return array<int, string>
     */
    public static function paraSelect(): array
    {
        return Categoria::query()
            ->with('desguace')
            ->orderBy('nombre')
            ->get()
            ->mapWithKeys(fn (Categoria $categoria): array => [
                $categoria->categoria_id => $categoria->nombre.' · '.($categoria->desguace?->nombre ?? 'Sin desguace'),
            ])
            ->all();
    }
}
