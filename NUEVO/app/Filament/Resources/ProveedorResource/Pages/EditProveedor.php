<?php

namespace App\Filament\Resources\ProveedorResource\Pages;

use App\Filament\Resources\ProveedorResource;
use App\Services\ProveedorSyncService;
use App\Services\UafeService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EditProveedor extends EditRecord
{
    protected static string $resource = ProveedorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn() => auth()->user()->can('Borrar')),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data) {
            $record->update($data);

            $lineasNegocioIds = $this->data['lineasNegocio'] ?? [];
            $record->lineasNegocio()->sync($lineasNegocioIds);

            app(UafeService::class)->gestionarEstadoYDocumentos($record, $this->data);

            try {
                ProveedorSyncService::sincronizar($record, $this->data);
                $record->update(['uafe_sync_pendiente' => false]);
            } catch (\Throwable $e) {
                $record->update(['uafe_sync_pendiente' => true]);

                Notification::make()
                    ->title('Cambios guardados localmente. SAE quedó pendiente de sincronizar.')
                    ->body($e->getMessage())
                    ->warning()
                    ->send();
            }

            return $record;
        });
    }
}
