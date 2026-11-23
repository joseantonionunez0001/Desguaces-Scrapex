<?php

namespace App\Services;

use App\Models\Estanteria;
use App\Models\Etiqueta;
use App\Models\Fila;
use App\Models\Modulo;
use App\Models\Nivel;
use App\Models\Pasillo;
use App\Models\Patio;
use App\Models\Posicion;
use App\Models\Sector;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

/**
 * Descripción de las dos jerarquías del almacén:
 *
 *  - interior: Desguace → Módulo → Pasillo → Estantería → Etiqueta
 *  - exterior: Desguace → Patio → Sector → Fila → Posición → Nivel
 *
 * Cada nivel se identifica por su "tipo" (el plural usado en las rutas y en los permisos:
 * "modulos", "pasillos"…). Las reglas de validación reproducen las de los controladores API.
 */
final class EstructuraAlmacen
{
    public const INTERIOR = 'interior';

    public const EXTERIOR = 'exterior';

    /** Clave de consulta (y de ruta) del desguace, raíz de ambas jerarquías. */
    public const CLAVE_DESGUACE = 'desguace';

    /**
     * Niveles de una zona, en orden descendente (del más cercano al desguace al más profundo).
     *
     * @return array<string, array<string, mixed>>
     */
    public static function niveles(string $zona): array
    {
        $tipos = $zona === self::INTERIOR
            ? ['modulos', 'pasillos', 'estanterias', 'etiquetas']
            : ['patios', 'sectores', 'filas', 'posiciones', 'niveles'];

        return Arr::only(self::definiciones(), $tipos);
    }

    /**
     * @return list<string>
     */
    public static function tipos(string $zona): array
    {
        return array_keys(self::niveles($zona));
    }

    /**
     * @return array<string, mixed>
     */
    public static function definicion(string $tipo): array
    {
        return self::definiciones()[$tipo] ?? abort(404);
    }

    public static function zonaDe(string $tipo): string
    {
        return in_array($tipo, self::tipos(self::INTERIOR), true) ? self::INTERIOR : self::EXTERIOR;
    }

    /**
     * Posición (empezando en 0) de un tipo dentro de su zona.
     */
    public static function indiceDe(string $tipo): int
    {
        return (int) array_search($tipo, self::tipos(self::zonaDe($tipo)), true);
    }

