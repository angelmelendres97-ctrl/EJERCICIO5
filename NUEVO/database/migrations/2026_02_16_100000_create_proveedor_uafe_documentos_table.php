<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proveedor_uafe_documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proveedor_id')->constrained('proveedores')->cascadeOnDelete();
            $table->string('archivo_ruta');
            $table->string('nombre_original');
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('tamano')->nullable();
            $table->string('tipo_documento')->nullable();
            $table->string('estado_documento')->nullable();
            $table->foreignId('subido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proveedor_uafe_documentos');
    }
};
