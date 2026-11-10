<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pieza extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'piezas';
    protected $primaryKey = 'id_pieza';

    protected $fillable = [
        'nombre',
        'precio',
        'estado',
        'stock',
        'stock_minimo',
        'id_usuario',
        'id_tipo_pieza',
    ];

    protected function casts(): array
    {
        return [
            'precio' => 'decimal:2',
            'stock' => 'integer',
            'stock_minimo' => 'integer',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id');
    }

    public function tipoPieza(): BelongsTo
    {
        return $this->belongsTo(TipoPieza::class, 'id_tipo_pieza', 'id_pieza');
    }
}
