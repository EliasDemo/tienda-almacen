@extends('layouts.app')

@section('title', 'Reporte de Caja')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h3 class="text-lg font-semibold mb-4 text-center">Reporte de Caja</h3>

        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <p class="text-xs text-gray-500">Cajero</p>
                <p class="font-medium">{{ $register->user->name }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Fecha</p>
                <p class="font-medium">{{ $register->opened_at->format('d/m/Y') }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Apertura</p>
                <p class="font-medium">{{ $register->opened_at->format('H:i') }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Cierre</p>
                <p class="font-medium">{{ $register->closed_at?->format('H:i') ?? '—' }}</p>
            </div>
        </div>

        @php
            $expectedCash = (float) $register->opening_amount + (float) $register->total_cash;
        @endphp

        <div class="space-y-2 border-t pt-4">
            <div class="flex justify-between text-sm">
                <span>Fondo inicial:</span>
                <span class="font-medium">S/ {{ number_format($register->opening_amount, 2) }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span>Total ventas:</span>
                <span class="font-medium">S/ {{ number_format($register->total_sales, 2) }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span>Efectivo neto de ventas:</span>
                <span class="font-medium text-green-600">S/ {{ number_format($register->total_cash, 2) }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span>Otros medios:</span>
                <span class="font-medium">S/ {{ number_format($register->total_other, 2) }}</span>
            </div>
            <div class="flex justify-between text-sm border-t pt-2">
                <span>Efectivo esperado:</span>
                <span class="font-bold">S/ {{ number_format($expectedCash, 2) }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span>Efectivo contado:</span>
                <span class="font-bold">S/ {{ number_format($register->closing_amount, 2) }}</span>
            </div>
            <div class="flex justify-between text-sm border-t pt-2">
                <span>Diferencia:</span>
                <span class="font-bold text-lg {{ (float)$register->difference >= 0 ? 'text-green-600' : 'text-red-600' }}">
                    S/ {{ number_format($register->difference, 2) }}
                    {{ (float)$register->difference > 0 ? '(sobrante)' : '' }}
                    {{ (float)$register->difference < 0 ? '(faltante)' : '' }}
                    {{ (float)$register->difference == 0 ? '(cuadra)' : '' }}
                </span>
            </div>
        </div>
    </div>

    {{-- Detalle de ventas --}}
    <div class="bg-white rounded-lg shadow">
        <div class="p-4 border-b">
            <h4 class="font-semibold">Ventas ({{ $register->sales->where('status', 'completed')->count() }})</h4>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-left">
                    <tr>
                        <th class="px-4 py-2 text-gray-600 font-medium text-xs">N° Venta</th>
                        <th class="px-4 py-2 text-gray-600 font-medium text-xs">Productos</th>
                        <th class="px-4 py-2 text-gray-600 font-medium text-xs">Total</th>
                        <th class="px-4 py-2 text-gray-600 font-medium text-xs">Pago</th>
                        <th class="px-4 py-2 text-gray-600 font-medium text-xs">Hora</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach($register->sales->where('status', 'completed') as $sale)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 font-mono text-xs">{{ $sale->sale_number }}</td>
                        <td class="px-4 py-2 text-xs">
                            @foreach($sale->items as $item)
                                <div>{{ $item->variant->product->name }} ({{ number_format($item->quantity, 3) }} {{ $item->unit }})</div>
                            @endforeach
                        </td>
                        <td class="px-4 py-2 font-medium">S/ {{ number_format($sale->total, 2) }}</td>
                        <td class="px-4 py-2 text-xs">
                            @foreach($sale->payments as $p)
                                <div>
                                    <span class="capitalize">{{ $p->method }}</span>:
                                    S/ {{ number_format($p->amount, 2) }}
                                </div>
                            @endforeach
                        </td>
                        <td class="px-4 py-2 text-xs">{{ $sale->created_at->format('H:i') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4 text-center">
        <a href="{{ route('pos.open-register') }}" class="text-blue-600 hover:underline text-sm">Abrir nueva caja</a>
    </div>
</div>
@endsection
