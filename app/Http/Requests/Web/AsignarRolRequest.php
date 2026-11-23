<?php

namespace App\Http\Requests\Web;

use App\Support\Acceso;
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
            'rol' => ['required', 'string', Rule::in(Acceso::rolesConcedibles($this->user()))],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'rol.required' => 'Selecciona el rol que quieres asignar.',
            'rol.in' => 'Solo puedes asignar los roles de gerente u operario.',
        ];
    }
}
