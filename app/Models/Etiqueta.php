<?php

namespace App\Models;

use App\Models\Concerns\RestringidoPorDesguace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Etiqueta extends Model
{
    use HasFactory, RestringidoPorDesguace, SoftDeletes;

    protected $table = 'etiquetas';

    protected $primaryKey = 'id_etiqueta';

    protected $fillable = [
        'id_estanteria',
        'numero_etiqueta',
    ];

    public function estanteria(): BelongsTo
    {
        return $this->belongsTo(Estanteria::class, 'id_estanteria', 'id_estanteria');
    }

    /**
     * Relaciones necesarias para mostrar el hueco completo (incluidos los padres archivados).
     *
     * @return array<string, \Closure>
     */
    public static function relacionesDeRuta(string $prefijo = ''): array
    {
        $conArchivados = fn ($consulta) => $consulta->withTrashed();

        return [
            $prefijo.'estanteria' => $conArchivados,
            $prefijo.'estanteria.pasillo' => $conArchivados,
            $prefijo.'estanteria.pasillo.modulo' => $conArchivados,
        ];
    }

    /**
     * Hueco completo: «Módulo · Pasillo · Estantería · Etiqueta». Reutiliza la jerarquía del almacén interior.
     */
    public function rutaCompleta(): string
    {
        $estanteria = $this->estanteria;
        $pasillo = $estanteria?->pasillo;
        $modulo = $pasillo?->modulo;

        return collect([
            $modulo ? $modulo->nombre_modulo : null,
            $pasillo ? 'Pasillo '.$pasillo->numero_pasillo : null,
            $estanteria ? 'Estantería '.$estanteria->numero_estanteria : null,
            'Etiqueta '.$this->numero_etiqueta,
        ])->filter()->implode(' · ');
    }

    // Alcance por desguace: usa las relaciones reales del modelo.
    public function scopeDelDesguace(Builder $consulta, int $idDesguace): void
    {
        $this->enSede($consulta, 'estanteria', $idDesguace);
    }
}
