<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura {{ $invoice['invoice_number'] }}</title>
    <style>
        @page {
            margin: 25mm 20mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #333333;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .header-table td {
            vertical-align: top;
        }
        .logo {
            max-width: 160px;
            max-height: 60px;
        }
        .issuer-name {
            font-size: 16px;
            font-weight: bold;
            color: #111827;
            margin-bottom: 4px;
        }
        .issuer-info {
            font-size: 10px;
            color: #4b5563;
        }
        .invoice-title-box {
            text-align: right;
        }
        .invoice-title {
            font-size: 22px;
            font-weight: bold;
            color: #1e40af;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .invoice-number {
            font-size: 13px;
            font-weight: bold;
            color: #374151;
            margin-bottom: 6px;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            border-radius: 4px;
        }
        .badge-paid {
            background-color: #d1fae5;
            color: #065f46;
        }
        .badge-issued {
            background-color: #dbeafe;
            color: #1e40af;
        }
        .badge-cancelled {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .badge-draft {
            background-color: #f3f4f6;
            color: #4b5563;
        }

        .details-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
        }
        .details-grid td {
            padding: 12px;
            vertical-align: top;
            width: 50%;
        }
        .box-title {
            font-size: 10px;
            font-weight: bold;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 3px;
        }
        .box-content-name {
            font-size: 12px;
            font-weight: bold;
            color: #111827;
            margin-bottom: 2px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .items-table th {
            background-color: #1e40af;
            color: #ffffff;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 8px 10px;
            text-align: left;
        }
        .items-table th.text-right, .items-table td.text-right {
            text-align: right;
        }
        .items-table th.text-center, .items-table td.text-center {
            text-align: center;
        }
        .items-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 10px;
        }
        .items-table tr:nth-child(even) td {
            background-color: #f9fafb;
        }

        .totals-table {
            width: 45%;
            margin-left: auto;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        .totals-table td {
            padding: 5px 8px;
            font-size: 11px;
        }
        .totals-table .label {
            color: #4b5563;
            text-align: right;
        }
        .totals-table .amount {
            text-align: right;
            font-weight: 600;
            color: #111827;
            width: 40%;
        }
        .totals-table .total-row td {
            border-top: 2px solid #1e40af;
            font-size: 14px;
            font-weight: bold;
            color: #1e40af;
            padding-top: 8px;
        }

        .footer-box {
            border-top: 1px solid #e5e7eb;
            padding-top: 15px;
            margin-top: 20px;
            font-size: 10px;
            color: #6b7280;
        }
        .notes-title {
            font-weight: bold;
            color: #374151;
            margin-bottom: 3px;
        }
    </style>
</head>
<body>

    <!-- Encabezado de Factura -->
    <table class="header-table">
        <tr>
            <td style="width: 55%;">
                @if(!empty($invoice['issuer_snapshot']['logo_path']))
                    <img src="{{ $invoice['issuer_snapshot']['logo_path'] }}" class="logo" alt="Logo">
                @endif
                <div class="issuer-name">{{ $invoice['issuer_snapshot']['legal_name'] ?? 'Comercio OwoMarket' }}</div>
                <div class="issuer-info">
                    <strong>ID Fiscal:</strong> {{ $invoice['issuer_snapshot']['tax_id'] ?? 'N/A' }}<br>
                    <strong>Email:</strong> {{ $invoice['issuer_snapshot']['billing_email'] ?? 'contacto@store.com' }}<br>
                    @if(!empty($invoice['issuer_snapshot']['phone']))
                        <strong>Tel:</strong> {{ $invoice['issuer_snapshot']['phone'] }}<br>
                    @endif
                    @if(!empty($invoice['issuer_snapshot']['address']))
                        {{ $invoice['issuer_snapshot']['address']['address_line_1'] ?? '' }}
                        @if(!empty($invoice['issuer_snapshot']['address']['city']))
                            , {{ $invoice['issuer_snapshot']['address']['city'] }}
                        @endif
                        @if(!empty($invoice['issuer_snapshot']['address']['country']))
                            , {{ $invoice['issuer_snapshot']['address']['country'] }}
                        @endif
                    @endif
                </div>
            </td>
            <td class="invoice-title-box" style="width: 45%;">
                <div class="invoice-title">FACTURA</div>
                <div class="invoice-number">{{ $invoice['invoice_number'] }}</div>
                <div>
                    @if($invoice['status'] === 'paid')
                        <span class="badge badge-paid">PAGADA</span>
                    @elseif($invoice['status'] === 'issued')
                        <span class="badge badge-issued">EMITIDA</span>
                    @elseif($invoice['status'] === 'cancelled')
                        <span class="badge badge-cancelled">ANULADA</span>
                    @else
                        <span class="badge badge-draft">BORRADOR</span>
                    @endif
                </div>
                <div style="margin-top: 10px; font-size: 10px; color: #4b5563;">
                    <strong>Fecha Emisión:</strong> {{ $invoice['issue_date'] }}<br>
                    @if(!empty($invoice['due_date']))
                        <strong>Fecha Vencimiento:</strong> {{ $invoice['due_date'] }}<br>
                    @endif
                    <strong>Moneda:</strong> {{ $invoice['currency'] }}<br>
                    <strong>Método de Pago:</strong> {{ ucfirst($invoice['payment_method'] ?? 'Manual') }}
                </div>
            </td>
        </tr>
    </table>

    <!-- Bloque de Datos del Cliente Receptor -->
    <table class="details-grid">
        <tr>
            <td>
                <div class="box-title">Facturado a (Cliente)</div>
                <div class="box-content-name">{{ $invoice['billing_customer_name'] }}</div>
                <div style="color: #4b5563; font-size: 10px;">
                    @if(!empty($invoice['billing_customer_tax_id']))
                        <strong>ID Fiscal:</strong> {{ $invoice['billing_customer_tax_id'] }}<br>
                    @endif
                    <strong>Email:</strong> {{ $invoice['billing_customer_email'] }}<br>
                    @if(!empty($invoice['billing_customer_address']['address_line_1']))
                        <strong>Dirección:</strong> {{ $invoice['billing_customer_address']['address_line_1'] }}
                        @if(!empty($invoice['billing_customer_address']['city']))
                            , {{ $invoice['billing_customer_address']['city'] }}
                        @endif
                        @if(!empty($invoice['billing_customer_address']['country']))
                            , {{ $invoice['billing_customer_address']['country'] }}
                        @endif
                    @endif
                </div>
            </td>
            <td>
                <div class="box-title">Detalles de la Transacción</div>
                <div style="color: #4b5563; font-size: 10px;">
                    <strong>Estado de Pago:</strong> {{ strtoupper($invoice['payment_status'] ?? 'PAID') }}<br>
                    @if(!empty($invoice['paid_at']))
                        <strong>Fecha de Pago:</strong> {{ $invoice['paid_at'] }}<br>
                    @endif
                    @if(!empty($invoice['order_id']))
                        <strong>N° Pedido / Orden:</strong> {{ $invoice['order_id'] }}<br>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <!-- Tabla de Ítems / Conceptos -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 45%;">Descripción</th>
                <th class="text-center" style="width: 10%;">Cant.</th>
                <th class="text-right" style="width: 12%;">P. Unitario</th>
                <th class="text-right" style="width: 8%;">IVA %</th>
                <th class="text-right" style="width: 10%;">Desc.</th>
                <th class="text-right" style="width: 10%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice['items'] as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        <strong>{{ $item['description'] }}</strong>
                        @if(!empty($item['sku']))
                            <div style="color: #6b7280; font-size: 9px;">SKU: {{ $item['sku'] }}</div>
                        @endif
                    </td>
                    <td class="text-center">{{ $item['quantity'] }}</td>
                    <td class="text-right">${{ number_format($item['unit_price'], 2) }}</td>
                    <td class="text-right">{{ number_format($item['tax_rate'], 1) }}%</td>
                    <td class="text-right">${{ number_format($item['discount_amount'], 2) }}</td>
                    <td class="text-right">${{ number_format($item['total'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totales -->
    <table class="totals-table">
        <tr>
            <td class="label">Subtotal Neto:</td>
            <td class="amount">${{ number_format($invoice['subtotal'], 2) }}</td>
        </tr>
        @if($invoice['discount_amount'] > 0)
            <tr>
                <td class="label">Descuentos:</td>
                <td class="amount" style="color: #dc2626;">-${{ number_format($invoice['discount_amount'], 2) }}</td>
            </tr>
        @endif
        @if($invoice['tax_amount'] > 0)
            <tr>
                <td class="label">Impuestos (IVA):</td>
                <td class="amount">${{ number_format($invoice['tax_amount'], 2) }}</td>
            </tr>
        @endif
        <tr class="total-row">
            <td class="label">TOTAL:</td>
            <td class="amount">${{ number_format($invoice['total'], 2) }} {{ $invoice['currency'] }}</td>
        </tr>
    </table>

    <div style="clear: both;"></div>

    <!-- Notas y Pie de Página -->
    <div class="footer-box">
        @if(!empty($invoice['notes']))
            <div class="notes-title">Notas:</div>
            <div>{{ $invoice['notes'] }}</div>
        @endif

        @if(!empty($invoice['issuer_snapshot']['invoice_footer_notes']))
            <div style="margin-top: 8px; font-style: italic;">
                {{ $invoice['issuer_snapshot']['invoice_footer_notes'] }}
            </div>
        @endif

        <div style="text-align: center; margin-top: 20px; font-size: 9px; color: #9ca3af;">
            Documento emitido electrónicamente por OwoMarket para {{ $invoice['issuer_snapshot']['legal_name'] ?? 'la tienda' }}.
        </div>
    </div>

</body>
</html>
