@extends('layouts.app')

@section('title', 'Detalle del Lote')

@section('content')
<div class="max-w-4xl mx-auto">

    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex justify-between items-start mb-4">
            <div>
                <h3 class="text-lg font-semibold">{{ $lot->lot_code }}</h3>
                <p class="text-sm text-gray-500">
                    {{ $lot->variant->product->category->name }} >
                    {{ $lot->variant->product->name }} >
                    {{ $lot->variant->name }}
                </p>
            </div>
            <a href="{{ route('almacen.entries.index') }}"
               class="text-sm text-blue-600 hover:underline">← Volver</a>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div>
                <p class="text-xs text-gray-500">Proveedor</p>
                <p class="font-medium">{{ $lot->supplier ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Precio Compra</p>
                <p class="font-medium">
                    S/ {{ number_format($lot->purchase_price_per_kg ?? $lot->purchase_price_per_unit ?? 0, 2) }}
                    / {{ $lot->unit }}
                </p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Cantidad Total</p>
                <p class="font-medium">{{ number_format($lot->total_quantity, 3) }} {{ $lot->unit }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Disponible</p>
                <p class="font-medium {{ $lot->remaining_quantity <= 0 ? 'text-red-600' : 'text-green-600' }}">
                    {{ number_format($lot->remaining_quantity, 3) }} {{ $lot->unit }}
                </p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Fecha Entrada</p>
                <p class="font-medium">{{ $lot->entry_date->format('d/m/Y') }}</p>
            </div>
            @if($lot->expiry_date)
            <div>
                <p class="text-xs text-gray-500">Vencimiento</p>
                <p class="font-medium">{{ $lot->expiry_date->format('d/m/Y') }}</p>
            </div>
            @endif
        </div>

        {{-- Barra de progreso --}}
        <div class="mt-4">
            <div class="flex justify-between text-xs text-gray-500 mb-1">
                <span>Despachado: {{ $lot->dispatched_percent }}%</span>
                <span>{{ number_format($lot->dispatched_quantity, 3) }} / {{ number_format($lot->total_quantity, 3) }} {{ $lot->unit }}</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-blue-600 h-2 rounded-full" style="width: {{ min($lot->dispatched_percent, 100) }}%"></div>
            </div>
        </div>

        @if($lot->notes)
        <div class="mt-3">
            <p class="text-xs text-gray-500">Observaciones</p>
            <p class="text-sm">{{ $lot->notes }}</p>
        </div>
        @endif
    </div>

    {{-- Bultos despachados de este lote --}}
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b">
            <h3 class="text-lg font-semibold">Bultos Despachados de este Lote</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-left">
                    <tr>
                        <th class="px-6 py-3 text-gray-600 font-medium">#</th>
                        <th class="px-6 py-3 text-gray-600 font-medium">UUID</th>
                        <th class="px-6 py-3 text-gray-600 font-medium">Cargamento</th>
                        <th class="px-6 py-3 text-gray-600 font-medium">Tipo</th>
                        <th class="px-6 py-3 text-gray-600 font-medium">Peso / Cantidad</th>
                        <th class="px-6 py-3 text-gray-600 font-medium">Estado</th>
                        <th class="px-6 py-3 text-gray-600 font-medium">Ubicación</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($lot->packages as $i => $pkg)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">{{ $i + 1 }}</td>
                        <td class="px-6 py-4 font-mono text-xs">{{ Str::limit($pkg->uuid, 12) }}</td>
                        <td class="px-6 py-4 font-mono text-xs">
                            @if($pkg->transferLine && $pkg->transferLine->transfer)
                                {{ $pkg->transferLine->transfer->transfer_code }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-6 py-4 capitalize">{{ $pkg->package_type }}</td>
                        <td class="px-6 py-4">
                            @if($pkg->gross_weight)
                                {{ number_format($pkg->gross_weight, 3) }} kg
                            @else
                                {{ $pkg->unit_count }} unidades
                            @endif
                        </td>
                        <td class="px-6 py-4">
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
                        <td class="px-6 py-4 capitalize">{{ $pkg->location }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-gray-400">
                            Aún no se han despachado bultos de este lote.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
