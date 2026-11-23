<?php

namespace App\Models;

use App\Models\Concerns\RestringidoPorDesguace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Nivel extends Model
{
    use HasFactory, RestringidoPorDesguace, SoftDeletes;

    protected $table = 'niveles';

    protected $primaryKey = 'id_nivel';

    protected $fillable = [
        'id_posicion',
        'numero_nivel',
    ];

    public function posicion(): BelongsTo
    {
        return $this->belongsTo(Posicion::class, 'id_posicion', 'id_posicion');
    }

    public function ubicaciones(): HasMany
    {
        return $this->hasMany(UbicacionVehiculo::class, 'id_nivel');
    }

    // Alcance por desguace: usa las relaciones reales del modelo.
    public function scopeDelDesguace(Builder $consulta, int $idDesguace): void
    {
        $this->enSede($consulta, 'posicion', $idDesguace);
    }
}
