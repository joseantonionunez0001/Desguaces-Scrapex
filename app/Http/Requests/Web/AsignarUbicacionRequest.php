<?php

namespace App\Http\Requests\Web;

use App\Http\Requests\Web\Concerns\MensajesDeValidacion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Asignación de ubicación de un vehículo. Reproduce las reglas de UbicacionVehiculoApiController
 * (el vehículo viene de la ruta). Las reglas de negocio se aplican en AsignacionUbicacionVehiculo.
 */
class AsignarUbicacionRequest extends FormRequest
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
            'id_nivel' => ['required', Rule::exists('niveles', 'id_nivel')->whereNull('deleted_at')],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'patio' => ['nullable', 'integer'],
            'sector' => ['nullable', 'integer'],
            'fila' => ['nullable', 'integer'],
            'posicion' => ['nullable', 'integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'id_nivel' => 'nivel',
            'fecha_inicio' => 'fecha de inicio',
            'fecha_fin' => 'fecha de fin',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...$this->mensajesEnEspanol(['id_nivel', 'fecha_inicio', 'fecha_fin']),
            'fecha_fin.after_or_equal' => 'La fecha de fin no puede ser anterior a la fecha de inicio.',
            'id_nivel.required' => 'Elige el nivel de destino.',
        ];
    }

    /**
     * Parámetros del selector en cascada, para volver al mismo paso si hay errores.
     *
     * @return array<string, int>
     */
    public function pasosDeLaCascada(): array
    {
        return array_map('intval', array_filter(
            $this->only(['patio', 'sector', 'fila', 'posicion']),
            fn (mixed $valor): bool => is_scalar($valor) && ctype_digit((string) $valor),
        ));
    }

    protected function getRedirectUrl(): string
    {
        return route('vehiculos.ubicaciones.asignar', ['vehiculo' => $this->route('vehiculo')] + $this->pasosDeLaCascada());
    }
}
