<?php

namespace App\Filament\Resources\ProveedorResource\Pages;

use App\Filament\Resources\ProveedorResource;
use App\Services\ProveedorSyncService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class CreateProveedor extends CreateRecord
{
    protected static string $resource = ProveedorResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $data['uafe_estado'] = $data['uafe_estado'] ?? ProveedorResource::UAFE_ESTADO_NO_APROBADO;

            // 1. Create the local record
            $record = static::getModel()::create($data);

            // 2. Attach related data (lineasNegocio)
            $lineasNegocioIds = $this->data['lineasNegocio'] ?? [];
            $record->lineasNegocio()->attach($lineasNegocioIds);

            ProveedorSyncService::sincronizar($record, $this->data);

            $record->uafeHistoriales()->create([
                'accion' => 'REGISTRO_INICIAL',
                'estado_anterior' => null,
                'estado_nuevo' => $record->uafe_estado,
                'detalle' => 'Proveedor registrado con estado inicial UAFE.',
                'usuario_id' => auth()->id(),
            ]);

            if (!empty($record->correo)) {
                Mail::raw(
                    'Estimado proveedor, por favor enviar la documentación requerida para validación UAFE.',
                    fn ($message) => $message->to($record->correo)->subject('Solicitud de documentación UAFE')
                );

                $record->uafeHistoriales()->create([
                    'accion' => 'ENVIO_CORREO',
                    'estado_anterior' => $record->uafe_estado,
                    'estado_nuevo' => $record->uafe_estado,
                    'detalle' => 'Envío automático de solicitud de documentación UAFE al registrar proveedor.',
                    'correo_destino' => $record->correo,
                    'enviado_en' => now(),
                    'usuario_id' => auth()->id(),
                ]);
            } else {
                Notification::make()
                    ->title('Proveedor creado sin correo para solicitud UAFE automática.')
                    ->warning()
                    ->send();
            }

            return $record;
        });
    }
}