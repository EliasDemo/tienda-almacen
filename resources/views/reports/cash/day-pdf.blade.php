<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte del Día</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; color: #333; }
        h1 { font-size: 18px; text-align: center; }
        .subtitle { text-align: center; color: #666; font-size: 11px; margin-bottom: 15px; }
        .summary { background: #f0f0f0; padding: 10px; margin-bottom: 15px; }
        .summary td { padding: 3px 8px; }
        .caja-header { background: #2563eb; color: white; padding: 8px; margin-top: 15px; font-size: 12px; font-weight: bold; }
        .caja-info { background: #f8f8f8; padding: 8px; margin-bottom: 5px; font-size: 10px; }
        table.sales { width: 100%; border-collapse: collapse; }
        table.sales th { background: #e5e7eb; padding: 4px 6px; text-align: left; font-size: 9px; }
        table.sales td { padding: 3px 6px; font-size: 9px; border-bottom: 1px solid #eee; }
    </style>
</head>
<body>
    <h1>Reporte del Día: {{ date('d/m/Y', strtotime($date)) }}</h1>
    <p class="subtitle">Sistema de Ventas</p>

    <div class="summary">
        <table>
            <tr>
                <td>Total cajas: <strong>{{ $summary['total_registers'] }}</strong></td>
                <td>Total ventas: <strong>{{ $summary['sales_count'] }}</strong></td>
                <td>Monto: <strong>S/ {{ number_format($summary['total_sales'], 2) }}</strong></td>
                <td>Efectivo: <strong>S/ {{ number_format($summary['total_cash'], 2) }}</strong></td>
                <td>Otros: <strong>S/ {{ number_format($summary['total_other'], 2) }}</strong></td>
            </tr>
        </table>
    </div>

    @foreach($registers as $r)
    <div class="caja-header">
        {{ $r->user->name }} — {{ $r->opened_at->format('H:i') }} a {{ $r->closed_at?->format('H:i') ?? '—' }}
        — Ventas: S/ {{ number_format($r->total_sales, 2) }}
        — Diferencia: S/ {{ number_format($r->difference, 2) }}
    </div>

    <table class="sales">
        <thead>
            <tr>
                <th>N° Venta</th>
                <th>Producto</th>
                <th>Cant.</th>
                <th>Precio</th>
                <th>Subtotal</th>
                <th>Pago</th>
                <th>Hora</th>
            </tr>
        </thead>
        <tbody>
            @foreach($r->sales as $sale)
                @foreach($sale->items as $item)
                <tr>
                    <td>{{ $sale->sale_number }}</td>
                    <td>{{ $item->variant->product->name }}</td>
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
    @endforeach
</body>
</html>