<?php

namespace App\Http\Requests\Web;

use App\Http\Requests\Web\Concerns\MensajesDeValidacion;
use App\Models\Categoria;
use App\Models\Etiqueta;
use App\Models\ModeloVehiculo;
use App\Models\Pieza;
use App\Models\TipoPieza;
use App\Rules\ExisteEnMiSede;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Alta y edición de piezas.
 *
 * - El alta pide además la cantidad que ha entrado y su ubicación inicial: se registran como la
 *   Entrada inicial. Después, el stock solo cambia con movimientos (el campo "stock" está prohibido).
 * - El modelo debe corresponder a la marca y el tipo a la categoría (única fuente: tipo_piezas.id_categoria).
 * - El usuario registrador es el autenticado: no se acepta desde el formulario.
 */
class GuardarPiezaRequest extends FormRequest
{
    use MensajesDeValidacion;

    /**
     * La autorización por permiso se aplica con el middleware de la ruta.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function esAlta(): bool
    {
        return ! $this->route('pieza') instanceof Pieza;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $pieza = $this->route('pieza');
        $estadoActual = $pieza instanceof Pieza ? $pieza->estado : null;

        $reglas = [
            'nombre' => ['required', 'string', 'max:255'],
            'id_marca' => ['required', 'integer', Rule::exists('marcas', 'id_marca')],
            'id_modelo_vehiculo' => ['required', 'integer', Rule::exists('modelos_vehiculo', 'id_modelo')],
            'id_categoria' => ['required', new ExisteEnMiSede(Categoria::class)],
            'id_tipo_pieza' => ['required', new ExisteEnMiSede(TipoPieza::class)],
            // Se conserva el estado de las piezas antiguas aunque no esté entre los nuevos.
            'estado' => ['required', 'string', 'max:50', Rule::in(array_filter([...Pieza::ESTADOS_FISICOS, $estadoActual]))],
            // Los máximos evitan desbordar las columnas decimal(10,2) y unsignedInteger.
            'precio' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'stock_minimo' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'stock' => ['prohibited'],
            'id_usuario' => ['prohibited'],
        ];

        if ($this->esAlta()) {
            $reglas['cantidad'] = ['required', 'integer', 'min:1', 'max:4294967295'];
            $reglas['id_etiqueta'] = ['required', new ExisteEnMiSede(Etiqueta::class)];
            $reglas['clave_envio'] = ['required', 'string', 'uuid'];
        }

        return $reglas;
    }

    /**
     * Coherencia marca/modelo y categoría/tipo, comprobada en la base de datos.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [function (Validator $validador): void {
            $errores = $validador->errors();

            if (! $errores->hasAny(['id_marca', 'id_modelo_vehiculo'])) {
                $modeloDeLaMarca = ModeloVehiculo::query()
                    ->whereKey($this->input('id_modelo_vehiculo'))
                    ->where('id_marca', $this->input('id_marca'))
                    ->exists();

                if (! $modeloDeLaMarca) {
                    $errores->add('id_modelo_vehiculo', 'El modelo elegido no corresponde a la marca seleccionada.');
                }
            }

            if (! $errores->hasAny(['id_categoria', 'id_tipo_pieza'])) {
                $tipoDeLaCategoria = TipoPieza::query()
                    ->whereKey($this->input('id_tipo_pieza'))
                    ->where('id_categoria', $this->input('id_categoria'))
                    ->exists();

                if (! $tipoDeLaCategoria) {
                    $errores->add('id_tipo_pieza', 'El tipo de pieza elegido no pertenece a la categoría seleccionada.');
                }
            }
        }];
    }

    /**
     * Campos propios de la pieza (sin cantidad, ubicación, marca ni categoría, que no son columnas de piezas).
     *
     * @return array<string, mixed>
     */
    public function datosDeLaPieza(): array
    {
        return Arr::only($this->validated(), ['nombre', 'precio', 'estado', 'stock_minimo', 'id_tipo_pieza', 'id_modelo_vehiculo']);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nombre' => 'nombre',
            'id_marca' => 'marca del vehículo',
            'id_modelo_vehiculo' => 'modelo del vehículo',
            'id_categoria' => 'categoría',
            'id_tipo_pieza' => 'tipo de pieza',
            'estado' => 'estado físico',
            'precio' => 'precio',
            'stock_minimo' => 'stock mínimo',
            'cantidad' => 'cantidad que ha entrado',
            'id_etiqueta' => 'ubicación inicial',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...$this->mensajesEnEspanol(['nombre', 'id_marca', 'id_modelo_vehiculo', 'id_categoria', 'id_tipo_pieza', 'estado', 'precio', 'stock_minimo', 'cantidad', 'id_etiqueta']),
            'numeric' => 'El campo :attribute debe ser un número.',
            'precio.max' => 'El precio no puede superar 99.999.999,99 €.',
            'stock_minimo.max' => 'El stock mínimo es demasiado alto.',
            'cantidad.max' => 'La cantidad es demasiado alta.',
            'estado.in' => 'Elige uno de los estados físicos de la lista.',
            'stock.prohibited' => 'El stock solo cambia registrando movimientos.',
            'id_usuario.prohibited' => 'El usuario que registra la pieza es siempre el autenticado.',
            'clave_envio.required' => 'El formulario ha caducado. Vuelve a abrirlo e inténtalo de nuevo.',
            'clave_envio.uuid' => 'El formulario ha caducado. Vuelve a abrirlo e inténtalo de nuevo.',
        ];
    }
}
