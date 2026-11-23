<?php

namespace App\Http\Requests\Web;

use App\Models\Categoria;
use App\Rules\ExisteEnMiSede;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Alta de movimientos. Reproduce las reglas de MovimientoApiController::store.
 */
class MovimientoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('movimientos.crear') ?? false;
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'tipo_movimiento' => ['required', 'string', 'max:50'],
            'fecha' => ['required', 'date'],
            // El tope evita un error de base de datos con enteros mayores que el tipo INT de la columna.
            'cantidad' => ['required', 'integer', 'min:1', 'max:2147483647'],
            'id_categoria' => ['required', new ExisteEnMiSede(Categoria::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser un texto.',
            'max.string' => 'El campo :attribute no puede superar los :max caracteres.',
            'max.numeric' => 'El campo :attribute no puede ser mayor que :max.',
            'min.numeric' => 'El campo :attribute debe ser como mínimo :min.',
            'integer' => 'El campo :attribute debe ser un número entero.',
            'date' => 'El campo :attribute no es una fecha válida.',
            'exists' => 'La categoría seleccionada no existe.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'tipo_movimiento' => 'tipo de movimiento',
            'fecha' => 'fecha',
            'cantidad' => 'cantidad',
            'id_categoria' => 'categoría',
        ];
    }
}
