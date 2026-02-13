<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uafe_configuraciones', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->default('Configuración UAFE Principal');
            $table->boolean('activo')->default(true);

            $table->string('plantilla_asunto')->nullable();
            $table->longText('plantilla_cuerpo')->nullable();
            $table->json('adjuntos_fijos')->nullable();

            $table->string('smtp_host')->nullable();
            $table->unsignedInteger('smtp_puerto')->nullable();
            $table->string('smtp_usuario')->nullable();
            $table->text('smtp_password')->nullable();
            $table->string('smtp_cifrado', 20)->nullable();
            $table->string('smtp_from_name')->nullable();
            $table->string('smtp_from_email')->nullable();
            $table->unsignedInteger('smtp_timeout')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uafe_configuraciones');
    }
};
