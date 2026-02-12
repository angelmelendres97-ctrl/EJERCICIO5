<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            $table->string('uafe_estado', 30)->default('NO_APROBADO')->after('anulada');
            $table->timestamp('uafe_fecha_validacion')->nullable()->after('uafe_estado');
            $table->text('uafe_observacion')->nullable()->after('uafe_fecha_validacion');
            $table->boolean('uafe_sync_pendiente')->default(false)->after('uafe_observacion');
        });
    }

    public function down(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            $table->dropColumn([
                'uafe_estado',
                'uafe_fecha_validacion',
                'uafe_observacion',
                'uafe_sync_pendiente',
            ]);
        });
    }
};
