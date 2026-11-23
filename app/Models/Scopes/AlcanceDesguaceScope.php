<?php

namespace App\Models\Scopes;

use App\Models\User;
use App\Support\Acceso;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Limita toda consulta de inventario al desguace del usuario autenticado.
 *
 * - Sin usuario autenticado (consola, seeders): sin restriccion.
 * - Usuario autenticado sin sede valida, sin rol o de plataforma: no ve nada (denegado por defecto).
 * - Usuario con sede: solo ve registros de esa sede (a traves de las relaciones del modelo).
 */
class AlcanceDesguaceScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $usuario = Auth::user();

        if (! $usuario instanceof User) {
            return;
        }

        if ($usuario->esAdministradorPlataforma() && $model::plataformaVeTodo()) {
            return;
        }

        $idDesguace = Acceso::desguaceId($usuario);

        if ($idDesguace === null) {
            $builder->whereRaw('1 = 0');

            return;
        }

        $builder->delDesguace($idDesguace);
    }
}
