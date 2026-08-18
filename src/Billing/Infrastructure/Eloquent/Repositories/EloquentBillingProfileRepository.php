<?php

declare(strict_types=1);

namespace Src\Billing\Infrastructure\Eloquent\Repositories;

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
