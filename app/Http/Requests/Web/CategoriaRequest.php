<?php

namespace App\Http\Requests\Web;

use App\Http\Requests\Web\Concerns\MensajesDeValidacion;
use App\Models\Categoria;
use App\Support\Acceso;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

/**
 * Alta y edición de categorías: solo se pide el nombre («Electricidad», «Carrocería», «Motor»…).
 *
 * La categoría pertenece al desguace del usuario, que asigna el servidor; el módulo ya no es obligatorio
 * (ver la migración simplificar_categorias_y_tipos_de_pieza). El nombre no se repite dentro de la sede.
 */
class CategoriaRequest extends FormRequest
{
    use MensajesDeValidacion;

    public function authorize(): bool
    {
        $permiso = $this->isMethod('POST') ? 'categorias.crear' : 'categorias.editar';

        return $this->user()?->can($permiso) ?? false;
    }

    /**
     * @return array<string, array<int, ValidationRule|string|Unique>>
     */
    public function rules(): array
    {
        $categoria = $this->route('categoria');

        return [
            'nombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categorias', 'nombre')
                    ->where('id_desguace', Acceso::desguaceId($this->user()))
                    ->whereNull('deleted_at')
                    ->ignore($categoria instanceof Categoria ? $categoria->getKey() : null, 'categoria_id'),
            ],
            'id_desguace' => ['prohibited'],
            '_modal' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...$this->mensajesEnEspanol(['nombre']),
            'nombre.unique' => 'Ya existe una categoría con ese nombre en tu desguace.',
            'id_desguace.prohibited' => 'El desguace lo asigna Scrapex automáticamente.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['nombre' => 'nombre'];
    }
}
