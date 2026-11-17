<?php

namespace App\Services;

use App\Models\Desguace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * NavegaciÃ³n y comprobaciones comunes a las jerarquÃ­as del almacÃ©n (interior y exterior).
 *
 * El "camino" es el conjunto de elementos elegidos hasta el nivel mostrado, expresado como
 * ['desguace' => 1, 'modulo' => 4, â€¦] (las mismas claves que se usan en la URL).
 */
class AlmacenJerarquia
{
    public function __construct(private readonly OcupacionAlmacen $ocupacion) {}

    /**
     * Resuelve el camino pedido en la URL. Aborta con 404 si algÃºn elemento no existe, estÃ¡
     * eliminado o no pertenece al elemento anterior del camino.
     *
     * @param  array<string, mixed>  $consulta
     * @return array{desguace: ?Desguace, seleccion: list<array{definicion: array<string, mixed>, elemento: Model}>, tipoListado: ?array<string, mixed>, padreId: ?int}
     */
    public function resolverCamino(string $zona, array $consulta): array
    {
        $niveles = array_values(EstructuraAlmacen::niveles($zona));
        $claves = $this->clavesNavegables($niveles);

        // Una clave mÃ¡s profunda sin las anteriores es una URL inconsistente.
        $primeraAusente = null;
        foreach ([EstructuraAlmacen::CLAVE_DESGUACE, ...$claves] as $clave) {
            if (! $this->hayValor($consulta, $clave)) {
                $primeraAusente ??= $clave;
            } elseif ($primeraAusente !== null) {
                abort(404);
            }
        }

        if (! $this->hayValor($consulta, EstructuraAlmacen::CLAVE_DESGUACE)) {
            return ['desguace' => null, 'seleccion' => [], 'tipoListado' => null, 'padreId' => null];
        }

        $desguace = Desguace::findOrFail($this->entero($consulta[EstructuraAlmacen::CLAVE_DESGUACE]));
        $seleccion = [];
        $padreId = (int) $desguace->getKey();

        foreach ($niveles as $definicion) {
            $clave = $definicion['clave'];

            if ($clave === null || ! $this->hayValor($consulta, $clave)) {
                return [
                    'desguace' => $desguace,
                    'seleccion' => $seleccion,
                    'tipoListado' => $definicion,
                    'padreId' => $padreId,
                ];
            }

            $elemento = $definicion['modelo']::query()
                ->where($definicion['columna_padre'], $padreId)
                ->findOrFail($this->entero($consulta[$clave]));

            $seleccion[] = ['definicion' => $definicion, 'elemento' => $elemento];
            $padreId = (int) $elemento->getKey();
        }

        // Todas las claves abribles estÃ¡n elegidas: lo que se lista es el Ãºltimo nivel.
        $ultimo = end($niveles);

        return [
            'desguace' => $desguace,
            'seleccion' => $seleccion,
            'tipoListado' => $ultimo ?: null,
            'padreId' => $padreId,
        ];
    }

    /**
     * Elementos del nivel mostrado, con el contador de hijos, paginados.
     *
     * @param  array<string, mixed>  $definicion
     */
    public function listar(array $definicion, int $padreId): LengthAwarePaginator
    {
        $consulta = $definicion['modelo']::query()->where($definicion['columna_padre'], $padreId);

        if ($definicion['relacion_hijos'] !== null) {
            $consulta->withCount($definicion['relacion_hijos']);
        }

        return $consulta
            ->orderBy((new $definicion['modelo'])->getKeyName())
            ->paginate(15)
            ->withQueryString();
    }

    /**
     * Camino de ancestros de un elemento a partir del id de su padre, desde el padre hasta el desguace.
     * Devuelve null si algÃºn ancestro no existe o estÃ¡ eliminado lÃ³gicamente.
     *
     * @return array<string, int>|null
     */
    public function caminoDesdePadre(string $tipo, int $padreId): ?array
    {
        $zona = EstructuraAlmacen::zonaDe($tipo);
        $niveles = array_values(EstructuraAlmacen::niveles($zona));
        $indice = EstructuraAlmacen::indiceDe($tipo);

        $camino = [];
        $idActual = $padreId;

        // Se asciende desde el padre del elemento hasta el desguace.
        for ($posicion = $indice - 1; $posicion >= 0; $posicion--) {
            $definicion = $niveles[$posicion];
            $ancestro = $definicion['modelo']::query()->find($idActual);

            if ($ancestro === null) {
                return null;
            }

            $camino[$definicion['clave']] = (int) $ancestro->getKey();
            $idActual = (int) $ancestro->{$definicion['columna_padre']};
        }

        if (Desguace::query()->whereKey($idActual)->doesntExist()) {
            return null;
        }

        $camino[EstructuraAlmacen::CLAVE_DESGUACE] = $idActual;

        // Se devuelve en orden de navegaciÃ³n: desguace primero.
        return array_reverse($camino, true);
    }

    /**
     * Indica si el padre elegido para un elemento nuevo o editado pertenece al camino mostrado.
     *
     * @param  array<string, mixed>  $ruta
     */
    public function padreEnCamino(string $tipo, int $padreId, array $ruta): bool
    {
        $camino = $this->caminoDesdePadre($tipo, $padreId);

        if ($camino === null) {
            return false;
        }

        foreach ($camino as $clave => $id) {
            if (! isset($ruta[$clave]) || (int) $ruta[$clave] !== $id) {
                return false;
            }
        }

        return true;
    }

    /**
     * Motivo por el que un elemento no se puede eliminar (null si se puede).
     *
     * La API elimina sin comprobar dependientes; en la web se bloquea el borrado cuando quedarÃ­an
     * elementos activos sin padre visible (huÃ©rfanos).
     *
     * @param  array<string, mixed>  $definicion
     */
    public function motivoBloqueoEliminacion(array $definicion, Model $elemento): ?string
    {
        foreach ($definicion['bloqueos'] as $relacion => $nombre) {
            $total = $elemento->{$relacion}()->count();

            if ($total > 0) {
                return $this->mensajeBloqueo($definicion, $elemento, "{$total} {$nombre}");
            }
        }

        if ($definicion['tipo'] === 'niveles') {
            $vigentes = $this->ocupacion->contarVigentesEnNivel((int) $elemento->getKey());

            if ($vigentes > 0) {
                return $this->mensajeBloqueo($definicion, $elemento, "{$vigentes} ubicaciÃ³n(es) de vehÃ­culos vigente(s)");
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $definicion
     */
    private function mensajeBloqueo(array $definicion, Model $elemento, string $detalle): string
    {
        $titulo = EstructuraAlmacen::titulo($definicion['tipo'], $elemento);

        return "No se puede eliminar el {$definicion['singular']} Â«{$titulo}Â» porque tiene {$detalle}. Elimina o traslada antes esos elementos.";
    }

    /**
     * @param  list<array<string, mixed>>  $niveles
     * @return list<string>
     */
    private function clavesNavegables(array $niveles): array
    {
        return array_values(array_filter(array_column($niveles, 'clave')));
    }

    /**
     * @param  array<string, mixed>  $consulta
     */
    private function hayValor(array $consulta, string $clave): bool
    {
        return isset($consulta[$clave]) && $consulta[$clave] !== '';
    }

    private function entero(mixed $valor): int
    {
        if (! is_scalar($valor) || ! ctype_digit((string) $valor)) {
            abort(404);
        }

        return (int) $valor;
    }
}
