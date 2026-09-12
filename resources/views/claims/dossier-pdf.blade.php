{{--
    Expediente de reclamación en PDF (subsistema 5, fase D).

    El destino de este papel es acompañar una denuncia, así que está escrito para que lo lea
    alguien que no conoce la plataforma: sin jerga interna, sin identificadores que no
    signifiquen nada fuera, y con la cronología como columna vertebral —cuándo se entregó,
    cuándo se reclamó, cuándo se resolvió— porque es lo que sostiene un caso.

    pendiente-abogado: este documento incluye la identidad completa del comerciante. Qué campos
    salen lo decide el bloque `store` de `BuildClaimDossierUseCase`, NO esta plantilla: filtrar
    aquí además crearía un segundo sitio donde decidirlo, y dos sitios acaban divergiendo.

    dompdf no soporta flexbox ni grid: de ahí las tablas para maquetar. No es descuido de estilo.
--}}
@php
    $claim = $expediente['claim'];
    $cliente = $expediente['customer'];
    $tienda = $expediente['store'];
    $entrega = $expediente['delivery'];

    $fecha = static fn (?string $iso) => $iso ? \Illuminate\Support\Carbon::parse($iso)->format('d/m/Y H:i') : '—';

    $comoSeResolvio = match ($claim['resolved_by'] ?? null) {
        'timeout' => 'Resuelta automáticamente: la tienda no respondió dentro del plazo.',
        'merchant' => 'Resuelta por la tienda.',
        null => 'Todavía sin resolver.',
        default => 'Resuelta por el equipo de la plataforma.',
    };

    $estados = [
        'requested' => 'Solicitada',
        'in_review' => 'En revisión',
        'approved' => 'Aprobada',
        'rejected' => 'Rechazada',
        'refunded' => 'Reembolsada',
    ];
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Expediente de reclamación {{ $claim['order_number'] }}</title>
    <style>
        @page { margin: 28px 34px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1f2937; line-height: 1.5; }
        h1 { font-size: 16px; margin: 0 0 2px; }
        h2 { font-size: 11px; margin: 18px 0 6px; padding-bottom: 3px; border-bottom: 1px solid #d1d5db; text-transform: uppercase; letter-spacing: 0.5px; }
        .sub { color: #6b7280; font-size: 9px; }
        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: top; padding: 3px 0; }
        td.k { width: 34%; color: #6b7280; }
        td.v { font-weight: bold; }
        .caja { border: 1px solid #d1d5db; padding: 8px 10px; margin-top: 4px; }
        .hito { padding: 4px 0 4px 10px; border-left: 2px solid #9ca3af; }
        .hito .cuando { font-weight: bold; }
        .nota { font-size: 8px; color: #6b7280; margin-top: 22px; border-top: 1px solid #e5e7eb; padding-top: 6px; }
    </style>
</head>
<body>

<h1>Expediente de reclamación</h1>
<div class="sub">
    Pedido {{ $claim['order_number'] }} · Emitido el {{ now()->format('d/m/Y H:i') }} · OwOMarket
</div>

<h2>La reclamación</h2>
<table>
    <tr><td class="k">Producto</td><td class="v">{{ $claim['product_name'] }}</td></tr>
    <tr><td class="k">Importe reclamado</td><td class="v">{{ number_format((float) $claim['amount'], 2) }} USD</td></tr>
    <tr><td class="k">Motivo</td><td class="v">{{ $claim['reason'] }}</td></tr>
    <tr><td class="k">Estado</td><td class="v">{{ $estados[$claim['status']] ?? $claim['status'] }}</td></tr>
    <tr><td class="k">Cómo se resolvió</td><td class="v">{{ $comoSeResolvio }}</td></tr>
</table>

@if (!empty($claim['description']))
    <div class="caja">{{ $claim['description'] }}</div>
@endif

@if (!empty($claim['resolution_notes']))
    <div class="caja"><strong>Respuesta de la tienda:</strong> {{ $claim['resolution_notes'] }}</div>
@endif

<h2>Cronología</h2>
{{-- La mitad del expediente. Sin fechas no hay caso que defender. --}}
<div class="hito"><span class="cuando">{{ $fecha($claim['delivered_at']) }}</span> — Entrega registrada</div>
<div class="hito"><span class="cuando">{{ $fecha($claim['claimed_at']) }}</span> — El comprador presenta la reclamación</div>
<div class="hito"><span class="cuando">{{ $fecha($claim['resolved_at']) }}</span> — Resolución</div>

<h2>El comprador</h2>
<table>
    <tr><td class="k">Nombre</td><td class="v">{{ $cliente['name'] ?? '—' }}</td></tr>
    <tr><td class="k">Documento de identidad</td><td class="v">{{ $cliente['document_id'] ?? '—' }}</td></tr>
    <tr><td class="k">Correo</td><td class="v">{{ $cliente['email'] ?? '—' }}</td></tr>
    <tr><td class="k">Teléfono</td><td class="v">{{ $cliente['phone'] ?? '—' }}</td></tr>
</table>

<h2>La tienda</h2>
<table>
    <tr><td class="k">Razón social</td><td class="v">{{ $tienda['legal_name'] ?? '—' }}</td></tr>
    <tr><td class="k">Documento de identidad</td><td class="v">{{ $tienda['cedula'] ?? '—' }}</td></tr>
    <tr><td class="k">RIF</td><td class="v">{{ $tienda['rif'] ?? '—' }}</td></tr>
    <tr><td class="k">Teléfono</td><td class="v">{{ $tienda['phone'] ?? '—' }}</td></tr>
    <tr><td class="k">Dirección</td><td class="v">{{ $tienda['address'] ?? '—' }}</td></tr>
    <tr>
        <td class="k">Identidad verificada</td>
        <td class="v">{{ ($tienda['kyc_status'] ?? 'missing') === 'verified' ? 'Sí, verificada por la plataforma' : 'No verificada' }}</td>
    </tr>
</table>

<h2>Evidencias de entrega</h2>
@if ($entrega === null)
    <p>No consta ningún registro de entrega para este pedido.</p>
@else
    <table>
        <tr><td class="k">Entrega declarada por la tienda</td><td class="v">{{ $fecha($entrega['declared_delivered_at']) }}</td></tr>
        <tr><td class="k">Confirmación del comprador</td><td class="v">{{ $fecha($entrega['confirmed_at']) }}</td></tr>
        <tr>
            <td class="k">Liberada por</td>
            <td class="v">{{ ($entrega['released_by'] ?? null) === 'timeout' ? 'Vencimiento del plazo, sin confirmación del comprador' : 'Confirmación del comprador' }}</td>
        </tr>
        <tr><td class="k">Pruebas aportadas por la tienda</td><td class="v">{{ count($entrega['shipment_evidence'] ?? []) }}</td></tr>
        <tr><td class="k">Pruebas aportadas por el comprador</td><td class="v">{{ count($entrega['confirmation_evidence'] ?? []) }}</td></tr>
    </table>
@endif

@if (!empty($claim['photos']))
    <p class="sub">El comprador adjuntó {{ count($claim['photos']) }} {{ count($claim['photos']) === 1 ? 'fotografía' : 'fotografías' }} a su reclamación, disponibles en la plataforma.</p>
@endif

<div class="nota">
    Documento generado automáticamente por OwOMarket a partir de los registros de la plataforma.
    Recoge lo que la plataforma tiene guardado sobre esta operación; no constituye asesoramiento
    legal ni una determinación de responsabilidad.
</div>

</body>
</html>
