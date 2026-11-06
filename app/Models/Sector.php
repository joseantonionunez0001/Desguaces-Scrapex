<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sector extends Model
{
    use HasFactory;

    protected $table = 'sectores';
    protected $primaryKey = 'id';
    protected $fillable = [
        'patio_id',
        'codigo',
        'tipo_zona'
    ];

    // Un sector pertenece a un patio (N:1)
    public function patio(): BelongsTo
    {
        return $this->belongsTo(Patio::class);
    }
}
