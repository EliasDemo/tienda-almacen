@extends('layouts.app')

@section('title', 'Bultos y Etiquetas')

@section('content')
<div class="bg-white rounded-lg shadow">
    <div class="p-6 border-b">
        <h3 class="text-lg font-semibold">Bultos por Cargamento</h3>
        <p class="text-sm text-gray-500 mt-1">Consulta los bultos y etiquetas de cada cargamento.</p>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left">
                <tr>
                    <th class="px-6 py-3 text-gray-600 font-medium">Cargamento</th>
                    <th class="px-6 py-3 text-gray-600 font-medium">Productos</th>
                    <th class="px-6 py-3 text-gray-600 font-medium">Total Bultos</th>
                    <th class="px-6 py-3 text-gray-600 font-medium">Peso Total</th>
                    <th class="px-6 py-3 text-gray-600 font-medium">Estado</th>
                    <th class="px-6 py-3 text-gray-600 font-medium">Despachado por</th>
                    <th class="px-6 py-3 text-gray-600 font-medium">Fecha</th>
                    <th class="px-6 py-3 text-gray-600 font-medium">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($transfers as $transfer)
                @php
                    $totalPkgs = $transfer->lines->sum('total_packages');
                    $totalWeight = $transfer->lines->sum(fn($l) => $l->packages->sum('gross_weight'));
                    $totalUnits = $transfer->lines->sum(fn($l) => $l->packages->sum('unit_count'));
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 font-mono text-xs font-medium">{{ $transfer->transfer_code }}</td>
                    <td class="px-6 py-4">
                        @foreach($transfer->lines as $line)
                            <div class="text-xs">
                                <span class="font-medium">{{ $line->variant->product->name }}</span>
                                <span class="text-gray-400">({{ $line->total_packages }} bultos)</span>
                            </div>
                        @endforeach
                        @if($transfer->lines->isEmpty())
                            <span class="text-gray-400 text-xs">Vacío</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 font-medium">{{ $totalPkgs }}</td>
                    <td class="px-6 py-4">
                        @if($totalWeight > 0)
                            {{ number_format($totalWeight, 3) }} kg
                        @endif
                        @if($totalUnits > 0)
                            {{ $totalUnits }} und
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full
                            {{ $transfer->status === 'preparing' ? 'bg-yellow-100 text-yellow-800' : '' }}
                            {{ $transfer->status === 'in_transit' ? 'bg-blue-100 text-blue-800' : '' }}
                            {{ $transfer->status === 'received' ? 'bg-green-100 text-green-800' : '' }}
                            {{ $transfer->status === 'partial' ? 'bg-orange-100 text-orange-800' : '' }}
                        ">
                            {{ $transfer->status === 'preparing' ? 'Preparando' : '' }}
                            {{ $transfer->status === 'in_transit' ? 'En camino' : '' }}
                            {{ $transfer->status === 'received' ? 'Recibido' : '' }}
                            {{ $transfer->status === 'partial' ? 'Parcial' : '' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-xs">{{ $transfer->dispatcher->name }}</td>
                    <td class="px-6 py-4 text-xs">{{ $transfer->created_at->format('d/m/Y H:i') }}</td>
                    <td class="px-6 py-4">
                        <a href="{{ route('almacen.packages.show', $transfer) }}"
                           class="text-blue-600 hover:underline text-sm">Ver bultos</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-8 text-center text-gray-400">
                        No hay cargamentos con bultos.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="p-4">
        {{ $transfers->links() }}
    </div>
</div>
@endsection