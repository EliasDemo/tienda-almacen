<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Boleta Final {{ $order->request_code }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; max-width: 21cm; margin: 0 auto; padding: 15px; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 10px; }
        .header h1 { font-size: 18px; }
        .section { margin-bottom: 12px; }
        .section-title { font-weight: bold; font-size: 13px; border-bottom: 1px solid #ccc; padding-bottom: 3px; margin-bottom: 6px; }
        .row { display: flex; justify-content: space-between; margin-bottom: 3px; }
        .row .label { color: #666; }
        .row .value { font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { padding: 5px 8px; text-align: left; border-bottom: 1px solid #eee; font-size: 11px; }
        th { background: #f5f5f5; font-weight: bold; }
        .total-box { background: #f0f0f0; border: 2px solid #000; border-radius: 4px; padding: 12px; text-align: center; margin: 10px 0; }
        .total-box .amount { font-size: 24px; font-weight: bold; }
        .payments-box { background: #f0fdf4; border: 1px solid #16a34a; border-radius: 4px; padding: 10px; margin: 8px 0; }
        .footer { text-align: center; margin-top: 15px; font-size: 10px; color: #999; border-top: 1px solid #ccc; padding-top: 8px; }
        @media print { .no-print { display: none; } body { padding: 0; } }
    </style>
</head>
<body>

    <div class="no-print" style="padding: 10px; text-align: center; margin-bottom: 15px;">
        <button onclick="window.print()" style="padding: 10px 30px; font-size: 14px; cursor: pointer; background: #2563eb; color: white; border: none; border-radius: 8px;">
            Imprimir Boleta Final
        </button>
        <a href="{{ route('pos.orders.show', $order) }}" style="margin-left: 10px; color: #2563eb;">← Volver</a>
    </div>

    <div class="header">
        <h1>BOLETA DE ENTREGA</h1>
        <p>{{ $order->request_code }}</p>
    </div>

    <div class="section">
        <div class="row"><span class="label">Cliente:</span><span class="value">{{ $order->customer->name }}</span></div>
        @if($order->customer->phone)
        <div class="row"><span class="label">Teléfono:</span><span class="value">{{ $order->customer->phone }}</span></div>
        @endif
        <div class="row"><span class="label">Fecha pedido:</span><span class="value">{{ $order->created_at->format('d/m/Y H:i') }}</span></div>
        <div class="row"><span class="label">Fecha entrega:</span><span class="value">{{ $order->delivered_at?->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i') }}</span></div>
        <div class="row"><span class="label">Atendido por:</span><span class="value">{{ $order->user->name }}</span></div>
    </div>

    <div class="section">
        <div class="section-title">DETALLE DE PRODUCTOS (PESO REAL)</div>
        <table>
            <thead>
                <tr>
                    <th>Producto</th>
                    <th style="text-align:center;">Cant. Pedida</th>
                    <th style="text-align:center;">Peso Real</th>
                    <th style="text-align:right;">Precio</th>
                    <th style="text-align:right;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                @php
                    $realQty = (float) $item->quantity_sent;
                    $subtotal = $realQty > 0 ? round($realQty * (float) $item->sale_price, 2) : 0;
                @endphp
                <tr>
                    <td>{{ $item->variant->product->name }} — {{ $item->variant->name }}</td>
                    <td style="text-align:center;">{{ (int) $item->quantity_requested }} {{ $item->package_type ?? 'saco' }}(s)</td>
                    <td style="text-align:center; font-weight:bold;">
                        @if($realQty > 0)
                        {{ number_format($realQty, 3) }} {{ $item->unit }}
                        @else
                        —
                        @endif
                    </td>
                    <td style="text-align:right;">S/ {{ number_format($item->sale_price, 2) }}/{{ $item->unit }}</td>
                    <td style="text-align:right; font-weight:bold;">S/ {{ number_format($subtotal, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="total-box">
        <p style="font-size:11px; color:#666;">TOTAL</p>
        <p class="amount">S/ {{ number_format($order->real_total, 2) }}</p>
    </div>

    <div class="payments-box">
        <div class="section-title" style="border-bottom:none; margin-bottom:4px;">PAGOS REALIZADOS</div>
        @foreach($order->payments as $payment)
        <div class="row">
            <span class="label">
                {{ $payment->payment_type === 'advance' ? 'Adelanto' : 'Pago final' }}
                ({{ ucfirst($payment->method) }})
                — {{ $payment->created_at->format('d/m/Y H:i') }}
            </span>
            <span class="value">S/ {{ number_format($payment->amount, 2) }}</span>
        </div>
        @endforeach
        <div style="border-top: 1px solid #16a34a; margin-top: 6px; padding-top: 6px;">
            <div class="row">
                <span class="label"><strong>Total pagado:</strong></span>
                <span class="value" style="color: #16a34a;">S/ {{ number_format($order->total_paid, 2) }}</span>
            </div>
            @if((float) $order->remaining_amount > 0)
            <div class="row">
                <span class="label"><strong>Saldo pendiente:</strong></span>
                <span class="value" style="color: #dc2626;">S/ {{ number_format($order->remaining_amount, 2) }}</span>
            </div>
            @endif
        </div>
    </div>

    <div class="footer">
        <p>Boleta de entrega — Pedido completado</p>
        <p>Gracias por su preferencia.</p>
    </div>

    <script>window.onload = function() { window.print(); };</script>
</body>
</html>