<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\FiltrarRegistrosRequest;
use App\Models\RegistroSistema;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

/**
 * Pantalla 21: consulta de los registros del sistema (solo lectura).
 *
 * La tabla registro_sistema solo guarda el usuario y la fecha/hora: no hay acción ni detalle.
 */
class RegistroSistemaController extends Controller
{
    public function index(FiltrarRegistrosRequest $request): View
    {
        $filtros = $request->validated();

        $registros = RegistroSistema::query()
            ->with(['usuario' => fn ($consulta) => $consulta->withTrashed()])
            ->when(filled($filtros['usuario'] ?? null), fn (Builder $consulta) => $consulta->where('id_usuario', $filtros['usuario']))
            ->when(filled($filtros['desde'] ?? null), fn (Builder $consulta) => $consulta->whereDate('fecha_hora', '>=', $filtros['desde']))
            ->when(filled($filtros['hasta'] ?? null), fn (Builder $consulta) => $consulta->whereDate('fecha_hora', '<=', $filtros['hasta']))
            ->when(filled($filtros['q'] ?? null), function (Builder $consulta) use ($filtros): void {
                $texto = '%'.trim($filtros['q']).'%';

                $consulta->whereHas('usuario', fn ($usuarios) => $usuarios
                    ->withTrashed()
                    ->where(fn ($grupo) => $grupo->where('name', 'like', $texto)->orWhere('email', 'like', $texto)));
            })
            ->orderByDesc('fecha_hora')
            ->orderByDesc('id_registro')
            ->paginate(15)
            ->withQueryString();

        // Solo se ofrecen los usuarios que tienen registros (incluidos los eliminados lógicamente).
        $usuarios = User::query()
            ->withTrashed()
            ->whereHas('registros')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (User $usuario): array => [
                $usuario->id => $usuario->name.' ('.$usuario->email.')'.($usuario->trashed() ? ' · eliminado' : ''),
            ]);

        return view('admin.registros', [
            'registros' => $registros,
            'usuarios' => $usuarios,
        ]);
    }
}
