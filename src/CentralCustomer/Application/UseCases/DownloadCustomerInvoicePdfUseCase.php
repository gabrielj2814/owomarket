<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Application\UseCases;

use App\Models\CentralOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;

final class DownloadCustomerInvoicePdfUseCase
{
    /**
     * @return array{filename: string, content: string}
     */
    public function execute(string $customerId, string $orderId): array
    {
        $order = CentralOrder::with('items')
            ->where('id', $orderId)
            ->where('customer_id', $customerId)
            ->first();

        if (! $order) {
            throw new Exception('Factura no encontrada o no pertenece a este usuario.', 404);
        }

        $bcvRate = 775.3356;
        if (isset($order->payment_details['rate_bcv']) && is_numeric($order->payment_details['rate_bcv'])) {
            $bcvRate = (float) $order->payment_details['rate_bcv'];
        }

        $totalVes = isset($order->payment_details['total_bs']) && is_numeric($order->payment_details['total_bs'])
            ? (float) $order->payment_details['total_bs']
            : round($order->total * $bcvRate, 2);

        $subtotalVes = round($order->subtotal * $bcvRate, 2);
        $invoiceNumber = 'FAC-'.str_replace('ORD-', '', $order->order_number);

        $items = $order->items->map(function ($item) use ($bcvRate) {
            $unitPriceUsd = (float) $item->price;
            $itemTotalUsd = (float) $item->total;

            return [
                'description' => $item->product_name,
                'sku' => $item->sku ?? '',
                'quantity' => (int) $item->quantity,
                'unit_price' => $unitPriceUsd,
                'tax_rate' => 16.0,
                'discount_amount' => 0.00,
                'total' => $itemTotalUsd,
                'unit_price_ves' => round($unitPriceUsd * $bcvRate, 2),
                'total_price_ves' => round($itemTotalUsd * $bcvRate, 2),
                'store_name' => $item->tenant_id,
            ];
        })->toArray();

        $invoiceData = [
            'invoice_number' => $invoiceNumber,
            'status' => $order->payment_status === 'paid' ? 'paid' : 'issued',
            'issue_date' => $order->created_at?->format('d/m/Y') ?? date('d/m/Y'),
            'due_date' => $order->created_at?->addDays(7)->format('d/m/Y'),
            'currency' => 'USD',
            'payment_method' => $order->payment_method === 'pago_movil' ? 'Pago Móvil' : 'Binance Pay',
            'payment_status' => $order->payment_status,
            'paid_at' => $order->payment_status === 'paid' ? $order->updated_at?->format('d/m/Y H:i') : null,
            'order_id' => $order->order_number,
            'issuer_snapshot' => [
                'legal_name' => 'OwOMarket Venezuela C.A.',
                'tax_id' => 'J-50012345-6',
                'billing_email' => 'facturacion@owomarket.local',
                'phone' => '+58 212 555-0100',
                'logo_path' => null,
                'address' => [
                    'address_line_1' => 'Av. Francisco de Miranda, Torre Cavendes',
                    'city' => 'Caracas',
                    'country' => 'Venezuela',
                ],
            ],
            'billing_customer_name' => $order->customer_name,
            'billing_customer_email' => $order->customer_email,
            'billing_customer_tax_id' => $order->customer_document_id ?? 'V-00000000',
            'billing_customer_address' => [
                'address_line_1' => is_array($order->shipping_address) ? ($order->shipping_address['address'] ?? 'Caracas, Venezuela') : 'Caracas, Venezuela',
                'city' => is_array($order->shipping_address) ? ($order->shipping_address['city'] ?? 'Caracas') : 'Caracas',
                'country' => 'Venezuela',
            ],
            'items' => $items,
            'subtotal' => (float) $order->subtotal,
            'discount_amount' => (float) ($order->discount_amount ?? 0),
            'tax_amount' => round($order->subtotal * 0.16, 2),
            'total' => (float) $order->total,
            'exchange_rate_bcv' => $bcvRate,
            'exchange_rate_date' => $order->created_at?->format('d/m/Y'),
            'total_ves' => $totalVes,
            'notes' => 'Factura emitida de conformidad con la normativa del BCV y Ley de Impuesto a las Grandes Transacciones Financieras (IGTF).',
        ];

        $pdf = Pdf::loadView('invoices.pdf', [
            'invoice' => $invoiceData,
        ]);
        $pdf->setPaper('a4', 'portrait');

        return [
            'filename' => "Factura-{$invoiceNumber}.pdf",
            'content' => (string) $pdf->output(),
        ];
    }
}
