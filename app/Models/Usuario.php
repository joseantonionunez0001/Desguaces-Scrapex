<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Usuario extends Model
{
    use HasFactory;

    protected $table = 'usuarios';

    protected $primaryKey = 'id';

    protected $fillable = [
        'desguace_id',
        'nombre',
        'correo',
        'contrasena',
    ];

    // La contraseña nunca se devuelve en las respuestas de la API
    protected $hidden = [
        'contrasena',
    ];

    // Se cifra automáticamente al guardarla
    protected function casts(): array
    {
        return [
            'contrasena' => 'hashed',
        ];
    }

    // Un usuario pertenece a un desguace (N:1)
    public function desguace(): BelongsTo
    {
        return $this->belongsTo(Desguace::class);
    }
}
