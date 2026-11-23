<?php

namespace App\Models;

use App\Models\Concerns\RestringidoPorDesguace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class UbicacionVehiculo extends Model
{
    use HasFactory, RestringidoPorDesguace, SoftDeletes;

    protected $table = 'ubicaciones_vehiculo';

    protected $primaryKey = 'id_ubicacion';

    protected $fillable = [
        'id_vehiculo',
        'id_nivel',
        'fecha_inicio',
        'fecha_fin',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
        ];
    }

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class, 'id_vehiculo', 'id_vehiculo');
    }

    public function nivel(): BelongsTo
    {
        return $this->belongsTo(Nivel::class, 'id_nivel', 'id_nivel');
    }

    // Alcance por desguace: usa las relaciones reales del modelo.
    public function scopeDelDesguace(Builder $consulta, int $idDesguace): void
    {
        $this->enSede($consulta, 'vehiculo', $idDesguace);
    }
}
