<?php

namespace App\Http\Requests\Web;

use App\Http\Requests\Web\Concerns\MensajesDeValidacion;
use App\Models\Categoria;
use App\Rules\ExisteEnMiSede;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Alta y edición de tipos de pieza («Alternador», «Faro delantero»…), vinculados a su categoría.
 *
 * La relación con la categoría tiene una única fuente: id_categoria. La marca y el modelo del vehículo
 * pertenecen a cada pieza, no al tipo. El fabricante es opcional.
 */
class TipoPiezaRequest extends FormRequest
{
    use MensajesDeValidacion;

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
            'id_categoria' => ['required', new ExisteEnMiSede(Categoria::class)],
            'fabricante' => ['nullable', 'string', 'max:255'],
            '_modal' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->mensajesEnEspanol(['nombre', 'id_categoria', 'fabricante']);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nombre' => 'nombre',
            'id_categoria' => 'categoría',
            'fabricante' => 'fabricante',
        ];
    }
}
