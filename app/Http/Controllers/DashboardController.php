<?php

namespace App\Http\Controllers;

use App\Models\Desguace;
use App\Models\Movimiento;
use App\Models\Pieza;
use App\Models\User;
use App\Models\Vehiculo;
use App\Support\Acceso;
use App\Support\Navegacion;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    // GET /dashboard
    public function __invoke(Request $request): View
    {
        $usuario = $request->user();
        $roles = $usuario->getRoleNames();
        $sinRol = $roles->isEmpty();
        // Rol de desguace sin sede válida: acceso bloqueado y aviso para contactar con administración.
        $sinDesguace = Acceso::sinDesguaceAsignado($usuario);
        $bloqueado = $sinRol || $sinDesguace;
        $idDesguace = Acceso::desguaceId($usuario);

        return view('dashboard', [
            'usuario' => $usuario,
            'roles' => $roles,
            'sinRol' => $sinRol,
            'sinDesguace' => $sinDesguace,
            'avisoSinDesguace' => Acceso::AVISO_SIN_DESGUACE,
            'desguace' => $idDesguace !== null ? Desguace::query()->find($idDesguace) : null,
            // Un usuario sin rol o sin sede no ve módulos ni indicadores internos.
            'indicadores' => $bloqueado ? [] : $this->indicadores($usuario),
            'accesos' => $bloqueado ? [] : $this->accesosRapidos($usuario),
        ]);
    }

    /**
     * Indicadores calculados con datos reales. Cada bloque solo se calcula
     * si el usuario tiene permiso para consultar ese recurso.
     *
     * @return array<string, mixed>
     */
    private function indicadores(User $usuario): array
    {
        $indicadores = [];

        if ($usuario->can('vehiculos.ver')) {
            $indicadores['vehiculos'] = [
                'total' => Vehiculo::count(),
                'ultimos_30_dias' => Vehiculo::where('fecha_llegada', '>=', now()->subDays(30)->toDateString())->count(),
            ];
        }

        if ($usuario->can('piezas.ver')) {
            $indicadores['piezas'] = [
                'total' => Pieza::count(),
                'unidades' => (int) Pieza::sum('stock'),
                'stock_bajo' => Pieza::whereColumn('stock', '<=', 'stock_minimo')->count(),
                'valor' => (float) (Pieza::selectRaw('COALESCE(SUM(precio * stock), 0) as valor')->value('valor') ?? 0),
            ];
        }

        if ($usuario->can('movimientos.ver')) {
            $indicadores['movimientos'] = [
                'total' => Movimiento::count(),
                'ultimos_30_dias' => Movimiento::where('fecha', '>=', now()->subDays(30)->toDateString())->count(),
                'por_tipo' => Movimiento::selectRaw('tipo_movimiento, COUNT(*) as total, SUM(cantidad) as unidades')
                    ->groupBy('tipo_movimiento')
                    ->orderBy('tipo_movimiento')
                    ->get(),
                'recientes' => Movimiento::with('categoria')->orderByDesc('fecha')->orderByDesc('id_movimiento')->limit(5)->get(),
            ];
        }

        return $indicadores;
    }

    /**
     * Accesos rápidos: los mismos módulos del menú lateral, sin el propio dashboard.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function accesosRapidos(User $usuario): Collection
    {
        return collect(Navegacion::para($usuario))
            ->flatten(1)
            ->reject(fn (array $elemento) => $elemento['route'] === 'dashboard')
            ->values();
    }
}
