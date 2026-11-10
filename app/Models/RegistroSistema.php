<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistroSistema extends Model
{
    use HasFactory;

    protected $table = 'registro_sistema';
    protected $primaryKey = 'id_registro';

    /**
     * Registro de auditoría: sin timestamps ni softDeletes.
     */
    public $timestamps = false;

    protected $fillable = [
        'fecha_hora',
        'id_usuario',
    ];

    protected function casts(): array
    {
        return [
            'fecha_hora' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id');
    }
}
