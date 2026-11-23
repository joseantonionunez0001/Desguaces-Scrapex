<?php

namespace App\Http\Requests\Web;

use App\Models\Modulo;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Alta y edición de categorías. Reproduce las reglas de CategoriaApiController y añade
 * la coherencia con el esquema: el módulo debe pertenecer al desguace elegido.
 */
class CategoriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $permiso = $this->isMethod('POST') ? 'categorias.crear' : 'categorias.editar';

        return $this->user()?->can($permiso) ?? false;
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'id_desguace' => ['required', Rule::exists('desguaces', 'id_desguace')->whereNull('deleted_at')],
            'id_modulo' => ['required', Rule::exists('modulos', 'id_modulo')->whereNull('deleted_at')],
            'id_vehiculo' => ['nullable', Rule::exists('vehiculos', 'id_vehiculo')->whereNull('deleted_at')],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['id_desguace', 'id_modulo'])) {
                    return;
                }

                $moduloPerteneceAlDesguace = Modulo::query()
                    ->whereKey($this->input('id_modulo'))
                    ->where('id_desguace', $this->input('id_desguace'))
                    ->exists();

                if (! $moduloPerteneceAlDesguace) {
                    $validator->errors()->add('id_modulo', 'El módulo seleccionado no pertenece al desguace elegido.');
                }
            },
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
            'exists' => 'El valor seleccionado en :attribute no existe.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nombre' => 'nombre',
            'id_desguace' => 'desguace',
            'id_modulo' => 'módulo',
            'id_vehiculo' => 'vehículo',
        ];
    }
}
