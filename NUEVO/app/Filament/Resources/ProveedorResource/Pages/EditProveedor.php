<?php

namespace App\Filament\Resources\ProveedorResource\Pages;

use App\Filament\Resources\ProveedorResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Services\ProveedorSyncService;

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
            $estadoAnterior = $record->uafe_estado;

            // 1. Actualizar el registro local
            $record->update($data);

            // 2. Sincronizar datos relacionados (lineasNegocio)
            $lineasNegocioIds = $this->data['lineasNegocio'] ?? [];
            $record->lineasNegocio()->sync($lineasNegocioIds);

            ProveedorSyncService::sincronizar($record, $this->data);

            if ($estadoAnterior !== $record->uafe_estado) {
                $record->uafeHistoriales()->create([
                    'accion' => 'CAMBIO_ESTADO',
                    'estado_anterior' => $estadoAnterior,
                    'estado_nuevo' => $record->uafe_estado,
                    'detalle' => 'Actualización manual del estado de aprobación UAFE.',
                    'usuario_id' => auth()->id(),
                ]);
            }

            return $record;
        });
    }
}
