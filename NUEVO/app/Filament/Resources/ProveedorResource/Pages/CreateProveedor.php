<?php

namespace App\Filament\Resources\ProveedorResource\Pages;

use App\Filament\Resources\ProveedorResource;
use App\Services\ProveedorSyncService;
use App\Services\UafeService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateProveedor extends CreateRecord
{
    protected static string $resource = ProveedorResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $record = static::getModel()::create($data);

            $lineasNegocioIds = $this->data['lineasNegocio'] ?? [];
            $record->lineasNegocio()->attach($lineasNegocioIds);

            app(UafeService::class)->gestionarEstadoYDocumentos($record, $this->data);

            try {
                ProveedorSyncService::sincronizar($record, $this->data);
                $record->update(['uafe_sync_pendiente' => false]);
            } catch (\Throwable $e) {
                $record->update(['uafe_sync_pendiente' => true]);

                Notification::make()
                    ->title('Proveedor guardado localmente. SAE quedó pendiente de sincronizar.')
                    ->body($e->getMessage())
                    ->warning()
                    ->send();
            }

            return $record;
        });
    }
}
