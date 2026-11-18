<?php

namespace App\Http\Requests\Web;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Alta y edición de tipos de pieza. Reproduce las reglas de TipoPiezaApiController.
 */
class TipoPiezaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $permiso = $this->isMethod('POST') ? 'tipo_piezas.crear' : 'tipo_piezas.editar';

        return $this->user()?->can($permiso) ?? false;
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'marca' => ['required', 'string', 'max:255'],
            'modelo' => ['required', 'string', 'max:255'],
            'fabricante' => ['required', 'string', 'max:255'],
            'categoria' => ['required', 'string', 'max:255'],
            'id_categoria' => ['required', Rule::exists('categorias', 'categoria_id')->whereNull('deleted_at')],
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
            'exists' => 'La categoría seleccionada no existe.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nombre' => 'nombre',
            'marca' => 'marca',
            'modelo' => 'modelo',
            'fabricante' => 'fabricante',
            'categoria' => 'categoría (texto)',
            'id_categoria' => 'categoría relacionada',
        ];
    }
}
