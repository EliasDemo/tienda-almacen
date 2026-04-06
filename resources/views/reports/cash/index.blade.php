@extends('layouts.app')

@section('title', 'Reportes de Caja')

@section('content')
<div class="max-w-5xl mx-auto">
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b">
            <h3 class="text-lg font-semibold">Historial de Cajas</h3>
            <p class="text-sm text-gray-500 mt-1">Selecciona un día para ver el detalle de las cajas.</p>
        </div>

        <div class="divide-y">
            @forelse($days as $day)
            <a href="{{ route('reports.cash.day', $day->date) }}"
               class="flex items-center justify-between p-4 hover:bg-gray-50 transition-colors">
                <div class="flex items-center gap-4">
                    {{-- Icono carpeta --}}
                    <div class="bg-blue-100 rounded-lg p-3">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-800">{{ \Carbon\Carbon::parse($day->date)->format('d/m/Y') }}</p>
                        <p class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($day->date)->translatedFormat('l') }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-8">
                    <div class="text-center">
                        <p class="text-xs text-gray-500">Cajas</p>
                        <p class="font-bold text-gray-800">{{ $day->total_registers }}</p>
                    </div>
                    <div class="text-center">
                        <p class="text-xs text-gray-500">Total Ventas</p>
                        <p class="font-bold text-green-600">S/ {{ number_format($day->total_sales, 2) }}</p>
                    </div>
                    <div class="text-center">
                        <p class="text-xs text-gray-500">Efectivo</p>
                        <p class="font-bold text-gray-800">S/ {{ number_format($day->total_cash, 2) }}</p>
                    </div>
                    <div class="text-center">
                        <p class="text-xs text-gray-500">Otros</p>
                        <p class="font-bold text-gray-800">S/ {{ number_format($day->total_other, 2) }}</p>
                    </div>
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </div>
            </a>
            @empty
            <div class="p-8 text-center text-gray-400">
                No hay registros de caja.
            </div>
            @endforelse
        </div>

        <div class="p-4">
            {{ $days->links() }}
        </div>
    </div>
</div>
@endsection