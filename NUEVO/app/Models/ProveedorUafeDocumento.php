<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProveedorUafeDocumento extends Model
{
    use HasFactory;

    protected $table = 'proveedor_uafe_documentos';

    protected $fillable = [
        'proveedor_id',
        'archivo_ruta',
        'nombre_original',
        'mime',
        'tamano',
        'tipo_documento',
        'estado_documento',
        'subido_por',
    ];

    public function proveedor()
    {
        return $this->belongsTo(Proveedores::class, 'proveedor_id');
    }
}