    /**
     * Texto principal de un elemento para mostrarlo en migas, mensajes y confirmaciones.
     */
    public static function titulo(string $tipo, Model $elemento): string
    {
        return match ($tipo) {
            'modulos' => (string) $elemento->nombre_modulo,
            'pasillos' => 'Pasillo '.$elemento->numero_pasillo,
            'estanterias' => 'Estantería '.$elemento->numero_estanteria,
            'etiquetas' => 'Etiqueta '.$elemento->numero_etiqueta,
            'patios' => $elemento->codigo.' · '.$elemento->nombre,
            'sectores' => 'Sector '.$elemento->codigo,
            'filas' => 'Fila '.$elemento->numero_fila,
            'posiciones' => 'Posición '.$elemento->numero_posicion,
            'niveles' => 'Nivel '.$elemento->numero_nivel,
            default => (string) $elemento->getKey(),
        };
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function definiciones(): array
    {
        return [
            'modulos' => [
                'tipo' => 'modulos',
                'clave' => 'modulo',
                'padre_clave' => self::CLAVE_DESGUACE,
                'modelo' => Modulo::class,
                'columna_padre' => 'id_desguace',
                'tabla_padre' => 'desguaces',
                'pk_padre' => 'id_desguace',
                'singular' => 'módulo',
                'plural' => 'Módulos',
                'relacion_hijos' => 'pasillos',
                'etiqueta_hijos' => 'Pasillos',
                'bloqueos' => ['pasillos' => 'pasillos', 'categorias' => 'categorías'],
                'campos' => [
                    'nombre_modulo' => [
                        'etiqueta' => 'Nombre del módulo',
                        'reglas' => ['required', 'string', 'max:255'],
                        'input' => 'text',
                        'maxlength' => 255,
                    ],
                ],
            ],
            'pasillos' => [
                'tipo' => 'pasillos',
                'clave' => 'pasillo',
                'padre_clave' => 'modulo',
                'modelo' => Pasillo::class,
                'columna_padre' => 'id_modulo',
                'tabla_padre' => 'modulos',
                'pk_padre' => 'id_modulo',
                'singular' => 'pasillo',
                'plural' => 'Pasillos',
                'relacion_hijos' => 'estanterias',
                'etiqueta_hijos' => 'Estanterías',
                'bloqueos' => ['estanterias' => 'estanterías'],
                'campos' => [
                    'numero_pasillo' => [
                        'etiqueta' => 'Número de pasillo',
                        'reglas' => ['required', 'string', 'max:50'],
                        'input' => 'text',
                        'maxlength' => 50,
                    ],
                ],
            ],
            'estanterias' => [
                'tipo' => 'estanterias',
                'clave' => 'estanteria',
                'padre_clave' => 'pasillo',
                'modelo' => Estanteria::class,
                'columna_padre' => 'id_pasillo',
                'tabla_padre' => 'pasillos',
                'pk_padre' => 'id_pasillo',
                'singular' => 'estantería',
                'plural' => 'Estanterías',
                'relacion_hijos' => 'etiquetas',
                'etiqueta_hijos' => 'Etiquetas',
                'bloqueos' => ['etiquetas' => 'etiquetas'],
                'campos' => [
                    'numero_estanteria' => [
                        'etiqueta' => 'Número de estantería',
                        'reglas' => ['required', 'string', 'max:50'],
                        'input' => 'text',
                        'maxlength' => 50,
                    ],
                ],
            ],
            'etiquetas' => [
                'tipo' => 'etiquetas',
                'clave' => null,
                'padre_clave' => 'estanteria',
                'modelo' => Etiqueta::class,
                'columna_padre' => 'id_estanteria',
                'tabla_padre' => 'estanterias',
                'pk_padre' => 'id_estanteria',
                'singular' => 'etiqueta',
                'plural' => 'Etiquetas',
                'relacion_hijos' => null,
                'etiqueta_hijos' => null,
                'bloqueos' => [],
                'campos' => [
                    'numero_etiqueta' => [
                        'etiqueta' => 'Número de etiqueta',
                        'reglas' => ['required', 'string', 'max:50'],
                        'input' => 'text',
                        'maxlength' => 50,
                    ],
                ],
            ],
            'patios' => [
                'tipo' => 'patios',
                'clave' => 'patio',
                'padre_clave' => self::CLAVE_DESGUACE,
                'modelo' => Patio::class,
                'columna_padre' => 'id_desguace',
                'tabla_padre' => 'desguaces',
                'pk_padre' => 'id_desguace',
                'singular' => 'patio',
                'plural' => 'Patios',
                'relacion_hijos' => 'sectores',
                'etiqueta_hijos' => 'Sectores',
                'bloqueos' => ['sectores' => 'sectores'],
                'campos' => [
                    'codigo' => [
                        'etiqueta' => 'Código',
                        'reglas' => ['required', 'string', 'max:50'],
                        'input' => 'text',
                        'maxlength' => 50,
                    ],
                    'nombre' => [
                        'etiqueta' => 'Nombre',
                        'reglas' => ['required', 'string', 'max:255'],
                        'input' => 'text',
                        'maxlength' => 255,
                    ],
                ],
            ],
            'sectores' => [
                'tipo' => 'sectores',
                'clave' => 'sector',
                'padre_clave' => 'patio',
                'modelo' => Sector::class,
                'columna_padre' => 'id_patio',
                'tabla_padre' => 'patios',
                'pk_padre' => 'id_patio',
                'singular' => 'sector',
                'plural' => 'Sectores',
                'relacion_hijos' => 'filas',
                'etiqueta_hijos' => 'Filas',
                'bloqueos' => ['filas' => 'filas'],
                'campos' => [
                    'codigo' => [
                        'etiqueta' => 'Código',
                        'reglas' => ['required', 'string', 'max:50'],
                        'input' => 'text',
                        'maxlength' => 50,
                    ],
                    'tipo_zona' => [
                        'etiqueta' => 'Tipo de zona',
                        'reglas' => ['required', 'string', 'max:100'],
                        'input' => 'text',
                        'maxlength' => 100,
                    ],
                ],
            ],
            'filas' => [
                'tipo' => 'filas',
                'clave' => 'fila',
                'padre_clave' => 'sector',
                'modelo' => Fila::class,
                'columna_padre' => 'id_sector',
                'tabla_padre' => 'sectores',
                'pk_padre' => 'id_sector',
                'singular' => 'fila',
                'plural' => 'Filas',
                'relacion_hijos' => 'posiciones',
                'etiqueta_hijos' => 'Posiciones',
                'bloqueos' => ['posiciones' => 'posiciones'],
                'campos' => [
                    'numero_fila' => [
                        'etiqueta' => 'Número de fila',
                        'reglas' => ['required', 'string', 'max:50'],
                        'input' => 'text',
                        'maxlength' => 50,
                    ],
                ],
            ],
            'posiciones' => [
                'tipo' => 'posiciones',
                'clave' => 'posicion',
                'padre_clave' => 'fila',
                'modelo' => Posicion::class,
                'columna_padre' => 'id_fila',
                'tabla_padre' => 'filas',
                'pk_padre' => 'id_fila',
                'singular' => 'posición',
                'plural' => 'Posiciones',
                'relacion_hijos' => 'niveles',
                'etiqueta_hijos' => 'Niveles',
                'bloqueos' => ['niveles' => 'niveles'],
                'campos' => [
                    'numero_posicion' => [
                        'etiqueta' => 'Número de posición',
                        'reglas' => ['required', 'string', 'max:50'],
                        'input' => 'text',
                        'maxlength' => 50,
                    ],
                    'capacidad_maxima' => [
                        'etiqueta' => 'Capacidad máxima',
                        'reglas' => ['required', 'integer', 'min:1'],
                        'input' => 'number',
                        'min' => 1,
                    ],
                ],
            ],
            'niveles' => [
                'tipo' => 'niveles',
                'clave' => null,
                'padre_clave' => 'posicion',
                'modelo' => Nivel::class,
                'columna_padre' => 'id_posicion',
                'tabla_padre' => 'posiciones',
                'pk_padre' => 'id_posicion',
                'singular' => 'nivel',
                'plural' => 'Niveles',
                'relacion_hijos' => null,
                'etiqueta_hijos' => null,
                'bloqueos' => [],
                'campos' => [
                    'numero_nivel' => [
                        'etiqueta' => 'Número de nivel',
                        'reglas' => ['required', 'string', 'max:50'],
                        'input' => 'text',
                        'maxlength' => 50,
                    ],
                ],
            ],
        ];
    }
}
