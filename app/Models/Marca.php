<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Marca de vehículo del catálogo común (no pertenece a ningún desguace).
 */
class Marca extends Model
{
    use HasFactory;

    protected $table = 'marcas';

    protected $primaryKey = 'id_marca';

    protected $fillable = ['nombre'];

    public function modelos(): HasMany
    {
        return $this->hasMany(ModeloVehiculo::class, 'id_marca', 'id_marca')->orderBy('nombre');
    }

    /**
     * Añade al catálogo el par marca/modelo si todavía no existe (se usa al registrar vehículos).
     */
    public static function registrarModelo(?string $marca, ?string $modelo): ?ModeloVehiculo
    {
        $marca = trim((string) $marca);
        $modelo = trim((string) $modelo);

        if ($marca === '' || $modelo === '') {
            return null;
        }

        $registro = static::query()->firstOrCreate(['nombre' => $marca]);

        return $registro->modelos()->firstOrCreate(['nombre' => $modelo]);
    }
}
