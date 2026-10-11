<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nota de crédito {{ $creditNote->number }} | {{ config('app.name') }}</title>
    <style>
        body{font-family:Arial,sans-serif;color:#0f172a;margin:0;background:#f1f5f9}.document{max-width:700px;margin:32px auto;padding:40px;background:#fff}.toolbar{max-width:700px;margin:20px auto;display:flex;justify-content:flex-end;gap:12px}.toolbar a,.toolbar button{border:1px solid #cbd5e1;background:#fff;border-radius:6px;padding:10px 16px;color:#0f172a;text-decoration:none;font-weight:600;cursor:pointer}h1{margin:0 0 8px;font-size:28px}.muted{color:#64748b}.row{display:flex;justify-content:space-between;gap:20px;border-bottom:1px solid #e2e8f0;padding:14px 0}.label{color:#64748b}.amount{margin-top:28px;text-align:right;font-size:24px;font-weight:700}.reason{margin-top:28px;white-space:pre-line}@media print{body{background:#fff}.document{max-width:none;margin:0;padding:0}.toolbar{display:none}}
    </style>
</head>
<body>
    <div class="toolbar"><a href="{{ route('notas-credito.show', $creditNote) }}">Volver</a><button type="button" onclick="window.print()">Imprimir / Guardar como PDF</button></div>
    <main class="document">
        <h1>Nota de crédito</h1>
        <p class="muted">{{ config('app.name') }} · {{ $creditNote->status->label() }}</p>
        <div class="row"><span class="label">Número</span><strong>{{ $creditNote->number }}</strong></div>
        <div class="row"><span class="label">Fecha de emisión</span><strong>{{ $creditNote->issue_date->format('Y-m-d') }}</strong></div>
        <div class="row"><span class="label">Cliente</span><strong>{{ $creditNote->invoice->sale->customer->name }}</strong></div>
        <div class="row"><span class="label">Factura</span><strong>{{ $creditNote->invoice->number }}</strong></div>
        <div class="amount">Monto acreditado: Q {{ number_format((float) $creditNote->amount, 2) }}</div>
        <div class="reason"><strong>Motivo</strong><br>{{ $creditNote->reason }}</div>
    </main>
</body>
</html>
