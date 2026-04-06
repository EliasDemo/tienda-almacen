@extends('layouts.app')

@section('title', 'Cajas del ' . date('d/m/Y', strtotime($date)))

@section('content')
<div class="max-w-5xl mx-auto">

    {{-- Resumen del día --}}
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex justify-between items-start mb-4">
            <div>
                <h3 class="text-lg font-semibold">{{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</h3>
                <p class="text-sm text-gray-500">{{ \Carbon\Carbon::parse($date)->translatedFormat('l') }}</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('reports.cash.export-day-excel', $date) }}"
                   class="bg-green-600 text-white px-3 py-1.5 rounded-lg hover:bg-green-700 text-xs">Excel</a>
                <a href="{{ route('reports.cash.export-day-pdf', $date) }}"
                   class="bg-red-600 text-white px-3 py-1.5 rounded-lg hover:bg-red-700 text-xs">PDF</a>
                <a href="{{ route('reports.cash.index') }}"
                   class="text-blue-600 hover:underline text-sm ml-2">← Volver</a>
            </div>
        </div>

        <div class="grid grid-cols-5 gap-4">
            <div class="bg-gray-50 rounded-lg p-3 text-center">
                <p class="text-xs text-gray-500">Cajas</p>
                <p class="text-2xl font-bold">{{ $summary['total_registers'] }}</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-3 text-center">
                <p class="text-xs text-gray-500">Ventas</p>
                <p class="text-2xl font-bold">{{ $summary['sales_count'] }}</p>
            </div>
            <div class="bg-green-50 rounded-lg p-3 text-center">
                <p class="text-xs text-green-600">Total Vendido</p>
                <p class="text-2xl font-bold text-green-700">S/ {{ number_format($summary['total_sales'], 2) }}</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-3 text-center">
                <p class="text-xs text-gray-500">Efectivo</p>
                <p class="text-2xl font-bold">S/ {{ number_format($summary['total_cash'], 2) }}</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-3 text-center">
                <p class="text-xs text-gray-500">Otros</p>
                <p class="text-2xl font-bold">S/ {{ number_format($summary['total_other'], 2) }}</p>
            </div>
        </div>
    </div>

    {{-- Lista de cajas --}}
    @foreach($registers as $register)
    @php
        $expectedCash = (float) $register->opening_amount + (float) $register->total_cash;
        $salesCount = $register->sales->where('status', 'completed')->count();
    @endphp
    <div class="bg-white rounded-lg shadow mb-4">
        <div class="p-4 flex justify-between items-center">
            <div class="flex items-center gap-4">
                <div class="bg-blue-100 rounded-full p-2">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <div>
                    <p class="font-semibold">{{ $register->user->name }}</p>
                    <p class="text-xs text-gray-500">
                        {{ $register->opened_at->format('H:i') }} — {{ $register->closed_at?->format('H:i') ?? '—' }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-6">
                <div class="text-center">
                    <p class="text-xs text-gray-500">Ventas</p>
                    <p class="font-bold">{{ $salesCount }}</p>
                </div>
                <div class="text-center">
                    <p class="text-xs text-gray-500">Total</p>
                    <p class="font-bold text-green-600">S/ {{ number_format($register->total_sales, 2) }}</p>
                </div>
                <div class="text-center">
                    <p class="text-xs text-gray-500">Diferencia</p>
                    <p class="font-bold {{ (float)$register->difference >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        S/ {{ number_format($register->difference, 2) }}
                    </p>
                </div>
                <div class="flex gap-1">
                    <a href="{{ route('reports.cash.show', $register) }}"
                       class="bg-blue-600 text-white px-3 py-1.5 rounded-lg hover:bg-blue-700 text-xs">Ver</a>
                    <a href="{{ route('reports.cash.export-excel', $register) }}"
                       class="bg-green-600 text-white px-2 py-1.5 rounded-lg hover:bg-green-700 text-xs">xlsx</a>
                    <a href="{{ route('reports.cash.export-pdf', $register) }}"
                       class="bg-red-600 text-white px-2 py-1.5 rounded-lg hover:bg-red-700 text-xs">pdf</a>
                </div>
            </div>
        </div>
    </div>
    @endforeach

</div>
@endsection