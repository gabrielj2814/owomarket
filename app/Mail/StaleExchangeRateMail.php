<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Aviso de que la tasa BCV activa lleva días sin actualizarse (hallazgo N20).
 */
class StaleExchangeRateMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $recipientName,
        public float $activeRate,
        public string $rateDate,
        public int $daysStale,
        public string $errorMessage
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "⚠️ La tasa BCV lleva {$this->daysStale} días sin actualizarse - OwoMarket",
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: $this->buildHtml(),
        );
    }

    private function buildHtml(): string
    {
        $rate = number_format($this->activeRate, 4, ',', '.');
        $error = e($this->errorMessage);
        $name = e($this->recipientName);
        $fecha = e($this->rateDate);

        return <<<HTML
        <div style="font-family: system-ui, -apple-system, sans-serif; max-width: 560px; margin: 0 auto; color: #1f2937;">
            <h2 style="color: #b91c1c;">La sincronización con el BCV lleva {$this->daysStale} días fallando</h2>

            <p>Hola {$name},</p>

            <p>
                <strong>Todo el sitio está facturando con una tasa desactualizada.</strong>
                Cada conversión a bolívares que se muestre o se cobre ahora mismo usa el
                valor de abajo, no el del día.
            </p>

            <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #e5e7eb;">Tasa en uso</td>
                    <td style="padding: 8px; border-bottom: 1px solid #e5e7eb;"><strong>{$rate} VES/USD</strong></td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #e5e7eb;">Fecha valor</td>
                    <td style="padding: 8px; border-bottom: 1px solid #e5e7eb;">{$fecha}</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #e5e7eb;">Días sin actualizar</td>
                    <td style="padding: 8px; border-bottom: 1px solid #e5e7eb;"><strong>{$this->daysStale}</strong></td>
                </tr>
            </table>

            <p style="margin-bottom: 4px;"><strong>Motivo del fallo:</strong></p>
            <pre style="background: #f3f4f6; padding: 12px; border-radius: 6px; white-space: pre-wrap; word-break: break-word; font-size: 13px;">{$error}</pre>

            <p>
                Puedes forzar una sincronización desde el panel de tasas, o revisar si
                bcv.org.ve cambió la estructura de su página.
            </p>

            <p style="color: #6b7280; font-size: 13px;">
                Este aviso se envía una vez al día mientras el problema siga abierto.
            </p>
        </div>
        HTML;
    }
}
