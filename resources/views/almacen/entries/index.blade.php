@extends('layouts.app')

@section('title', 'Entradas de Productos')

@section('content')
<div class="bg-white rounded-lg shadow">
    <div class="flex justify-between items-center p-6 border-b">
        <h3 class="text-lg font-semibold">Entradas de Productos al Almacén</h3>
        <a href="{{ route('almacen.entries.create') }}"
           class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm">
            + Nueva Entrada
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left">
                <tr>
                    <th class="px-6 py-3 text-gray-600 font-medium">Código Lote</th>
                    <th class="px-6 py-3 text-gray-600 font-medium">Producto</th>
                    <th class="px-6 py-3 text-gray-600 font-medium">Proveedor</th>
                    <th class="px-6 py-3 text-gray-600 font-medium">Cantidad Total</th>
                    <th class="px-6 py-3 text-gray-600 font-medium">Disponible</th>
                    <th class="px-6 py-3 text-gray-600 font-medium">Fecha</th>
                    <th class="px-6 py-3 text-gray-600 font-medium">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($lots as $lot)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 font-mono text-xs">{{ $lot->lot_code }}</td>
                    <td class="px-6 py-4">
                        {{ $lot->variant->product->name }} - {{ $lot->variant->name }}
                    </td>
                    <td class="px-6 py-4">{{ $lot->supplier ?? '—' }}</td>
                    <td class="px-6 py-4">{{ number_format($lot->total_quantity, 3) }} {{ $lot->unit }}</td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <span class="{{ $lot->remaining_quantity <= 0 ? 'text-red-600' : 'text-green-600' }} font-medium">
                                {{ number_format($lot->remaining_quantity, 3) }} {{ $lot->unit }}
                            </span>
                            <span class="text-xs text-gray-400">({{ $lot->dispatched_percent }}% despachado)</span>
                        </div>
                    </td>
                    <td class="px-6 py-4">{{ $lot->entry_date->format('d/m/Y') }}</td>
                    <td class="px-6 py-4">
                        <a href="{{ route('almacen.entries.show', $lot) }}"
                           class="text-blue-600 hover:underline text-sm">Ver</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-8 text-center text-gray-400">
                        No hay entradas registradas.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="p-4">
        {{ $lots->links() }}
    </div>
</div>
@endsection