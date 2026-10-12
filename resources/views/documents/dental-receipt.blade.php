<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recibo {{ $payment->number }} | {{ config('app.name') }}</title>
    <style>
        body{font-family:Arial,sans-serif;color:#17211c;margin:0;background:#f4f7f5}.document{max-width:700px;margin:32px auto;padding:40px;background:#fff}.toolbar{max-width:700px;margin:20px auto;display:flex;justify-content:flex-end;gap:12px}.toolbar a,.toolbar button{border:1px solid #cbd5e1;background:#fff;border-radius:6px;padding:10px 16px;color:#17211c;text-decoration:none;font-weight:600;cursor:pointer}h1{margin:0 0 8px;font-size:28px}.muted{color:#64748b}.notice{margin:18px 0;padding:12px;border:1px solid #dbe7df;background:#f6faf7;color:#3f594a;font-size:12px}.row{display:flex;justify-content:space-between;gap:20px;border-bottom:1px solid #e2e8f0;padding:14px 0}.label{color:#64748b}.amount{margin-top:28px;text-align:right;font-size:24px;font-weight:700}.status{margin-top:18px;font-weight:700;color:#9f1239}@media print{body{background:#fff}.document{max-width:none;margin:0;padding:0}.toolbar{display:none}}
    </style>
</head>
<body>
    <div class="toolbar"><a href="{{ route('facturacion.pagos.show', $payment) }}">Volver</a><button type="button" onclick="window.print()">Imprimir / Guardar como PDF</button></div>
    <main class="document">
        <h1>Recibo de pago</h1>
        <p class="muted">{{ config('app.name') }}</p>
        <div class="notice">Recibo administrativo interno. No constituye un documento tributario electrónico.</div>
        <div class="row"><span class="label">Número de recibo</span><strong>{{ $payment->number }}</strong></div>
        <div class="row"><span class="label">Paciente</span><strong>{{ $payment->invoice->patient->first_name }} {{ $payment->invoice->patient->last_name }}</strong></div>
        <div class="row"><span class="label">Factura</span><strong>{{ $payment->invoice->number }}</strong></div>
        <div class="row"><span class="label">Fecha de pago</span><strong>{{ $payment->payment_date->format('d/m/Y') }}</strong></div>
        <div class="row"><span class="label">Método</span><strong>{{ $payment->method->label() }}</strong></div>
        @if ($payment->reference)
            <div class="row"><span class="label">Referencia</span><strong>{{ $payment->reference }}</strong></div>
        @endif
        <div class="amount">Recibido: Q {{ number_format((float) $payment->amount, 2) }}</div>
        @if ($payment->status === \App\Enums\DentalPaymentStatus::Cancelled)
            <p class="status">Pago anulado · {{ $payment->cancellation_reason }}</p>
        @endif
    </main>
</body>
</html>
