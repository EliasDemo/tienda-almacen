@extends('layouts.app')

@section('title', 'Caja - ' . $register->user->name)

@section('content')
<div class="max-w-5xl mx-auto">

    {{-- Resumen --}}
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex justify-between items-start mb-4">
            <div>
                <h3 class="text-lg font-semibold">{{ $register->user->name }}</h3>
                <p class="text-sm text-gray-500">
                    {{ $register->opened_at->format('d/m/Y') }} —
                    {{ $register->opened_at->format('H:i') }} a {{ $register->closed_at?->format('H:i') ?? '—' }}
                </p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('reports.cash.export-excel', $register) }}"
                   class="bg-green-600 text-white px-3 py-1.5 rounded-lg hover:bg-green-700 text-xs">Excel</a>
                <a href="{{ route('reports.cash.export-pdf', $register) }}"
                   class="bg-red-600 text-white px-3 py-1.5 rounded-lg hover:bg-red-700 text-xs">PDF</a>
                <a href="{{ route('reports.cash.day', $register->opened_at->format('Y-m-d')) }}"
                   class="text-blue-600 hover:underline text-sm ml-2">← Volver</a>
            </div>
        </div>

        @php
            $expectedCash = (float) $register->opening_amount + (float) $register->total_cash;
        @endphp

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-gray-50 rounded-lg p-3">
                <p class="text-xs text-gray-500">Fondo inicial</p>
                <p class="font-bold">S/ {{ number_format($register->opening_amount, 2) }}</p>
            </div>
            <div class="bg-green-50 rounded-lg p-3">
                <p class="text-xs text-green-600">Total ventas</p>
                <p class="font-bold text-green-700">S/ {{ number_format($register->total_sales, 2) }}</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
                <p class="text-xs text-gray-500">Efectivo neto</p>
                <p class="font-bold">S/ {{ number_format($register->total_cash, 2) }}</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
                <p class="text-xs text-gray-500">Otros medios</p>
                <p class="font-bold">S/ {{ number_format($register->total_other, 2) }}</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
                <p class="text-xs text-gray-500">Efectivo esperado</p>
                <p class="font-bold">S/ {{ number_format($expectedCash, 2) }}</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
                <p class="text-xs text-gray-500">Efectivo contado</p>
                <p class="font-bold">S/ {{ number_format($register->closing_amount, 2) }}</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-3 col-span-2">
                <p class="text-xs text-gray-500">Diferencia</p>
                <p class="font-bold text-xl {{ (float)$register->difference >= 0 ? 'text-green-600' : 'text-red-600' }}">
                    S/ {{ number_format($register->difference, 2) }}
                    {{ (float)$register->difference > 0 ? '(sobrante)' : '' }}
                    {{ (float)$register->difference < 0 ? '(faltante)' : '' }}
                    {{ (float)$register->difference == 0 ? '(cuadra)' : '' }}
                </p>
            </div>
        </div>
    </div>

    {{-- Lista de ventas --}}
    <div class="bg-white rounded-lg shadow">
        <div class="p-4 border-b">
            <h4 class="font-semibold">Ventas ({{ $register->sales->count() }})</h4>
        </div>

        <div class="divide-y">
            @foreach($register->sales as $sale)
            <a href="{{ route('reports.cash.sale', $sale) }}"
               class="flex items-center justify-between p-4 hover:bg-gray-50 transition-colors">
                <div class="flex-1">
                    <div class="flex items-center gap-3">
                        <span class="font-mono text-xs text-gray-600">{{ $sale->sale_number }}</span>
                        @if($sale->customer)
                        <span class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded text-xs">{{ $sale->customer->name }}</span>
                        @endif
                    </div>
                    <div class="mt-1">
                        @foreach($sale->items as $item)
                        <span class="text-xs text-gray-500">
                            {{ $item->variant->product->name }}
                            ({{ number_format($item->quantity, 3) }} {{ $item->unit }})
                            @if(!$loop->last) — @endif
                        </span>
                        @endforeach
                    </div>
                </div>

                <div class="flex items-center gap-6">
                    <div class="text-right">
                        <p class="font-bold">S/ {{ number_format($sale->total, 2) }}</p>
                        <p class="text-xs text-gray-500">
                            {{ $sale->payments->map(fn($p) => ucfirst($p->method))->join(', ') }}
                        </p>
                    </div>
                    <div class="text-xs text-gray-400">{{ $sale->created_at->format('H:i') }}</div>
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </div>
            </a>
            @endforeach
        </div>
    </div>

</div>
@endsection