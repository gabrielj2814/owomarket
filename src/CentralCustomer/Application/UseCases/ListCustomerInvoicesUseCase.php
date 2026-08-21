<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Application\UseCases;

use Src\Order\Infrastructure\Eloquent\Models\CentralOrder;

final class ListCustomerInvoicesUseCase
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function execute(string $customerId, ?string $customerEmail = null): array
    {
        $orders = CentralOrder::where(function ($q) use ($customerId, $customerEmail) {
            $q->where('customer_id', $customerId);
            if ($customerEmail) {
                $q->orWhere('customer_email', strtolower(trim($customerEmail)));
            }
        })
            ->whereIn('payment_status', ['paid', 'pending'])
            ->orderBy('created_at', 'desc')
            ->get();

        return $orders->map(function (CentralOrder $order) {
            $bcvRate = 775.3356;
            if (isset($order->payment_details['rate_bcv']) && is_numeric($order->payment_details['rate_bcv'])) {
                $bcvRate = (float) $order->payment_details['rate_bcv'];
            }

            $totalVes = isset($order->payment_details['total_bs']) && is_numeric($order->payment_details['total_bs'])
                ? (float) $order->payment_details['total_bs']
                : round($order->total * $bcvRate, 2);

            $invoiceNumber = 'FAC-'.str_replace('ORD-', '', $order->order_number);

            return [
                'id' => $order->id,
                'invoice_number' => $invoiceNumber,
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $order->customer_name,
                'customer_email' => $order->customer_email,
                'customer_document_id' => $order->customer_document_id ?? 'V-00000000',
                'date' => $order->created_at?->format('d/m/Y'),
                'total_usd' => (float) $order->total,
                'total_ves' => $totalVes,
                'exchange_rate_bcv' => $bcvRate,
                'payment_method' => $order->payment_method,
                'payment_status' => $order->payment_status,
                'pdf_url' => "/api/central/customer/invoices/{$order->id}/pdf",
            ];
        })->toArray();
    }
}
