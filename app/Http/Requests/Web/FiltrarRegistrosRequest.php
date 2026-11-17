<?php

namespace App\Http\Requests\Web;

use App\Http\Requests\Web\Concerns\MensajesDeValidacion;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Filtros (por GET) del listado de registros del sistema.
 */
class FiltrarRegistrosRequest extends FormRequest
{
    use MensajesDeValidacion;

    /**
     * La autorización real la hace el middleware de permisos de la ruta.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'usuario' => ['nullable', 'integer'],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
            'q' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'usuario' => 'usuario',
            'desde' => 'fecha desde',
            'hasta' => 'fecha hasta',
            'q' => 'búsqueda',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...$this->mensajesEnEspanol(['usuario', 'desde', 'hasta', 'q']),
            'hasta.after_or_equal' => 'La fecha "hasta" no puede ser anterior a la fecha "desde".',
        ];
    }
}
