<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte de Caja</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; }
        h1 { font-size: 18px; text-align: center; margin-bottom: 5px; }
        .subtitle { text-align: center; color: #666; font-size: 11px; margin-bottom: 20px; }
        .info-grid { width: 100%; margin-bottom: 15px; }
        .info-grid td { padding: 3px 8px; font-size: 11px; }
        .info-grid .label { color: #666; width: 150px; }
        .info-grid .value { font-weight: bold; }
        .summary { background: #f8f8f8; padding: 10px; border-radius: 5px; margin-bottom: 20px; }
        .summary table { width: 100%; }
        .summary td { padding: 4px 8px; }
        .summary .total { font-size: 14px; font-weight: bold; border-top: 2px solid #333; padding-top: 8px; }
        .diff-ok { color: #16a34a; }
        .diff-bad { color: #dc2626; }
        table.sales { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.sales th { background: #e5e7eb; padding: 6px 8px; text-align: left; font-size: 10px; border-bottom: 2px solid #ccc; }
        table.sales td { padding: 5px 8px; font-size: 10px; border-bottom: 1px solid #eee; }
        .section-title { font-size: 14px; font-weight: bold; margin-top: 20px; margin-bottom: 5px; border-bottom: 1px solid #ccc; padding-bottom: 5px; }
    </style>
</head>
<body>
    <h1>Reporte de Caja</h1>
    <p class="subtitle">Sistema de Ventas</p>

    <table class="info-grid">
        <tr>
            <td class="label">Cajero:</td>
            <td class="value">{{ $register->user->name }}</td>
            <td class="label">Fecha:</td>
            <td class="value">{{ $register->opened_at->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td class="label">Apertura:</td>
            <td class="value">{{ $register->opened_at->format('H:i') }}</td>
            <td class="label">Cierre:</td>
            <td class="value">{{ $register->closed_at?->format('H:i') ?? '—' }}</td>
        </tr>
    </table>

    <div class="summary">
        <table>
            <tr>
                <td class="label">Fondo inicial:</td>
                <td>S/ {{ number_format($register->opening_amount, 2) }}</td>
            </tr>
            <tr>
                <td class="label">Total ventas:</td>
                <td>S/ {{ number_format($register->total_sales, 2) }}</td>
            </tr>
            <tr>
                <td class="label">Efectivo neto:</td>
                <td>S/ {{ number_format($register->total_cash, 2) }}</td>
            </tr>
            <tr>
                <td class="label">Otros medios:</td>
                <td>S/ {{ number_format($register->total_other, 2) }}</td>
            </tr>
            <tr>
                <td class="label">Efectivo esperado:</td>
                <td><strong>S/ {{ number_format($expectedCash, 2) }}</strong></td>
            </tr>
            <tr>
                <td class="label">Efectivo contado:</td>
                <td><strong>S/ {{ number_format($register->closing_amount, 2) }}</strong></td>
            </tr>
            <tr>
                <td class="label total">Diferencia:</td>
                <td class="total {{ (float)$register->difference >= 0 ? 'diff-ok' : 'diff-bad' }}">
                    S/ {{ number_format($register->difference, 2) }}
                    {{ (float)$register->difference > 0 ? '(sobrante)' : '' }}
                    {{ (float)$register->difference < 0 ? '(faltante)' : '' }}
                    {{ (float)$register->difference == 0 ? '(cuadra)' : '' }}
                </td>
            </tr>
        </table>
    </div>

    <p class="section-title">Detalle de Ventas ({{ $register->sales->count() }})</p>

    <table class="sales">
        <thead>
            <tr>
                <th>N° Venta</th>
                <th>Cliente</th>
                <th>Producto</th>
                <th>Cant.</th>
                <th>Precio</th>
                <th>Subtotal</th>
                <th>Pago</th>
                <th>Hora</th>
            </tr>
        </thead>
        <tbody>
            @foreach($register->sales as $sale)
                @foreach($sale->items as $item)
                <tr>
                    <td>{{ $sale->sale_number }}</td>
                    <td>{{ $sale->customer?->name ?? '—' }}</td>
                    <td>{{ $item->variant->product->name }} - {{ $item->variant->name }}</td>
                    <td>{{ number_format($item->quantity, 3) }} {{ $item->unit }}</td>
                    <td>S/ {{ number_format($item->unit_price, 2) }}</td>
                    <td>S/ {{ number_format($item->subtotal, 2) }}</td>
                    <td>{{ $sale->payments->map(fn($p) => ucfirst($p->method))->join(', ') }}</td>
                    <td>{{ $sale->created_at->format('H:i') }}</td>
                </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>
</body>
</html>