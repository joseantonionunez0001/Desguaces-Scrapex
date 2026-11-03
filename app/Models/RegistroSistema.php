<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegistroSistema extends Model
{
    use HasFactory;

    protected $table = 'registros_sistema';
    protected $primaryKey = 'id';

    protected $fillable = [
        'fecha_hora'
    ];
}
