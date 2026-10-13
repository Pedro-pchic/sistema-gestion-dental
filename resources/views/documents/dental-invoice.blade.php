<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Comprobante {{ $invoice->number }} | {{ config('app.name') }}</title>
    <style>
        body{font-family:Arial,sans-serif;color:#17211c;margin:0;background:#f4f7f5}.document{max-width:850px;margin:32px auto;padding:40px;background:#fff}.toolbar{max-width:850px;margin:20px auto;display:flex;justify-content:flex-end;gap:12px}.toolbar a,.toolbar button{border:1px solid #cbd5e1;background:#fff;border-radius:6px;padding:10px 16px;color:#17211c;text-decoration:none;font-weight:600;cursor:pointer}h1{margin:0 0 8px;font-size:28px}.muted{color:#64748b}.notice{margin-top:18px;padding:12px;border:1px solid #dbe7df;background:#f6faf7;color:#3f594a;font-size:12px}.grid{display:grid;grid-template-columns:1fr 1fr;gap:24px;margin:28px 0}.label{font-size:12px;text-transform:uppercase;letter-spacing:.06em;color:#64748b}.value{margin-top:5px;font-weight:600}table{width:100%;border-collapse:collapse;margin-top:28px}th,td{padding:12px;border-bottom:1px solid #e2e8f0;text-align:left}th{font-size:12px;text-transform:uppercase;color:#475569;background:#f8fafc}td.num,th.num{text-align:right;font-variant-numeric:tabular-nums}.totals{margin:20px 0 0 auto;max-width:340px}.total-row{display:flex;justify-content:space-between;gap:20px;padding:7px 0}.grand-total{border-top:1px solid #cbd5e1;margin-top:6px;padding-top:14px;font-size:19px;font-weight:700}.balance{font-weight:700}.footer{margin-top:30px;color:#64748b;font-size:12px}
        body{background:#f4f7fb}.document{color:#172b4d}.notice{border-color:#a9dfd8;background:#eaf7f5;color:#123f40}.toolbar a:focus-visible,.toolbar button:focus-visible{outline:2px solid #0f766e;outline-offset:2px}@media(max-width:600px){.document{margin:0;padding:20px}.toolbar{margin:0;padding:12px;flex-wrap:wrap}.grid{grid-template-columns:1fr;gap:16px}.table-wrap{overflow-x:auto}table{min-width:600px}}@media print{body{background:#fff}.document{max-width:none;margin:0;padding:0}.toolbar{display:none}.table-wrap{overflow:visible}table{min-width:0}}
    </style>
</head>
<body>
    <div class="toolbar"><a href="{{ route('facturacion.facturas.show', $invoice) }}">Volver</a><button type="button" onclick="window.print()">Imprimir / Guardar como PDF</button></div>
    <main class="document">
        <h1>Comprobante de facturación</h1>
        <p class="muted">{{ config('app.name') }} · {{ $invoice->status->label() }}</p>
        <div class="notice">Comprobante administrativo interno. No constituye una factura fiscal oficial ni un documento tributario electrónico.</div>
        <div class="grid">
            <div><div class="label">Número</div><div class="value">{{ $invoice->number }}</div></div>
            <div><div class="label">Fecha de emisión</div><div class="value">{{ $invoice->issue_date->format('d/m/Y') }}</div></div>
            <div><div class="label">Paciente</div><div class="value">{{ $invoice->patient->first_name }} {{ $invoice->patient->last_name }}</div><div>{{ $invoice->patient->record_number }}</div></div>
            <div><div class="label">Contacto</div><div class="value">{{ $invoice->patient->phone ?: 'Sin teléfono registrado' }}</div><div>{{ $invoice->patient->email ?: 'Sin correo registrado' }}</div></div>
        </div>
        <div class="table-wrap"><table>
            <thead><tr><th>Procedimiento realizado</th><th class="num">Cantidad</th><th class="num">Precio unitario</th><th class="num">Subtotal</th></tr></thead>
            <tbody>
                @foreach ($invoice->details as $detail)
                    <tr><td>{{ $detail->description }}</td><td class="num">{{ $detail->quantity }}</td><td class="num">Q {{ number_format((float) $detail->unit_price, 2) }}</td><td class="num">Q {{ number_format((float) $detail->subtotal, 2) }}</td></tr>
                @endforeach
            </tbody>
        </table></div>
        <div class="totals">
            <div class="total-row"><span>Subtotal</span><span>Q {{ number_format((float) $invoice->subtotal, 2) }}</span></div>
            <div class="total-row"><span>Descuento</span><span>Q {{ number_format((float) $invoice->discount, 2) }}</span></div>
            <div class="total-row"><span>Impuestos</span><span>Q {{ number_format((float) $invoice->tax_amount, 2) }}</span></div>
            <div class="total-row grand-total"><span>Total</span><span>Q {{ number_format((float) $invoice->total, 2) }}</span></div>
            <div class="total-row balance"><span>Saldo pendiente</span><span>Q {{ number_format((float) $invoice->balance_due, 2) }}</span></div>
        </div>
        <p class="footer">Documento de consulta e impresión interna. No incluye observaciones clínicas.</p>
    </main>
</body>
</html>
