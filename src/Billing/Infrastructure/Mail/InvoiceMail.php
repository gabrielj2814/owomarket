<?php

declare(strict_types=1);

namespace Src\Billing\Infrastructure\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Src\Billing\Domain\Entities\Invoice;

final class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Invoice $invoice,
        public readonly string $pdfBinary
    ) {}

    public function envelope(): Envelope
    {
        $issuerName = $this->invoice->issuer()->legalName();
        $number = $this->invoice->invoiceNumber()->value();

        return new Envelope(
            subject: "Factura {$number} - {$issuerName}"
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: "
                <p>Estimado(a) <strong>{$this->invoice->customer()->name()}</strong>,</p>
                <p>Le agradecemos su preferencia. Adjunto a este correo encontrará su comprobante/factura electrónica <strong>{$this->invoice->invoiceNumber()->value()}</strong> por un total de <strong>\${$this->invoice->total()} {$this->invoice->currency()}</strong>.</p>
                <p>Saludos cordiales,<br><strong>{$this->invoice->issuer()->legalName()}</strong></p>
            "
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $filename = "{$this->invoice->invoiceNumber()->value()}.pdf";

        return [
            Attachment::fromData(fn () => $this->pdfBinary, $filename)
                ->withMime('application/pdf'),
        ];
    }
}
