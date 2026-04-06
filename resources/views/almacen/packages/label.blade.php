<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $package->lot->variant->product->name }} — {{ $package->gross_weight ? number_format($package->gross_weight, 3).'kg' : $package->unit_count.'und' }}</title>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
    <style>
        @page {
            size: 10cm 6cm;
            margin: 0;
        }
 
        * { margin: 0; padding: 0; box-sizing: border-box; }
 
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #e5e7eb;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
 
        /* ══════ ETIQUETA ══════ */
        .label {
            width: 10cm;
            height: 6cm;
            background: #fff;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
 
        /* ── Cuerpo principal ── */
        .body {
            flex: 1;
            display: flex;
            padding: 7px 10px 5px;
            gap: 10px;
        }
 
        /* Columna izquierda: QR */
        .col-qr {
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 2px;
        }
 
        .col-qr svg { display: block; }
 
        /* Columna derecha: info */
        .col-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 4px;
            min-width: 0;
        }
 
        /* Producto */
        .product {
            font-size: 12px;
            font-weight: 800;
            color: #000;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            line-height: 1.15;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
 
        .variant {
            font-size: 10px;
            font-weight: 400;
            color: #444;
            text-transform: none;
            letter-spacing: 0;
        }
 
        /* Badge pedido */
        .order {
            background: #000;
            color: #fff;
            border-radius: 3px;
            padding: 4px 7px;
            display: flex;
            flex-direction: column;
            gap: 1px;
        }
 
        .order-client {
            font-size: 11px;
            font-weight: 900;
            letter-spacing: 0.3px;
        }
 
        .order-detail {
            font-size: 7.5px;
            color: #aaa;
            display: flex;
            gap: 6px;
        }
 
        .order-detail b { color: #ddd; }
 
        /* Peso */
        .weight {
            border: 2.5px solid #000;
            border-radius: 4px;
            text-align: center;
            padding: 4px 6px;
            background: #fff;
            line-height: 1;
        }
 
        .weight-num {
            font-size: 46px;
            font-weight: 900;
            color: #000;
            letter-spacing: -2px;
            font-variant-numeric: tabular-nums;
        }
 
        .weight-unit {
            font-size: 15px;
            font-weight: 700;
            color: #333;
            margin-left: 2px;
        }
 
        /* ── Barra inferior: código de barras ── */
        .bar {
            border-top: 1.5px solid #000;
            padding: 3px 10px 4px;
            display: flex;
            align-items: center;
            gap: 8px;
            background: #fafafa;
        }
 
        .bar svg {
            flex: 1;
            display: block;
            height: 22px;
        }
 
        .bar-id {
            font-size: 7px;
            font-family: 'Courier New', monospace;
            color: #555;
            letter-spacing: 0.5px;
            white-space: nowrap;
            flex-shrink: 0;
        }
 
        /* ══════ BOTONES ══════ */
        .actions {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }
 
        .btn {
            padding: 10px 24px;
            font-size: 13px;
            font-weight: 700;
            border: 2px solid #111;
            border-radius: 50px;
            cursor: pointer;
            font-family: inherit;
            transition: 0.15s;
        }
 
        .btn-print { background: #111; color: #fff; }
        .btn-print:hover { background: #333; }
        .btn-close { background: #fff; color: #111; }
        .btn-close:hover { background: #f3f3f3; }
 
        /* ══════ IMPRESIÓN ══════ */
        @media print {
            body { background: #fff; min-height: auto; }
            .actions { display: none !important; }
            .label { border: none; }
            .order { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>
 
    <div class="label">
        <div class="body">
            {{-- QR --}}
            <div class="col-qr">
                {!! QrCode::size(90)->margin(0)->generate($package->uuid) !!}
            </div>
 
            {{-- Info --}}
            <div class="col-info">
                {{-- Nombre --}}
                <div class="product">
                    {{ $package->lot->variant->product->name }}
                    <span class="variant">{{ $package->lot->variant->name }}</span>
                </div>
 
                {{-- Badge pedido (solo si for_order) --}}
                @if($package->for_order)
                    @php $ord = $package->transferLine?->transfer?->stockRequestOrder; @endphp
                    @if($ord)
                    <div class="order">
                        <div class="order-client">&#9733; {{ $ord->customer->name ?? 'Cliente' }}</div>
                        <div class="order-detail">
                            <b>{{ $ord->request_code }}</b>
                            @if($ord->delivery_date)
                            <span>Entrega {{ $ord->delivery_date->format('d/m/Y') }}</span>
                            @endif
                        </div>
                    </div>
                    @endif
                @endif
 
                {{-- Peso --}}
                <div class="weight">
                    <span class="weight-num">{{ $package->gross_weight ? number_format($package->gross_weight, 3) : $package->unit_count }}</span>
                    <span class="weight-unit">{{ $package->gross_weight ? 'kg' : 'und' }}</span>
                </div>
            </div>
        </div>
 
        {{-- Código de barras --}}
        <div class="bar">
            <svg id="barcode"></svg>
            <span class="bar-id">{{ Str::limit($package->uuid, 16, '') }}</span>
        </div>
    </div>
 
    <div class="actions">
        <button class="btn btn-print" onclick="window.print()">Imprimir</button>
        <button class="btn btn-close" onclick="window.close()">Cerrar</button>
    </div>
 
    <script>
        JsBarcode("#barcode", "{{ Str::limit($package->uuid, 20, '') }}", {
            format: "CODE128",
            width: 1.3,
            height: 20,
            displayValue: false,
            margin: 0,
            background: "transparent",
            lineColor: "#000"
        });
 
        // Auto-imprimir y cerrar
        window.onload = function() {
            setTimeout(function() {
                window.print();
                // Cerrar automáticamente después de imprimir (o cancelar)
                setTimeout(function() { window.close(); }, 500);
            }, 300);
        };
    </script>
</body>
</html>