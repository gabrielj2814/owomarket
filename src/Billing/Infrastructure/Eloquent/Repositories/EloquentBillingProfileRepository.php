<?php

declare(strict_types=1);

namespace Src\Billing\Infrastructure\Eloquent\Repositories;

use Illuminate\Support\Facades\DB;
use Src\Billing\Application\Contracts\Repositories\BillingProfileRepositoryInterface;
use Src\Billing\Domain\Entities\BillingProfile;
use Src\Billing\Infrastructure\Eloquent\Models\BillingProfile as EloquentBillingProfile;

final class EloquentBillingProfileRepository implements BillingProfileRepositoryInterface
{
    public function getProfile(): ?BillingProfile
    {
        $model = EloquentBillingProfile::first();

        if (! $model) {
            return null;
        }

        return $this->toDomain($model);
    }

    public function save(BillingProfile $profile): BillingProfile
    {
        $model = EloquentBillingProfile::updateOrCreate(
            ['id' => $profile->id()->value()],
            [
                'legal_name' => $profile->legalName(),
                'tax_id' => $profile->taxId()->value(),
                'billing_email' => $profile->billingEmail()->value(),
                'phone' => $profile->phone(),
                'address_line_1' => $profile->address()->addressLine1(),
                'address_line_2' => $profile->address()->addressLine2(),
                'city' => $profile->address()->city(),
                'state' => $profile->address()->state(),
                'postal_code' => $profile->address()->postalCode(),
                'country' => $profile->address()->country(),
                'invoice_prefix' => $profile->invoicePrefix(),
                'next_invoice_number' => $profile->nextInvoiceNumber(),
                'invoice_footer_notes' => $profile->invoiceFooterNotes(),
                'logo_path' => $profile->logoPath(),
                'metadata' => $profile->metadata(),
            ]
        );

        return $this->toDomain($model);
    }

    /**
     * Reserva atómicamente el siguiente correlativo de factura (hallazgo C4).
     *
     * Toda la operación —leer el contador, formatear el número e incrementarlo—
     * ocurre dentro de una transacción con la fila del perfil bloqueada. Una
     * segunda petición simultánea espera en el `lockForUpdate()` y, cuando
     * entra, ya lee el contador incrementado.
     *
     * Antes, `getProfile()` hacía un `first()` sin bloqueo, el incremento
     * ocurría en memoria y se persistía aparte: dos operadores emitiendo
     * factura a la vez con `next_invoice_number = 42` generaban ambos
     * `FAC-2026-000042`, dos facturas fiscales con el mismo correlativo.
     */
    public function reserveNextInvoiceNumber(): string
    {
        return DB::transaction(function () {
            $model = EloquentBillingProfile::lockForUpdate()->first();

            if (! $model) {
                // Sin perfil todavía: se crea el de por defecto ya bloqueado
                // dentro de esta misma transacción.
                $profile = BillingProfile::create(
                    legalName: 'Mi Empresa',
                    taxId: 'CL-11223344-5',
                    billingEmail: 'facturacion@store.com',
                    phone: null,
                    address: [
                        'address_line_1' => 'Dirección Comercial 123',
                        'city' => 'Santiago',
                        'state' => 'RM',
                        'postal_code' => '8320000',
                        'country' => 'Chile',
                    ],
                    invoicePrefix: 'FAC-',
                    nextInvoiceNumber: 1
                );

                $this->save($profile);
                $model = EloquentBillingProfile::lockForUpdate()->first();
            }

            $current = (int) ($model->next_invoice_number ?: 1);
            $prefix = (string) ($model->invoice_prefix ?: 'FAC-');

            // Mismo formato que BillingProfile::generateAndIncrementInvoiceNumber().
            $invoiceNumber = $prefix.date('Y').'-'.str_pad((string) $current, 6, '0', STR_PAD_LEFT);

            $model->next_invoice_number = $current + 1;
            $model->save();

            return $invoiceNumber;
        });
    }

    private function toDomain(EloquentBillingProfile $model): BillingProfile
    {
        return BillingProfile::create(
            legalName: $model->legal_name,
            taxId: $model->tax_id,
            billingEmail: $model->billing_email,
            phone: $model->phone,
            address: [
                'address_line_1' => $model->address_line_1,
                'address_line_2' => $model->address_line_2,
                'city' => $model->city,
                'state' => $model->state,
                'postal_code' => $model->postal_code,
                'country' => $model->country,
            ],
            invoicePrefix: $model->invoice_prefix,
            nextInvoiceNumber: $model->next_invoice_number,
            invoiceFooterNotes: $model->invoice_footer_notes,
            logoPath: $model->logo_path,
            metadata: $model->metadata,
            id: $model->id
        );
    }
}
