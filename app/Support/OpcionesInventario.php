<?php

namespace App\Support;

use App\Models\Categoria;
use App\Models\Etiqueta;
use App\Models\ExistenciaPieza;
use App\Models\Marca;
use App\Models\ModeloVehiculo;
use App\Models\Pieza;
use App\Models\TipoPieza;

/**
 * Opciones de los desplegables de inventario (filtros y formularios).
 *
 * Las categorías, los tipos y los huecos salen de consultas con el alcance por sede, así que cada usuario
 * solo ve los de su desguace. Las marcas y modelos son un catálogo común.
 */
class OpcionesInventario
{
    /**
     * @return array<int, string>
     */
    public static function marcas(): array
    {
        return Marca::query()->orderBy('nombre')->pluck('nombre', 'id_marca')->all();
    }

    /**
     * Modelos con la marca a la que pertenecen (para el desplegable dependiente).
     *
     * @return array{opciones: array<int, string>, padres: array<int, int>}
     */
    public static function modelos(): array
    {
        $modelos = ModeloVehiculo::query()->orderBy('nombre')->get(['id_modelo', 'id_marca', 'nombre']);

        return [
            'opciones' => $modelos->pluck('nombre', 'id_modelo')->all(),
            'padres' => $modelos->pluck('id_marca', 'id_modelo')->all(),
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function categorias(): array
    {
        return Categoria::query()->orderBy('nombre')->pluck('nombre', 'categoria_id')->all();
    }

    /**
     * Tipos de pieza con su categoría (única fuente de la relación: tipo_piezas.id_categoria).
     *
     * @return array{opciones: array<int, string>, padres: array<int, int>}
     */
    public static function tipos(): array
    {
        $tipos = TipoPieza::query()->orderBy('nombre')->get(['id_pieza', 'id_categoria', 'nombre', 'fabricante']);

        return [
            'opciones' => $tipos->mapWithKeys(fn (TipoPieza $tipo): array => [
                $tipo->id_pieza => $tipo->nombre.($tipo->fabricante ? ' ('.$tipo->fabricante.')' : ''),
            ])->all(),
            'padres' => $tipos->pluck('id_categoria', 'id_pieza')->all(),
        ];
    }

    /**
     * Huecos (etiquetas) del almacén interior de la sede: «Módulo · Pasillo · Estantería · Etiqueta».
     *
     * @return array<int, string>
     */
    public static function huecos(): array
    {
        return Etiqueta::query()
            ->with(Etiqueta::relacionesDeRuta())
            ->get()
            ->mapWithKeys(fn (Etiqueta $etiqueta): array => [$etiqueta->id_etiqueta => $etiqueta->rutaCompleta()])
            ->sort()
            ->all();
    }

    /**
     * Huecos en los que la pieza tiene unidades (orígenes posibles), con su cantidad.
     * La clave «pendiente» es el stock «Pendiente de ubicar».
     *
     * @return array<string, string>
     */
    public static function origenes(Pieza $pieza): array
    {
        return ExistenciaPieza::query()
            ->where('id_pieza', $pieza->getKey())
            ->where('cantidad', '>', 0)
            ->with(['etiqueta' => fn ($consulta) => $consulta->withTrashed(), ...Etiqueta::relacionesDeRuta('etiqueta.')])
            ->get()
            ->mapWithKeys(fn (ExistenciaPieza $existencia): array => [
                ($existencia->id_etiqueta ?? 'pendiente') => ($existencia->etiqueta?->rutaCompleta() ?? 'Pendiente de ubicar')
                    .' ('.$existencia->cantidad.' '.($existencia->cantidad === 1 ? 'unidad' : 'unidades').')',
            ])
            ->all();
    }

    /**
     * Estados físicos que se ofrecen, más los ya usados en piezas anteriores (se conservan tal cual).
     *
     * @return array<string, string>
     */
    public static function estadosFisicos(): array
    {
        $usados = Pieza::query()->whereNotNull('estado')->distinct()->orderBy('estado')->pluck('estado')->all();

        return collect([...Pieza::ESTADOS_FISICOS, ...$usados])
            ->unique()
            ->mapWithKeys(fn (string $estado): array => [$estado => $estado])
            ->all();
    }
}
