@extends('layouts.app')

@section('title', 'Bultos del Cargamento')

@section('content')
<div class="max-w-6xl mx-auto">

    {{-- Resumen del cargamento --}}
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex justify-between items-start mb-4">
            <div>
                <h3 class="text-lg font-semibold">{{ $transfer->transfer_code }}</h3>
                <p class="text-sm text-gray-500">
                    Despachado por: {{ $transfer->dispatcher->name }}
                    — {{ $transfer->created_at->format('d/m/Y H:i') }}
                </p>
            </div>
            <div class="flex gap-2 items-center">
                <span class="inline-flex px-3 py-1 text-sm font-medium rounded-full
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
                <a href="{{ route('almacen.packages.index') }}" class="text-sm text-blue-600 hover:underline">← Volver</a>
            </div>
        </div>

        @if($transfer->dispatched_at)
        <p class="text-xs text-gray-500">Despachado: {{ $transfer->dispatched_at->format('d/m/Y H:i') }}</p>
        @endif
        @if($transfer->received_at)
        <p class="text-xs text-gray-500">Recibido: {{ $transfer->received_at->format('d/m/Y H:i') }}</p>
        @endif
        @if($transfer->notes)
        <p class="text-sm text-gray-600 mt-2">{{ $transfer->notes }}</p>
        @endif

        {{-- Resumen por producto --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
            @foreach($transfer->lines as $line)
            @php
                $lineWeight = $line->packages->sum('gross_weight');
                $lineUnits = $line->packages->sum('unit_count');
            @endphp
            <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-sm font-semibold">{{ $line->variant->product->name }} — {{ $line->variant->name }}</p>
                <div class="mt-2 space-y-1">
                    <p class="text-xs text-gray-500">Bultos: <span class="font-medium text-gray-800">{{ $line->total_packages }}</span></p>
                    @if($lineWeight > 0)
                    <p class="text-xs text-gray-500">Peso total: <span class="font-medium text-gray-800">{{ number_format($lineWeight, 3) }} kg</span></p>
                    @endif
                    @if($lineUnits > 0)
                    <p class="text-xs text-gray-500">Unidades: <span class="font-medium text-gray-800">{{ $lineUnits }}</span></p>
                    @endif
                    <p class="text-xs text-red-600">Merma: {{ number_format($line->merma_kg, 3) }} kg</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Bultos por producto --}}
    @foreach($transfer->lines as $line)
    <div class="bg-white rounded-lg shadow mb-4">
        <div class="p-4 border-b">
            <div class="flex justify-between items-center">
                <h4 class="font-semibold">
                    {{ $line->variant->product->category->name }} >
                    {{ $line->variant->product->name }} >
                    {{ $line->variant->name }}
                </h4>
                <span class="text-xs text-gray-500">{{ $line->total_packages }} bultos — Merma: {{ number_format($line->merma_kg, 3) }} kg</span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-left">
                    <tr>
                        <th class="px-4 py-2 text-gray-600 font-medium text-xs">#</th>
                        <th class="px-4 py-2 text-gray-600 font-medium text-xs">UUID</th>
                        <th class="px-4 py-2 text-gray-600 font-medium text-xs">Lote Origen</th>
                        <th class="px-4 py-2 text-gray-600 font-medium text-xs">Tipo</th>
                        <th class="px-4 py-2 text-gray-600 font-medium text-xs">Peso / Cant.</th>
                        <th class="px-4 py-2 text-gray-600 font-medium text-xs">Estado</th>
                        <th class="px-4 py-2 text-gray-600 font-medium text-xs">Ubicación</th>
                        <th class="px-4 py-2 text-gray-600 font-medium text-xs">Etiqueta</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach($line->packages as $i => $pkg)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 text-xs">{{ $i + 1 }}</td>
                        <td class="px-4 py-2 font-mono text-xs">{{ Str::limit($pkg->uuid, 12) }}</td>
                        <td class="px-4 py-2 text-xs">{{ $pkg->lot->lot_code }}</td>
                        <td class="px-4 py-2 text-xs capitalize">{{ $pkg->package_type }}</td>
                        <td class="px-4 py-2 text-xs font-medium">
                            @if($pkg->gross_weight)
                                {{ number_format($pkg->gross_weight, 3) }} kg
                            @else
                                {{ $pkg->unit_count }} und
                            @endif
                        </td>
                        <td class="px-4 py-2">
                            <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full
                                {{ $pkg->status === 'closed' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $pkg->status === 'opened' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                {{ $pkg->status === 'sold' ? 'bg-blue-100 text-blue-800' : '' }}
                                {{ $pkg->status === 'sold_in_transit' ? 'bg-orange-100 text-orange-800' : '' }}
                                {{ $pkg->status === 'exhausted' ? 'bg-gray-100 text-gray-800' : '' }}
                            ">
                                {{ $pkg->status }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-xs capitalize">{{ $pkg->location }}</td>
                        <td class="px-4 py-2">
                            <a href="{{ route('almacen.packages.label', $pkg) }}"
                               class="text-green-600 hover:underline text-xs" target="_blank">
                                Imprimir QR
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endforeach

</div>
@endsection
