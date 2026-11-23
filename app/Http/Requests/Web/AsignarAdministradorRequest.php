<?php

namespace App\Http\Requests\Web;

use App\Http\Requests\Web\Concerns\MensajesDeValidacion;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

/**
 * Designación (o sustitución) del administrador de un desguace desde la web.
 *
 * Si el correo pertenece a una cuenta existente se usa esa cuenta; si no, se da de alta
 * una cuenta nueva y entonces el nombre y la contraseña son obligatorios.
 */
class AsignarAdministradorRequest extends FormRequest
{
    use MensajesDeValidacion;

    public function authorize(): bool
    {
        return $this->user()?->can('desguaces.asignar_administrador') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', Password::defaults()],
            '_modal' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [function (Validator $validador): void {
            if ($validador->errors()->isNotEmpty() || $this->cuentaExistente() !== null) {
                return;
            }

            if (blank($this->input('name'))) {
                $validador->errors()->add('name', 'No hay ninguna cuenta con ese correo: indica el nombre para darla de alta.');
            }

            if (blank($this->input('password'))) {
                $validador->errors()->add('password', 'No hay ninguna cuenta con ese correo: indica una contraseña para darla de alta.');
            }
        }];
    }

    /**
     * Cuenta activa con el correo indicado, si existe.
     */
    public function cuentaExistente(): ?User
    {
        return User::query()->where('email', $this->input('email'))->first();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'email' => 'correo electrónico',
            'name' => 'nombre',
            'password' => 'contraseña',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->mensajesEnEspanol(['email', 'name', 'password']);
    }
}
