<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Factura {{ $invoice->number }} | {{ config('app.name') }}</title>
    <style>
        body{font-family:Arial,sans-serif;color:#0f172a;margin:0;background:#f1f5f9}.document{max-width:850px;margin:32px auto;padding:40px;background:#fff}.toolbar{max-width:850px;margin:20px auto;display:flex;justify-content:flex-end;gap:12px}.toolbar a,.toolbar button{border:1px solid #cbd5e1;background:#fff;border-radius:6px;padding:10px 16px;color:#0f172a;text-decoration:none;font-weight:600;cursor:pointer}h1{margin:0 0 8px;font-size:28px}.muted{color:#64748b}.grid{display:grid;grid-template-columns:1fr 1fr;gap:24px;margin:32px 0}.label{font-size:12px;text-transform:uppercase;letter-spacing:.06em;color:#64748b}.value{margin-top:5px;font-weight:600}table{width:100%;border-collapse:collapse;margin-top:28px}th,td{padding:12px;border-bottom:1px solid #e2e8f0;text-align:left}th{font-size:12px;text-transform:uppercase;color:#475569;background:#f8fafc}td.num,th.num{text-align:right;font-variant-numeric:tabular-nums}.total{margin-top:18px;text-align:right;font-size:20px;font-weight:700}.notes{margin-top:30px;white-space:pre-line}@media print{body{background:#fff}.document{max-width:none;margin:0;padding:0}.toolbar{display:none}}
    </style>
</head>
<body>
    <div class="toolbar"><a href="{{ route('facturas.show', $invoice) }}">Volver</a><button type="button" onclick="window.print()">Imprimir / Guardar como PDF</button></div>
    <main class="document">
        <h1>Factura</h1>
        <p class="muted">{{ config('app.name') }} · {{ $invoice->status->label() }}</p>
        <div class="grid">
            <div><div class="label">Número</div><div class="value">{{ $invoice->number }}</div></div>
            <div><div class="label">Fecha de emisión</div><div class="value">{{ $invoice->issue_date->format('Y-m-d') }}</div></div>
            <div><div class="label">Cliente</div><div class="value">{{ $invoice->sale->customer->name }}</div><div>{{ $invoice->sale->customer->nit ?: 'NIT no registrado' }}</div></div>
            <div><div class="label">Venta de origen</div><div class="value">{{ $invoice->sale->number }}</div></div>
        </div>
        <table>
            <thead><tr><th>Producto</th><th class="num">Cantidad</th><th class="num">Precio unitario</th><th class="num">Subtotal</th></tr></thead>
            <tbody>
                @foreach ($invoice->sale->details as $detail)
                    <tr><td>{{ $detail->product->name }} <span class="muted">({{ $detail->product->sku }})</span></td><td class="num">{{ $detail->quantity }}</td><td class="num">Q {{ number_format((float) $detail->unit_price, 2) }}</td><td class="num">Q {{ number_format((float) $detail->subtotal, 2) }}</td></tr>
                @endforeach
            </tbody>
        </table>
        <div class="total">Total: Q {{ number_format((float) $invoice->total, 2) }}</div>
        @if ($invoice->notes)
            <div class="notes"><strong>Notas</strong><br>{{ $invoice->notes }}</div>
        @endif
    </main>
</body>
</html>
