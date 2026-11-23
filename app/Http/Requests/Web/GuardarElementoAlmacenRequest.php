<?php

namespace App\Http\Requests\Web;

use App\Http\Requests\Web\Concerns\MensajesDeValidacion;
use App\Models\Desguace;
use App\Models\Estanteria;
use App\Models\Fila;
use App\Models\Modulo;
use App\Models\Pasillo;
use App\Models\Patio;
use App\Models\Posicion;
use App\Models\Sector;
use App\Rules\ExisteEnMiSede;
use App\Services\AlmacenJerarquia;
use App\Services\EstructuraAlmacen;
use App\Services\OcupacionAlmacen;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Validator;

/**
 * Alta y edición de cualquier elemento del almacén (módulos, pasillos, patios, posiciones…).
 * El tipo llega como valor por defecto de la ruta ("modulos", "pasillos"…).
 *
 * Las reglas reproducen las de los controladores API, con dos diferencias:
 *  - el padre debe existir y no estar eliminado lógicamente;
 *  - el padre debe pertenecer al camino mostrado en pantalla (campos ruta[...]).
 */
class GuardarElementoAlmacenRequest extends FormRequest
{
    use MensajesDeValidacion;

    /**
     * Modelo de cada tabla padre, para validar que pertenece a la sede del usuario.
     */
    private const MODELOS_PADRE = [
        'desguaces' => Desguace::class,
        'modulos' => Modulo::class,
        'pasillos' => Pasillo::class,
        'estanterias' => Estanteria::class,
        'patios' => Patio::class,
        'sectores' => Sector::class,
        'filas' => Fila::class,
        'posiciones' => Posicion::class,
    ];

    /**
     * La autorización real la hace el middleware de permisos de cada ruta.
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
        $definicion = $this->definicion();

        $reglas = [
            $definicion['columna_padre'] => [
                'required',
                'integer',
                // El padre debe existir y pertenecer al desguace autorizado.
                new ExisteEnMiSede(self::MODELOS_PADRE[$definicion['tabla_padre']]),
            ],
            'ruta' => ['required', 'array'],
            'ruta.*' => ['nullable', 'integer'],
            '_modal' => ['nullable', 'string', 'max:100'],
        ];

        foreach ($definicion['campos'] as $campo => $configuracion) {
            $reglas[$campo] = $configuracion['reglas'];
        }

        return $reglas;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $definicion = $this->definicion();

        $atributos = [$definicion['columna_padre'] => 'elemento padre', 'ruta' => 'ruta'];
        foreach ($definicion['campos'] as $campo => $configuracion) {
            $atributos[$campo] = mb_strtolower($configuracion['etiqueta']);
        }

        return $atributos;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $definicion = $this->definicion();

        return [
            ...$this->mensajesEnEspanol([$definicion['columna_padre'], 'ruta', ...array_keys($definicion['campos'])]),
            'ruta.*.integer' => 'La ruta mostrada no es válida.',
            '_modal.max' => 'El formulario no es válido.',
        ];
    }

    /**
     * Comprobaciones que dependen de la base de datos y del camino mostrado.
     */
    public function after(): array
    {
        return [function (Validator $validador): void {
            if ($validador->errors()->isNotEmpty()) {
                return;
            }

            $definicion = $this->definicion();
            $columnaPadre = $definicion['columna_padre'];

            if (! app(AlmacenJerarquia::class)->padreEnCamino($definicion['tipo'], (int) $this->input($columnaPadre), $this->input('ruta', []))) {
                $validador->errors()->add($columnaPadre, 'El elemento padre no pertenece a la ruta mostrada. Vuelve a abrir el nivel e inténtalo de nuevo.');

                return;
            }

            $this->comprobarCapacidadDePosicion($validador, $definicion);
        }];
    }

    /**
     * Datos listos para asignar al modelo: solo el padre y los campos propios del nivel.
     *
     * @return array<string, mixed>
     */
    public function datosDelElemento(): array
    {
        $definicion = $this->definicion();

        return Arr::only($this->validated(), [$definicion['columna_padre'], ...array_keys($definicion['campos'])]);
    }

    /**
     * @return array<string, mixed>
     */
    public function definicion(): array
    {
        return EstructuraAlmacen::definicion((string) $this->route('tipo'));
    }

    /**
     * Una posición no puede quedarse con menos capacidad que vehículos vigentes tiene ya.
     * (Se aplica solo al editar: en el alta no hay ocupación.)
     *
     * @param  array<string, mixed>  $definicion
     */
    private function comprobarCapacidadDePosicion(Validator $validador, array $definicion): void
    {
        if ($definicion['tipo'] !== 'posiciones' || ! $this->route('id')) {
            return;
        }

        $idPosicion = (int) $this->route('id');
        $ocupados = app(OcupacionAlmacen::class)->ocupadosPorPosicion([$idPosicion])[$idPosicion] ?? 0;

        if ((int) $this->input('capacidad_maxima') < $ocupados) {
            $validador->errors()->add('capacidad_maxima', "La capacidad no puede ser menor que los vehículos ubicados actualmente en la posición ({$ocupados}).");
        }
    }
}
