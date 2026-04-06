<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Boleta Pedido {{ $order->request_code }}</title>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; max-width: 21cm; margin: 0 auto; padding: 15px; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 10px; }
        .header h1 { font-size: 18px; }
        .header p { font-size: 11px; color: #666; }
        .section { margin-bottom: 12px; }
        .section-title { font-weight: bold; font-size: 13px; border-bottom: 1px solid #ccc; padding-bottom: 3px; margin-bottom: 6px; }
        .row { display: flex; justify-content: space-between; margin-bottom: 3px; }
        .row .label { color: #666; }
        .row .value { font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { padding: 5px 8px; text-align: left; border-bottom: 1px solid #eee; font-size: 11px; }
        th { background: #f5f5f5; font-weight: bold; }
        .advance-box { background: #f0fdf4; border: 2px solid #16a34a; border-radius: 8px; padding: 12px; text-align: center; margin: 10px 0; }
        .advance-box .amount { font-size: 24px; font-weight: bold; color: #16a34a; }
        .qr-section { text-align: center; margin-top: 15px; }
        .footer { text-align: center; margin-top: 15px; font-size: 10px; color: #999; border-top: 1px solid #ccc; padding-top: 8px; }
        .color-badge { display: inline-block; width: 20px; height: 20px; border-radius: 50%; vertical-align: middle; margin-right: 5px; }
        @media print { .no-print { display: none; } body { padding: 0; } }
    </style>
</head>
<body>

    <div class="no-print" style="padding: 10px; text-align: center; margin-bottom: 15px;">
        <button onclick="window.print()" style="padding: 10px 30px; font-size: 14px; cursor: pointer; background: #2563eb; color: white; border: none; border-radius: 8px;">
            Imprimir Boleta
        </button>
        <a href="{{ route('pos.orders.show', $order) }}" style="margin-left: 10px; color: #2563eb;">← Volver al pedido</a>
    </div>

    <div class="header">
        <h1>COMPROBANTE DE PEDIDO</h1>
        <p>{{ $order->request_code }}</p>
    </div>

    <div class="section">
        <div class="row"><span class="label">Cliente:</span><span class="value">{{ $order->customer->name }}</span></div>
        @if($order->customer->phone)
        <div class="row"><span class="label">Teléfono:</span><span class="value">{{ $order->customer->phone }}</span></div>
        @endif
        <div class="row"><span class="label">Fecha pedido:</span><span class="value">{{ $order->created_at->format('d/m/Y H:i') }}</span></div>
        <div class="row">
            <span class="label">Fecha entrega:</span>
            <span class="value">{{ $order->delivery_date?->format('d/m/Y') ?? '—' }}</span>
        </div>
        <div class="row"><span class="label">Atendido por:</span><span class="value">{{ $order->user->name }}</span></div>
        <div class="row">
            <span class="label">Color etiqueta:</span>
            <span class="value">
                @php
                    $colors = ['rojo'=>'#DC2626','azul'=>'#2563EB','verde'=>'#16A34A','amarillo'=>'#F59E0B'];
                @endphp
                <span class="color-badge" style="background:{{ $colors[$order->label_color] ?? '#999' }};"></span>
                {{ ucfirst($order->label_color) }}
            </span>
        </div>
    </div>

    <div class="section">
        <div class="section-title">PRODUCTOS PEDIDOS</div>
        <table>
            <thead>
                <tr>
                    <th>Producto</th>
                    <th style="text-align:center;">Cantidad</th>
                    <th style="text-align:center;">Empaque</th>
                    <th style="text-align:right;">Precio x {{ '' }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                <tr>
                    <td>{{ $item->variant->product->name }} — {{ $item->variant->name }}</td>
                    <td style="text-align:center; font-weight:bold;">{{ (int) $item->quantity_requested }}</td>
                    <td style="text-align:center;">{{ ucfirst($item->package_type ?? 'saco') }}</td>
                    <td style="text-align:right; font-weight:bold;">S/ {{ number_format($item->sale_price, 2) }} / {{ $item->unit }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <p style="font-size:10px; color:#666;">* El peso y total final se determinan cuando almacén prepare el pedido.</p>
    </div>

    @if((float) $order->advance_amount > 0)
    <div class="advance-box">
        <p style="font-size:11px; color:#666;">ADELANTO RECIBIDO</p>
        <p class="amount">S/ {{ number_format($order->advance_amount, 2) }}</p>
        <p style="font-size:10px; color:#666; margin-top:4px;">
            {{ ucfirst($order->advance_method ?? 'efectivo') }}
            {{ $order->advance_reference ? ' — Ref: '.$order->advance_reference : '' }}
        </p>
    </div>
    @endif

    @if($order->customer_notes)
    <div class="section">
        <div class="section-title">OBSERVACIONES</div>
        <p>{{ $order->customer_notes }}</p>
    </div>
    @endif

    <div class="qr-section">
        {!! QrCode::size(100)->generate($order->uuid) !!}
        <p style="font-size:10px; color:#999; margin-top:4px;">{{ $order->request_code }}</p>
    </div>

    <div class="footer">
        <p>Este comprobante acredita el pedido y adelanto recibido.</p>
        <p>El monto final se calcula al momento de la entrega según peso real.</p>
        <p style="margin-top:8px;">Presentar este comprobante al recoger el pedido.</p>
    </div>

    <script>window.onload = function() { window.print(); };</script>
</body>
</html>