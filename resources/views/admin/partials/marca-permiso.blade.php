{{-- Marca accesible de la matriz de permisos: ✓ permitido / — no permitido, con texto para lectores de pantalla. --}}
@if ($permitido)
    <span class="font-bold text-emerald-600" title="Permitido"><span aria-hidden="true">✓</span><span class="sr-only">Permitido: {{ $descripcion }}</span></span>
@else
    <span class="text-slate-400" title="No permitido"><span aria-hidden="true">—</span><span class="sr-only">No permitido: {{ $descripcion }}</span></span>
@endif
