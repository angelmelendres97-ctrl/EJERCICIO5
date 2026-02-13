<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uafe_notificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proveedor_id')->constrained('proveedores')->cascadeOnDelete();
            $table->string('enviado_a');
            $table->string('asunto');
            $table->foreignId('plantilla_id')->nullable()->constrained('uafe_configuraciones')->nullOnDelete();
            $table->timestamp('fecha_envio')->nullable();
            $table->string('estado_envio', 20)->default('PENDIENTE');
            $table->text('error')->nullable();
            $table->json('adjuntos')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uafe_notificaciones');
    }
};
