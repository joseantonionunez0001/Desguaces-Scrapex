<?php

namespace App\Models;

use App\Models\Concerns\RestringidoPorDesguace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistroSistema extends Model
{
    use HasFactory, RestringidoPorDesguace;

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

    // Alcance por desguace: los registros del personal de la sede (a traves del usuario autor).
    public function scopeDelDesguace(Builder $consulta, int $idDesguace): void
    {
        $consulta->whereHas('usuario', fn (Builder $usuario) => $usuario->withTrashed()->delDesguace($idDesguace));
    }
}
