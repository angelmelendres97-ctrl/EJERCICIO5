<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UafeConfiguracion extends Model
{
    use HasFactory;

    protected $table = 'uafe_configuraciones';

    protected $fillable = [
        'nombre',
        'activo',
        'plantilla_asunto',
        'plantilla_cuerpo',
        'adjuntos_fijos',
        'smtp_host',
        'smtp_puerto',
        'smtp_usuario',
        'smtp_password',
        'smtp_cifrado',
        'smtp_from_name',
        'smtp_from_email',
        'smtp_timeout',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'adjuntos_fijos' => 'array',
    ];
}
