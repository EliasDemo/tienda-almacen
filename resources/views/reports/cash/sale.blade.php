@extends('layouts.app')

@section('title', 'Venta ' . $sale->sale_number)

@section('content')
<div class="max-w-3xl mx-auto">

    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex justify-between items-start mb-4">
            <div>
                <h3 class="text-lg font-semibold">{{ $sale->sale_number }}</h3>
                <p class="text-sm text-gray-500">
                    {{ $sale->created_at->format('d/m/Y H:i') }} — Cajero: {{ $sale->user->name }}
                </p>
            </div>
            <a href="{{ route('reports.cash.show', $sale->cash_register_id) }}"
               class="text-blue-600 hover:underline text-sm">← Volver a la caja</a>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
            <div class="bg-gray-50 rounded-lg p-3">
                <p class="text-xs text-gray-500">Cliente</p>
                <p class="font-medium">{{ $sale->customer?->name ?? 'Sin cliente' }}</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
                <p class="text-xs text-gray-500">Tipo Precio</p>
                <p class="font-medium capitalize">{{ $sale->price_type }}</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
                <p class="text-xs text-gray-500">Tipo Venta</p>
                <p class="font-medium capitalize">{{ $sale->sale_type }}</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
                <p class="text-xs text-gray-500">Estado</p>
                <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full
                    {{ $sale->status === 'completed' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                    {{ $sale->status === 'completed' ? 'Completada' : $sale->status }}
                </span>
            </div>
        </div>
    </div>

    {{-- Productos --}}
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="p-4 border-b">
            <h4 class="font-semibold">Productos</h4>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-left">
                    <tr>
                        <th class="px-4 py-2 text-gray-600 font-medium text-xs">Producto</th>
                        <th class="px-4 py-2 text-gray-600 font-medium text-xs">Modo</th>
                        <th class="px-4 py-2 text-gray-600 font-medium text-xs">Cantidad</th>
                        <th class="px-4 py-2 text-gray-600 font-medium text-xs">Precio Unit.</th>
                        <th class="px-4 py-2 text-gray-600 font-medium text-xs">Subtotal</th>
                        <th class="px-4 py-2 text-gray-600 font-medium text-xs">Bulto</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach($sale->items as $item)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <p class="font-medium">{{ $item->variant->product->name }}</p>
                            <p class="text-xs text-gray-500">{{ $item->variant->name }}</p>
                        </td>
                        <td class="px-4 py-3 text-xs">
                            {{ $item->sell_mode === 'bulk' ? 'Saco completo' : 'Fraccionado' }}
                        </td>
                        <td class="px-4 py-3 font-medium">
                            {{ number_format($item->quantity, 3) }} {{ $item->unit }}
                        </td>
                        <td class="px-4 py-3">S/ {{ number_format($item->unit_price, 2) }}</td>
                        <td class="px-4 py-3 font-bold">S/ {{ number_format($item->subtotal, 2) }}</td>
                        <td class="px-4 py-3 text-xs">
                            @if($item->package)
                                <span class="font-mono">{{ Str::limit($item->package->uuid, 8) }}</span>
                                @if($item->package->lot)
                                    <br><span class="text-gray-400">{{ Str::limit($item->package->lot->lot_code, 15) }}</span>
                                @endif
                            @else
                                <span class="text-gray-400">Auto (FIFO)</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Totales y pagos --}}
    <div class="grid grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow p-4">
            <h4 class="font-semibold mb-3">Totales</h4>
            <div class="space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Subtotal:</span>
                    <span>S/ {{ number_format($sale->subtotal, 2) }}</span>
                </div>
                @if((float)$sale->discount > 0)
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Descuento:</span>
                    <span class="text-red-600">- S/ {{ number_format($sale->discount, 2) }}</span>
                </div>
                @endif
                <div class="flex justify-between font-bold text-lg border-t pt-2">
                    <span>Total:</span>
                    <span>S/ {{ number_format($sale->total, 2) }}</span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-4">
            <h4 class="font-semibold mb-3">Pagos</h4>
            <div class="space-y-2">
                @foreach($sale->payments as $payment)
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500 capitalize">{{ $payment->method }}:</span>
                    <span class="font-medium">S/ {{ number_format($payment->amount, 2) }}</span>
                </div>
                @if($payment->reference)
                <p class="text-xs text-gray-400 text-right">Ref: {{ $payment->reference }}</p>
                @endif
                @endforeach

                @php
                    $totalPaid = $sale->payments->sum('amount');
                    $change = (float)$totalPaid - (float)$sale->total;
                @endphp

                @if($change > 0)
                <div class="flex justify-between text-sm border-t pt-2">
                    <span class="text-yellow-600">Vuelto:</span>
                    <span class="font-medium text-yellow-600">S/ {{ number_format($change, 2) }}</span>
                </div>
                @endif
            </div>
        </div>
    </div>

</div>
@endsection