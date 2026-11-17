<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validación de alta y edición de piezas.
 * Reproduce las reglas de PiezaApiController salvo "id_usuario": en la web el usuario
 * registrador es el autenticado y no se acepta desde el formulario.
 */
class GuardarPiezaRequest extends FormRequest
{
    /**
     * La autorización por permiso se aplica con el middleware de la ruta.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            // Los máximos evitan desbordar las columnas decimal(10,2) y unsignedInteger.
            'precio' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'estado' => ['required', 'string', 'max:50'],
            'stock' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'stock_minimo' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'id_tipo_pieza' => ['required', Rule::exists('tipo_piezas', 'id_pieza')->whereNull('deleted_at')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nombre' => 'nombre',
            'precio' => 'precio',
            'estado' => 'estado',
            'stock' => 'stock',
            'stock_minimo' => 'stock mínimo',
            'id_tipo_pieza' => 'tipo de pieza',
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
            'numeric' => 'El campo :attribute debe ser un número.',
            'integer' => 'El campo :attribute debe ser un número entero.',
            'min' => 'El campo :attribute no puede ser negativo.',
            'max' => 'El campo :attribute supera el valor máximo permitido.',
            'nombre.max' => 'El nombre no puede superar los :max caracteres.',
            'estado.max' => 'El estado no puede superar los :max caracteres.',
            'exists' => 'El :attribute seleccionado no es válido.',
        ];
    }
}
