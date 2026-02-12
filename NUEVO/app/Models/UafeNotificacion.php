<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UafeNotificacion extends Model
{
    use HasFactory;

    protected $table = 'uafe_notificaciones';

    protected $fillable = [
        'proveedor_id',
        'enviado_a',
        'asunto',
        'plantilla_id',
        'fecha_envio',
        'estado_envio',
        'error',
        'adjuntos',
    ];

    protected $casts = [
        'fecha_envio' => 'datetime',
        'adjuntos' => 'array',
    ];
}
