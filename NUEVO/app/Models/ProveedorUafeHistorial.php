<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedores::class, 'proveedor_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
