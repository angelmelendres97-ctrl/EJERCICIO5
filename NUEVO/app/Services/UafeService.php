<?php

namespace App\Services;

use App\Mail\UafeSolicitudDocumentosMail;
use App\Models\ProveedorUafeDocumento;
use App\Models\ProveedorUafeHistorial;
use App\Models\Proveedores;
use App\Models\UafeConfiguracion;
use App\Models\UafeNotificacion;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class UafeService
{
    public function gestionarEstadoYDocumentos(Proveedores $proveedor, array $data): void
    {
        $estadoAnterior = $proveedor->getOriginal('uafe_estado') ?? $proveedor->uafe_estado;
        $estadoNuevo = $data['uafe_estado'] ?? $proveedor->uafe_estado ?? 'APROBADO_PARCIAL';

        $updateData = [
            'uafe_estado' => $estadoNuevo,
            'uafe_observacion' => $data['uafe_observacion'] ?? $proveedor->uafe_observacion,
        ];

        if ($estadoNuevo === 'APROBADO') {
            $updateData['uafe_fecha_validacion'] = $data['uafe_fecha_validacion'] ?? now();
        } elseif ($estadoAnterior !== $estadoNuevo) {
            $updateData['uafe_fecha_validacion'] = null;
        }

        $proveedor->update($updateData);

        $this->guardarDocumentos($proveedor, $data['uafe_documentos_upload'] ?? []);

        if ($estadoAnterior !== $estadoNuevo) {
            $this->registrarHistorial($proveedor, 'CAMBIO_ESTADO', $estadoAnterior, $estadoNuevo, $data['uafe_observacion'] ?? null);
        }
    }



    public function mapearEstadoSae(?string $estadoUafe): string
    {
        return $estadoUafe === 'NO_APROBADO' ? 'P' : 'A';
    }

    public function guardarDocumentos(Proveedores $proveedor, array $files): void
    {
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $path = $file->store('uafe/proveedores/' . $proveedor->id, 'public');
                $originalName = $file->getClientOriginalName();
                $mime = $file->getClientMimeType();
                $size = $file->getSize();
            } elseif (is_string($file) && Storage::disk('public')->exists($file)) {
                $path = $file;
                $originalName = basename($file);
                $mime = Storage::disk('public')->mimeType($file);
                $size = Storage::disk('public')->size($file);
            } else {
                continue;
            }

            ProveedorUafeDocumento::create([
                'proveedor_id' => $proveedor->id,
                'archivo_ruta' => $path,
                'nombre_original' => $originalName,
                'mime' => $mime,
                'tamano' => $size,
                'subido_por' => Auth::id(),
                'estado_documento' => 'SUBIDO',
            ]);

            $this->registrarHistorial($proveedor, 'SUBIDA_DOCUMENTO', null, null, $originalName);
        }
    }

    public function enviarCorreoSolicitudDocumentos(Proveedores $proveedor): void
    {
        $config = UafeConfiguracion::query()->where('activo', true)->latest('id')->first();
        if (!$config) {
            throw new \RuntimeException('No existe una configuración UAFE activa.');
        }

        $placeholders = [
            '{{proveedor.nombre}}' => $proveedor->nombre,
            '{{proveedor.ruc}}' => $proveedor->ruc,
            '{{empresa.nombre}}' => $proveedor->empresa?->nombre_empresa ?? '',
            '{{link_carga_documentos}}' => url('/admin/proveedors/' . $proveedor->id . '/edit'),
        ];

        $subject = strtr((string) $config->plantilla_asunto, $placeholders);
        $body = strtr((string) $config->plantilla_cuerpo, $placeholders);
        $fixedAttachments = $config->adjuntos_fijos ?? [];

        $notificationLog = UafeNotificacion::create([
            'proveedor_id' => $proveedor->id,
            'enviado_a' => $proveedor->correo,
            'asunto' => $subject,
            'plantilla_id' => $config->id,
            'estado_envio' => 'PENDIENTE',
            'adjuntos' => $fixedAttachments,
        ]);

        $this->aplicarSmtpRuntime($config);

        try {
            Mail::mailer('smtp')->to($proveedor->correo)
                ->send(new UafeSolicitudDocumentosMail($proveedor, $subject, $body, $fixedAttachments));

            $notificationLog->update([
                'fecha_envio' => now(),
                'estado_envio' => 'ENVIADO',
            ]);

            $this->registrarHistorial($proveedor, 'REENVIO_CORREO', $proveedor->uafe_estado, $proveedor->uafe_estado, 'Correo reenviado al proveedor.', $proveedor->correo);
        } catch (\Throwable $e) {
            $notificationLog->update([
                'fecha_envio' => now(),
                'estado_envio' => 'ERROR',
                'error' => $e->getMessage(),
            ]);

            $this->registrarHistorial($proveedor, 'ERROR_CORREO', $proveedor->uafe_estado, $proveedor->uafe_estado, $e->getMessage(), $proveedor->correo);

            throw $e;
        }
    }

    protected function registrarHistorial(
        Proveedores $proveedor,
        string $accion,
        ?string $estadoAnterior = null,
        ?string $estadoNuevo = null,
        ?string $detalle = null,
        ?string $correoDestino = null,
    ): void {
        ProveedorUafeHistorial::create([
            'proveedor_id' => $proveedor->id,
            'accion' => $accion,
            'estado_anterior' => $estadoAnterior,
            'estado_nuevo' => $estadoNuevo,
            'detalle' => $detalle,
            'correo_destino' => $correoDestino,
            'enviado_en' => now(),
            'usuario_id' => Auth::id(),
        ]);
    }

    protected function aplicarSmtpRuntime(UafeConfiguracion $config): void
    {
        Config::set('mail.mailers.smtp.host', $config->smtp_host ?: config('mail.mailers.smtp.host'));
        Config::set('mail.mailers.smtp.port', $config->smtp_puerto ?: config('mail.mailers.smtp.port'));
        Config::set('mail.mailers.smtp.username', $config->smtp_usuario ?: config('mail.mailers.smtp.username'));
        Config::set('mail.mailers.smtp.password', $config->smtp_password ?: config('mail.mailers.smtp.password'));
        Config::set('mail.mailers.smtp.encryption', $config->smtp_cifrado ?: config('mail.mailers.smtp.encryption'));
        Config::set('mail.mailers.smtp.timeout', $config->smtp_timeout ?: config('mail.mailers.smtp.timeout'));
        Config::set('mail.from.address', $config->smtp_from_email ?: config('mail.from.address'));
        Config::set('mail.from.name', $config->smtp_from_name ?: config('mail.from.name'));
    }
}
