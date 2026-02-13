<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProveedorUafeHistorial extends Model
{
    use HasFactory;

    protected $table = 'proveedor_uafe_historials';

    protected $fillable = [
        'proveedor_id',
        'accion',
        'estado_anterior',
        'estado_nuevo',
        'detalle',
        'correo_destino',
        'enviado_en',
        'usuario_id',
    ];

    protected $casts = [
        'enviado_en' => 'datetime',
    ];
}
