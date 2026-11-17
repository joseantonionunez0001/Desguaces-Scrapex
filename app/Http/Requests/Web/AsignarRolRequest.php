<?php

namespace App\Http\Requests\Web;

use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AsignarRolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('usuarios.asignar_roles') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'rol' => ['required', 'string', Rule::in(RoleSeeder::ROLES)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'rol.required' => 'Selecciona el rol que quieres asignar.',
            'rol.in' => 'El rol seleccionado no es válido.',
        ];
    }
}
