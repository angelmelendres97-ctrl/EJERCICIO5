<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            $table->string('uafe_estado', 30)
                ->default('NO_APROBADO')
                ->after('correo');
            $table->text('uafe_observacion')->nullable()->after('uafe_estado');
            $table->string('uafe_documento_path')->nullable()->after('uafe_observacion');
            $table->timestamp('uafe_fecha_validacion')->nullable()->after('uafe_documento_path');
        });
    }

    public function down(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            $table->dropColumn([
                'uafe_estado',
                'uafe_observacion',
                'uafe_documento_path',
                'uafe_fecha_validacion',
            ]);
        });
    }
};
