<?php

namespace App\Http\Requests\Web;

use App\Models\Desguace;
use App\Models\Vehiculo;
use App\Rules\ExisteEnMiSede;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validación de alta y edición de vehículos.
 * Reproduce las reglas de VehiculoApiController (store y update).
 */
class GuardarVehiculoRequest extends FormRequest
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
        // En edición hay un vehículo enlazado en la ruta y se ignora su propio registro en la unicidad.
        $vehiculo = $this->route('vehiculo');
        $vehiculo = $vehiculo instanceof Vehiculo ? $vehiculo : null;

        return [
            'id_desguace' => ['required', new ExisteEnMiSede(Desguace::class)],
            'matricula' => [
                'required',
                'string',
                'max:20',
                Rule::unique('vehiculos', 'matricula')->ignore($vehiculo?->getKey(), 'id_vehiculo'),
            ],
            'marca' => ['required', 'string', 'max:255'],
            'modelo' => ['required', 'string', 'max:255'],
            'fecha_llegada' => ['required', 'date'],
            'numero_bastidor' => [
                'required',
                'string',
                'size:17',
                Rule::unique('vehiculos', 'numero_bastidor')->ignore($vehiculo?->getKey(), 'id_vehiculo'),
            ],
            'estado_tratamiento' => ['required', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'id_desguace' => 'desguace',
            'matricula' => 'matrícula',
            'marca' => 'marca',
            'modelo' => 'modelo',
            'fecha_llegada' => 'fecha de llegada',
            'numero_bastidor' => 'número de bastidor',
            'estado_tratamiento' => 'estado de tratamiento',
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
            'max' => 'El campo :attribute no puede superar los :max caracteres.',
            'size' => 'El campo :attribute debe tener exactamente :size caracteres.',
            'date' => 'El campo :attribute no es una fecha válida.',
            'exists' => 'El :attribute seleccionado no es válido.',
            'matricula.unique' => 'Ya existe un vehículo con esa matrícula.',
            'numero_bastidor.unique' => 'Ya existe un vehículo con ese número de bastidor.',
        ];
    }
}
