<?php

namespace App\Http\Requests\Web;

use App\Http\Requests\Web\Concerns\MensajesDeValidacion;
use App\Models\User;
use App\Support\Acceso;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Alta y edición de cuentas del equipo de un desguace (gerentes y operarios).
 *
 * El rol se elige en el formulario, pero solo entre los que el usuario puede conceder.
 * El desguace no se acepta nunca: lo asigna el servidor con la sede del administrador.
 */
class GuardarUsuarioRequest extends FormRequest
{
    use MensajesDeValidacion;

    public function authorize(): bool
    {
        $permiso = $this->isMethod('POST') ? 'usuarios.crear' : 'usuarios.editar';

        return $this->user()?->can($permiso) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $usuario = $this->route('user');
        $usuario = $usuario instanceof User ? $usuario : null;
        $esAlta = $usuario === null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($usuario?->getKey()),
            ],
            'password' => [$esAlta ? 'required' : 'nullable', 'confirmed', Password::defaults()],
            'rol' => ['required', 'string', Rule::in(Acceso::rolesConcedibles($this->user()))],
            // La sede nunca viene del formulario.
            'id_desguace' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'email' => 'correo electrónico',
            'password' => 'contraseña',
            'rol' => 'rol',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...$this->mensajesEnEspanol(['name', 'email', 'password', 'rol']),
            'email.unique' => 'Ya existe una cuenta con ese correo electrónico.',
            'email.email' => 'El correo electrónico no es válido.',
            'email.lowercase' => 'El correo electrónico debe estar en minúsculas.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
            'rol.in' => 'Solo puedes asignar los roles de gerente u operario.',
            'id_desguace.prohibited' => 'El desguace lo asigna Scrapex automáticamente.',
        ];
    }
}
