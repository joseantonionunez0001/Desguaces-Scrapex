<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Modulo extends Model
{
    use HasFactory;

    protected $table = 'modulos';
    protected $primaryKey = 'id';
    protected $fillable = [
        'nombre_modulo',
        'desguace_id'
    ];

    // Un módulo pertenece a un desguace (N:1)
    public function desguace(): BelongsTo
    {
        return $this->belongsTo(Desguace::class);
    }
}
