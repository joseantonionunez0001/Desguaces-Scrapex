<?php

namespace App\Models\Concerns;

use App\Models\Scopes\AlcanceDesguaceScope;
use Illuminate\Database\Eloquent\Builder;

/**
 * Aplica el alcance por desguace a un modelo. Cada modelo define scopeDelDesguace()
 * usando SUS relaciones reales hasta llegar a la columna id_desguace.
 */
trait RestringidoPorDesguace
{
    public static function bootRestringidoPorDesguace(): void
    {
        static::addGlobalScope(new AlcanceDesguaceScope);
    }

    // El administrador de plataforma NO ve el inventario; solo Desguace lo sobrescribe.
    public static function plataformaVeTodo(): bool
    {
        return false;
    }

    /**
     * Filtra por la sede a la que pertenece el padre indicado.
     *
     * Se quitan los scopes globales del padre (también el borrado lógico): la pertenencia a una sede
     * no cambia porque el padre esté archivado, y así el historial conserva sus registros.
     * El propio modelo sigue excluyendo sus registros borrados con su SoftDeletes.
     */
    protected function enSede(Builder $consulta, string $relacion, int $idDesguace): void
    {
        $consulta->whereHas(
            $relacion,
            fn (Builder $sub) => $sub->withoutGlobalScopes()->delDesguace($idDesguace)
        );
    }
}
