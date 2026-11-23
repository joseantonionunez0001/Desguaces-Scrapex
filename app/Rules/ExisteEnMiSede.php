<?php

namespace App\Rules;

use App\Support\Acceso;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Sustituye a "exists:" en los formularios: el registro referenciado debe existir, no estar
 * eliminado y pertenecer al desguace autorizado del usuario. El mensaje es el mismo si no existe
 * o si es de otra sede, para no revelar datos ajenos.
 */
class ExisteEnMiSede implements ValidationRule
{
    /**
     * @param  class-string<Model>  $modelo
     */
    public function __construct(private string $modelo) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $idDesguace = Acceso::desguaceId(Auth::user());

        $valido = $idDesguace !== null
            && is_scalar($value)
            && $this->modelo::query()->delDesguace($idDesguace)->whereKey($value)->exists();

        if (! $valido) {
            $fail('El valor seleccionado en :attribute no existe o no pertenece a tu desguace.');
        }
    }
}
