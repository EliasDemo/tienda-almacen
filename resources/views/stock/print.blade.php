<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Stock Tienda — {{ now()->format('d/m/Y H:i') }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Courier New', monospace; font-size: 11px; width: 80mm; margin: 0 auto; color: #000; }
        @page { size: 80mm auto; margin: 2mm; }

        .header { text-align: center; padding: 3mm 0; border-bottom: 2px solid #000; }
        .header h1 { font-size: 15px; font-weight: bold; letter-spacing: 1px; }
        .header .sub { font-size: 10px; margin-top: 1px; }

        .summary { padding: 2mm 0; border-bottom: 1px dashed #000; }
        .summary-item { display: flex; justify-content: space-between; font-size: 10px; padding: 0.3mm 0; }
        .summary-item span:last-child { font-weight: bold; }
        .summary-big { border-top: 1px solid #000; margin-top: 1mm; padding-top: 1mm; font-size: 12px; font-weight: bold; display: flex; justify-content: space-between; }

        .cat-header {
            background: #000; color: #fff;
            padding: 1.5mm 2mm; margin-top: 3mm;
            font-weight: bold; font-size: 11px;
            text-transform: uppercase; letter-spacing: 0.5px;
        }
        .cat-summary {
            font-size: 9px; color: #555;
            padding: 0.5mm 2mm; border-bottom: 1px solid #000;
        }

        .item { padding: 1.5mm 1mm; border-bottom: 1px dotted #aaa; }
        .item-top { display: flex; justify-content: space-between; align-items: baseline; }
        .item-name { font-size: 11px; font-weight: bold; max-width: 55%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .item-stock { font-size: 12px; font-weight: bold; text-align: right; }
        .item-detail { font-size: 9px; color: #444; margin-top: 0.5mm; }

        .tag-urgente { font-size: 8px; font-weight: bold; border: 1px solid #000; padding: 0 3px; background: #000; color: #fff; margin-left: 2px; }
        .tag-bajo { font-size: 8px; font-weight: bold; border: 1px solid #000; padding: 0 3px; margin-left: 2px; }
        .tag-ok { font-size: 8px; color: #888; margin-left: 2px; }

        .bar { height: 2.5mm; background: #ddd; margin-top: 0.8mm; border: 1px solid #aaa; }
        .bar-fill { height: 100%; background: #000; max-width: 100%; }

        .totals { border-top: 2px solid #000; padding: 2mm 0; margin-top: 3mm; }
        .total-row { display: flex; justify-content: space-between; font-size: 10px; padding: 0.3mm 0; }
        .total-row.big { font-size: 13px; font-weight: bold; border-top: 1px solid #000; padding-top: 1.5mm; margin-top: 1mm; }

        .footer { text-align: center; padding: 3mm 0; border-top: 1px dashed #000; margin-top: 2mm; font-size: 9px; }
        .footer .cut { margin-top: 3mm; letter-spacing: 2px; }

        @media print { body { width: 80mm; } .no-print { display: none !important; } }
        @media screen { body { max-width: 80mm; margin: 20px auto; padding: 10px; border: 1px solid #ccc; background: #fff; } }
    </style>
</head>
<body>

    <div class="no-print" style="text-align: center; padding: 8px 0;">
        <button onclick="window.print()" style="background:#000;color:#fff;border:none;padding:8px 24px;font-size:12px;cursor:pointer;border-radius:4px;font-weight:bold;">IMPRIMIR</button>
        <button onclick="window.close()" style="background:#666;color:#fff;border:none;padding:8px 24px;font-size:12px;cursor:pointer;border-radius:4px;margin-left:4px;">CERRAR</button>
    </div>

    <div class="header">
        <h1>STOCK TIENDA</h1>
        <div class="sub">REPORTE PARA DESPACHO</div>
        <div class="sub" style="font-weight:bold;">{{ now()->format('d/m/Y — H:i') }}</div>
    </div>

    @php
        $grouped = $stock->groupBy('category_name');
        $globalSinStock = 0;
        $globalBajo = 0;
        $globalOk = 0;

        function getPriority($item) {
            $isUnit = ($item->sale_unit === 'unit');
            $total = $isUnit ? $item->total_units : $item->total_weight;
            $cerrados = $item->closed_sacos + $item->closed_cajas;

            if ($total <= 0) return 0;
            if ($cerrados == 0 || (!$isUnit && $total < 10) || ($isUnit && $total < 5)) return 1;
            return 2;
        }

        function getPriorityLabel($priority) {
            return match($priority) {
                0 => ['!! LLEVAR', 'tag-urgente'],
                1 => ['! BAJO', 'tag-bajo'],
                default => ['OK', 'tag-ok'],
            };
        }

        foreach ($stock as $item) {
            $p = getPriority($item);
            if ($p === 0) $globalSinStock++;
            elseif ($p === 1) $globalBajo++;
            else $globalOk++;
        }
    @endphp

    <div class="summary">
        <div class="summary-item"><span>!! Sin stock (LLEVAR):</span><span>{{ $globalSinStock }}</span></div>
        <div class="summary-item"><span>!  Stock bajo:</span><span>{{ $globalBajo }}</span></div>
        <div class="summary-item"><span>   Stock ok:</span><span>{{ $globalOk }}</span></div>
        <div class="summary-item"><span>Sacos cerrados:</span><span>{{ $stock->sum('closed_sacos') }}</span></div>
        <div class="summary-item"><span>Cajas cerradas:</span><span>{{ $stock->sum('closed_cajas') }}</span></div>
        <div class="summary-item"><span>Abiertos:</span><span>{{ $stock->sum('opened_packages') }}</span></div>
        <div class="summary-big"><span>PESO TOTAL:</span><span>{{ number_format($totalWeight, 1) }} kg</span></div>
        @if($totalUnits > 0)
        <div class="summary-big" style="border-top:none;padding-top:0;"><span>UNIDADES:</span><span>{{ $totalUnits }}</span></div>
        @endif
    </div>

    @foreach($grouped as $catName => $items)
        @php
            $sorted = $items->sort(function($a, $b) {
                $pA = getPriority($a);
                $pB = getPriority($b);
                if ($pA !== $pB) return $pA <=> $pB;
                $totalA = ($a->sale_unit === 'unit') ? $a->total_units : $a->total_weight;
                $totalB = ($b->sale_unit === 'unit') ? $b->total_units : $b->total_weight;
                return $totalA <=> $totalB;
            });

            $catSinStock = $sorted->filter(fn($i) => getPriority($i) === 0)->count();
            $catBajo = $sorted->filter(fn($i) => getPriority($i) === 1)->count();
            $catOk = $sorted->filter(fn($i) => getPriority($i) === 2)->count();
        @endphp

        <div class="cat-header">{{ $catName }}</div>
        <div class="cat-summary">
            @if($catSinStock > 0) !! {{ $catSinStock }} sin stock @endif
            @if($catBajo > 0) · ! {{ $catBajo }} bajo @endif
            @if($catOk > 0) · {{ $catOk }} ok @endif
        </div>

        @foreach($sorted as $item)
            @php
                $isUnit = ($item->sale_unit === 'unit');
                $total = $isUnit ? $item->total_units : $item->total_weight;
                $priority = getPriority($item);
                [$label, $tagClass] = getPriorityLabel($priority);
                $pMin = ($prices[$item->id] ?? collect())->firstWhere('price_type', 'minorista');

                $detParts = [];
                if ($item->closed_sacos > 0) $detParts[] = $item->closed_sacos . ' saco' . ($item->closed_sacos > 1 ? 's' : '');
                if ($item->closed_cajas > 0) $detParts[] = $item->closed_cajas . ' caja' . ($item->closed_cajas > 1 ? 's' : '');
                if ($item->opened_packages > 0) $detParts[] = $item->opened_packages . ' abierto' . ($item->opened_packages > 1 ? 's' : '');
                $empTxt = implode(', ', $detParts) ?: 'Sin empaques';

                $barRef = $isUnit ? 30 : 50;
                $barPct = min(100, ($total / $barRef) * 100);
            @endphp

            <div class="item">
                <div class="item-top">
                    <span class="item-name">
                        {{ $item->product_name }}
                        <span class="{{ $tagClass }}">{{ $label }}</span>
                    </span>
                    <span class="item-stock">
                        @if($total <= 0)
                            VACIO
                        @elseif($isUnit)
                            {{ $total }} u
                        @else
                            {{ number_format($total, 1) }} kg
                        @endif
                    </span>
                </div>
                <div class="item-detail">
                    {{ $item->variant_name }} · {{ $empTxt }}
                    @if($pMin) · S/{{ number_format($pMin->price, 2) }} @endif
                </div>
                @if($total > 0)
                <div class="bar"><div class="bar-fill" style="width: {{ $barPct }}%"></div></div>
                @endif
            </div>
        @endforeach
    @endforeach

    <div class="totals">
        <div class="total-row" style="font-weight:bold;font-size:11px;border-bottom:1px solid #000;padding-bottom:1mm;margin-bottom:1mm;">
            <span>RESUMEN DESPACHO</span><span></span>
        </div>
        <div class="total-row"><span>!! Llevar urgente:</span><span style="font-weight:bold;">{{ $globalSinStock }}</span></div>
        <div class="total-row"><span>!  Prioridad:</span><span style="font-weight:bold;">{{ $globalBajo }}</span></div>
        <div class="total-row"><span>   Ok por ahora:</span><span>{{ $globalOk }}</span></div>
        <div class="total-row"><span>Sacos:</span><span>{{ $stock->sum('closed_sacos') }}</span></div>
        <div class="total-row"><span>Cajas:</span><span>{{ $stock->sum('closed_cajas') }}</span></div>
        <div class="total-row"><span>Abiertos:</span><span>{{ $stock->sum('opened_packages') }}</span></div>
        @if($totalUnits > 0)
        <div class="total-row"><span>Unidades:</span><span>{{ $totalUnits }}</span></div>
        @endif
        <div class="total-row big"><span>PESO TIENDA:</span><span>{{ number_format($totalWeight, 1) }} kg</span></div>
    </div>

    <div class="footer">
        <div>{{ config('app.name', 'Sistema Ventas') }}</div>
        <div>{{ now()->format('d/m/Y H:i:s') }}</div>
        <div style="margin-top:1mm;">!! = sin stock · ! = bajo · OK = suficiente</div>
        <div class="cut">- - - - - - - - - - - - - - -</div>
    </div>

    <script>window.onload = function() { setTimeout(function() { window.print(); }, 300); }</script>
</body>
</html>