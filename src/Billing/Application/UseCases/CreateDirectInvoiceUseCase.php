<?php

declare(strict_types=1);

namespace Src\Billing\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Src\Billing\Application\Contracts\Repositories\BillingProfileRepositoryInterface;
use Src\Billing\Application\Contracts\Repositories\InvoiceRepositoryInterface;
use Src\Billing\Application\DTOs\CreateDirectInvoiceData;
use Src\Billing\Application\DTOs\InvoiceItemData;
use Src\Billing\Domain\Entities\Invoice;

final class CreateDirectInvoiceUseCase
{
    public function __construct(
        private readonly InvoiceRepositoryInterface $invoiceRepository,
        private readonly BillingProfileRepositoryInterface $billingProfileRepository
    ) {}

    /**
     * Hallazgo C4 — números de factura correlativos duplicados.
     *
     * Antes: `getProfile()` hacía un `first()` sin bloqueo, el incremento del
     * correlativo ocurría en memoria y se persistía en una escritura aparte, y
     * la transacción del repositorio de facturas era posterior y no cubría el
     * contador.
     *
     * Escenario de la auditoría: dos operadores emiten factura a la vez con
     * `next_invoice_number = 42`. Ambos generan `FAC-2026-000042` — dos
     * facturas fiscales con el mismo correlativo. Y si el `save()` de la
     * factura fallaba, el contador ya había quedado incrementado, dejando un
     * hueco en la serie.
     *
     * Ahora el número se reserva de forma atómica (fila del perfil bloqueada)
     * y todo el caso de uso corre dentro de una transacción: si la factura no
     * se persiste, el correlativo no se consume.
     */
    public function execute(CreateDirectInvoiceData $data): Invoice
    {
        return DB::transaction(function () use ($data) {
            // 1. Reservar el correlativo de forma atómica. Crea el perfil por
            //    defecto si aún no existe.
            $invoiceNumber = $this->billingProfileRepository->reserveNextInvoiceNumber();

            $profile = $this->billingProfileRepository->getProfile();

            // 2. Mapear ítems a entidades de dominio
            $items = array_map(
                fn (InvoiceItemData $itemData) => $itemData->toDomain(),
                $data->items
            );

            // 3. Crear agregado Invoice
            $invoice = Invoice::createDirect(
                invoiceNumber: $invoiceNumber,
                customer: [
                    'name' => $data->customer_name,
                    'tax_id' => $data->customer_tax_id,
                    'email' => $data->customer_email,
                    'address' => $data->toCustomerAddressArray(),
                ],
                issuer: $profile->toArray(),
                items: $items,
                paymentMethod: $data->payment_method,
                paymentStatus: $data->payment_status,
                status: $data->status,
                issueDate: $data->issue_date,
                dueDate: $data->due_date,
                currency: $data->currency,
                notes: $data->notes,
                orderId: $data->order_id,
                customerId: $data->customer_id,
                metadata: $data->metadata,
                exchangeRate: $data->exchange_rate,
                commissionAmount: $data->commission_amount,
                commissionCurrency: $data->commission_currency
            );

            // 4. Persistir factura
            return $this->invoiceRepository->save($invoice);
        });
    }
}
