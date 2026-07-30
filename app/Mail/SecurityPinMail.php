<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SecurityPinMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $userName;
    public string $pin;

    /**
     * Create a new message instance.
     */
    public function __construct(string $userName, string $pin)
    {
        $this->userName = $userName;
        $this->pin = $pin;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu PIN de Seguridad para Cambio de Contraseña - OwoMarket',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            htmlString: $this->buildHtml(),
        );
    }

    private function buildHtml(): string
    {
        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e5e7eb; border-radius: 8px; background-color: #ffffff;'>
            <h2 style='color: #1f2937; text-align: center;'>🔐 Solicitud de Cambio de Contraseña</h2>
            <p style='color: #4b5563; font-size: 16px;'>Hola <strong>" . htmlspecialchars($this->userName) . "</strong>,</p>
            <p style='color: #4b5563; font-size: 15px;'>Hemos recibido una solicitud para cambiar la contraseña de tu cuenta administrativa en <strong>OwoMarket</strong>.</p>
            <div style='text-align: center; margin: 30px 0;'>
                <span style='font-size: 32px; font-weight: bold; letter-spacing: 6px; color: #4f46e5; background-color: #eef2ff; padding: 12px 24px; border-radius: 8px; border: 1px dashed #6366f1;'>
                    " . htmlspecialchars($this->pin) . "
                </span>
            </div>
            <p style='color: #ef4444; font-size: 14px; text-align: center;'>⚠️ Este PIN vence en <strong>15 minutos</strong>. No compartas este código con nadie.</p>
            <hr style='border: none; border-top: 1px solid #e5e7eb; margin: 25px 0;' />
            <p style='color: #9ca3af; font-size: 12px; text-align: center;'>Si no solicitaste este cambio, puedes ignorar este correo de forma segura.</p>
        </div>
        ";
    }
}
