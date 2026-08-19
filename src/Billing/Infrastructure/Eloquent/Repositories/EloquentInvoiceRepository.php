<?php

declare(strict_types=1);

namespace Src\Billing\Infrastructure\Eloquent\Repositories;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Src\Billing\Application\Contracts\Repositories\InvoiceRepositoryInterface;
use Src\Billing\Application\DTOs\FilterInvoicesCriteria;
use Src\Billing\Application\DTOs\PaginatedInvoicesResult;
use Src\Billing\Domain\Entities\Invoice;
use Src\Billing\Domain\Entities\InvoiceItem;
use Src\Billing\Domain\ValueObjects\CustomerFiscalData;
use Src\Billing\Domain\ValueObjects\InvoiceDate;
use Src\Billing\Domain\ValueObjects\InvoiceId;
use Src\Billing\Domain\ValueObjects\InvoiceNumber;
use Src\Billing\Domain\ValueObjects\InvoiceStatus;
use Src\Billing\Domain\ValueObjects\IssuerFiscalData;
use Src\Billing\Infrastructure\Eloquent\Models\Invoice as EloquentInvoice;
use Src\Billing\Infrastructure\Eloquent\Models\InvoiceItem as EloquentInvoiceItem;

final class EloquentInvoiceRepository implements InvoiceRepositoryInterface
{
    public function save(Invoice $invoice): Invoice
    {
        return DB::transaction(function () use ($invoice) {
            $invoiceModel = EloquentInvoice::create([
                'id' => $invoice->id()->value(),
                'order_id' => $invoice->orderId(),
                'customer_id' => $invoice->customerId(),
                'invoice_number' => $invoice->invoiceNumber()->value(),
                'status' => $invoice->status()->value(),
                'issue_date' => $invoice->date()->issueDateFormatted(),
                'due_date' => $invoice->date()->dueDateFormatted(),
                'currency' => $invoice->currency(),
                'exchange_rate' => $invoice->exchangeRate(),
                'subtotal' => $invoice->subtotal(),
                'tax_amount' => $invoice->taxAmount(),
                'discount_amount' => $invoice->discountAmount(),
                'total' => $invoice->total(),
                'subtotal_ves' => $invoice->subtotalVes(),
                'total_ves' => $invoice->totalVes(),
                'subtotal_usd' => $invoice->subtotalUsd(),
                'total_usd' => $invoice->totalUsd(),
                'commission_amount' => $invoice->commissionAmount(),
                'commission_currency' => $invoice->commissionCurrency(),
                'payment_method' => $invoice->paymentMethod(),
                'payment_status' => $invoice->paymentStatus(),
                'paid_at' => $invoice->paidAt()?->format('Y-m-d H:i:s'),
                'billing_customer_name' => $invoice->customer()->name(),
                'billing_customer_tax_id' => $invoice->customer()->taxId(),
                'billing_customer_email' => $invoice->customer()->email(),
                'billing_customer_address' => $invoice->customer()->address()->toArray(),
                'issuer_snapshot' => $invoice->issuer()->toArray(),
                'pdf_path' => $invoice->pdfPath(),
                'notes' => $invoice->notes(),
                'metadata' => $invoice->metadata(),
            ]);

            foreach ($invoice->items() as $item) {
                EloquentInvoiceItem::create([
                    'id' => $item->id()->value(),
                    'invoice_id' => $invoice->id()->value(),
                    'product_id' => $item->productId(),
                    'product_variant_id' => $item->productVariantId(),
                    'description' => $item->description(),
                    'sku' => $item->sku(),
                    'quantity' => $item->quantity(),
                    'unit_price' => $item->unitPrice(),
                    'tax_rate' => $item->taxRate(),
                    'tax_amount' => $item->taxAmount(),
                    'discount_amount' => $item->discountAmount(),
                    'subtotal' => $item->subtotal(),
                    'total' => $item->total(),
                ]);
            }

            $invoiceModel->load('items');

            return $this->toDomain($invoiceModel);
        });
    }

    public function findById(InvoiceId $id): ?Invoice
    {
        $model = EloquentInvoice::with('items')->find($id->value());

        if (! $model) {
            return null;
        }

        return $this->toDomain($model);
    }

    public function findByNumber(InvoiceNumber $number): ?Invoice
    {
        $model = EloquentInvoice::with('items')->where('invoice_number', $number->value())->first();

        if (! $model) {
            return null;
        }

        return $this->toDomain($model);
    }

