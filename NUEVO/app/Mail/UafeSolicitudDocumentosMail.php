<?php

namespace App\Mail;

use App\Models\Proveedores;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class UafeSolicitudDocumentosMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Proveedores $proveedor,
        public string $subjectLine,
        public string $body,
        public array $attachments = [],
    ) {}

    public function build(): static
    {
        $mail = $this->subject($this->subjectLine)
            ->view('emails.uafe-solicitud-documentos', [
                'proveedor' => $this->proveedor,
                'body' => $this->body,
            ]);

        foreach ($this->attachments as $attachmentPath) {
            if ($attachmentPath && Storage::disk('public')->exists($attachmentPath)) {
                $mail->attachFromStorageDisk('public', $attachmentPath);
            }
        }

        return $mail;
    }
}
