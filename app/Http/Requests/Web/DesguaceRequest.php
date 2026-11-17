<?php

namespace App\Http\Requests\Web;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Alta y edición de desguaces. Reproduce las reglas de DesguaceApiController.
 * La columna "ubicación" lleva tilde, igual que en la migración y en la API.
 */
class DesguaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $permiso = $this->isMethod('POST') ? 'desguaces.crear' : 'desguaces.editar';

        return $this->user()?->can($permiso) ?? false;
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'direccion' => ['required', 'string', 'max:255'],
            'numero_telefono' => ['required', 'string', 'max:20'],
            'ubicación' => ['required', 'string', 'max:255'],
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
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nombre' => 'nombre',
            'direccion' => 'dirección',
            'numero_telefono' => 'teléfono',
            'ubicación' => 'ubicación',
        ];
    }
}