    public function update(Invoice $invoice): Invoice
    {
        $model = EloquentInvoice::findOrFail($invoice->id()->value());

        $model->update([
            'status' => $invoice->status()->value(),
            'payment_status' => $invoice->paymentStatus(),
            'payment_method' => $invoice->paymentMethod(),
            'paid_at' => $invoice->paidAt()?->format('Y-m-d H:i:s'),
            'pdf_path' => $invoice->pdfPath(),
            'notes' => $invoice->notes(),
            'metadata' => $invoice->metadata(),
            'exchange_rate' => $invoice->exchangeRate(),
            'subtotal_ves' => $invoice->subtotalVes(),
            'total_ves' => $invoice->totalVes(),
            'subtotal_usd' => $invoice->subtotalUsd(),
            'total_usd' => $invoice->totalUsd(),
            'commission_amount' => $invoice->commissionAmount(),
            'commission_currency' => $invoice->commissionCurrency(),
        ]);

        $model->load('items');

        return $this->toDomain($model);
    }

    public function filter(FilterInvoicesCriteria $criteria): PaginatedInvoicesResult
    {
        $query = EloquentInvoice::with('items');

        if ($criteria->search) {
            $search = '%'.trim($criteria->search).'%';
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', $search)
                    ->orWhere('billing_customer_name', 'like', $search)
                    ->orWhere('billing_customer_email', 'like', $search)
                    ->orWhere('billing_customer_tax_id', 'like', $search);
            });
        }

        if ($criteria->status) {
            $query->where('status', $criteria->status);
        }

        if ($criteria->payment_status) {
            $query->where('payment_status', $criteria->payment_status);
        }

        if ($criteria->date_from) {
            $query->whereDate('issue_date', '>=', $criteria->date_from);
        }

        if ($criteria->date_to) {
            $query->whereDate('issue_date', '<=', $criteria->date_to);
        }

        $paginator = $query->orderBy('created_at', 'desc')->paginate($criteria->per_page, ['*'], 'page', $criteria->page);

        $domainInvoices = array_map(
            fn (EloquentInvoice $m) => $this->toDomain($m),
            $paginator->items()
        );

        return new PaginatedInvoicesResult(
            items: $domainInvoices,
            total: $paginator->total(),
            currentPage: $paginator->currentPage(),
            perPage: $paginator->perPage(),
            lastPage: $paginator->lastPage()
        );
    }

    public function getMetrics(): array
    {
        $totalBilled = (float) EloquentInvoice::where('status', '!=', 'cancelled')->sum('total');
        $totalIssued = EloquentInvoice::where('status', 'issued')->count();
        $totalPaid = EloquentInvoice::where('status', 'paid')->count();
        $totalCancelled = EloquentInvoice::where('status', 'cancelled')->count();

        return [
            'total_billed' => round($totalBilled, 2),
            'total_issued' => $totalIssued,
            'total_paid' => $totalPaid,
            'total_cancelled' => $totalCancelled,
        ];
    }

    private function toDomain(EloquentInvoice $model): Invoice
    {
        $items = $model->items->map(function (EloquentInvoiceItem $itemModel) {
            return InvoiceItem::create(
                description: $itemModel->description,
                quantity: $itemModel->quantity,
                unitPrice: $itemModel->unit_price,
                taxRate: $itemModel->tax_rate,
                discountAmount: $itemModel->discount_amount,
                productId: $itemModel->product_id,
                productVariantId: $itemModel->product_variant_id,
                sku: $itemModel->sku,
                id: $itemModel->id
            );
        })->all();

        $paidAt = $model->paid_at ? new DateTimeImmutable($model->paid_at->toDateTimeString()) : null;

        return new Invoice(
            id: InvoiceId::fromString($model->id),
            orderId: $model->order_id,
            customerId: $model->customer_id,
            invoiceNumber: InvoiceNumber::fromString($model->invoice_number),
            status: InvoiceStatus::fromString($model->status),
            date: InvoiceDate::create(
                $model->issue_date?->format('Y-m-d'),
                $model->due_date?->format('Y-m-d')
            ),
            currency: $model->currency,
            subtotal: $model->subtotal,
            taxAmount: $model->tax_amount,
            discountAmount: $model->discount_amount,
            total: $model->total,
            paymentMethod: $model->payment_method,
            paymentStatus: $model->payment_status,
            paidAt: $paidAt,
            customer: CustomerFiscalData::fromArray([
                'name' => $model->billing_customer_name,
                'tax_id' => $model->billing_customer_tax_id,
                'email' => $model->billing_customer_email,
                'address' => $model->billing_customer_address,
            ]),
            issuer: IssuerFiscalData::fromArray($model->issuer_snapshot ?? []),
            items: $items,
            pdfPath: $model->pdf_path,
            notes: $model->notes,
            metadata: $model->metadata,
            exchangeRate: $model->exchange_rate ? (float) $model->exchange_rate : null,
            subtotalVes: $model->subtotal_ves ? (float) $model->subtotal_ves : null,
            totalVes: $model->total_ves ? (float) $model->total_ves : null,
            subtotalUsd: $model->subtotal_usd ? (float) $model->subtotal_usd : null,
            totalUsd: $model->total_usd ? (float) $model->total_usd : null,
            commissionAmount: $model->commission_amount ? (float) $model->commission_amount : null,
            commissionCurrency: $model->commission_currency
        );
    }
}
