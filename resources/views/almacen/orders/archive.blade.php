@extends('layouts.app')

@section('title', 'Archivo de Pedidos')

@section('content')
<div class="max-w-6xl mx-auto">

    <div class="bg-white rounded-lg shadow p-4 mb-4 flex justify-between items-center">
        <div>
            <h3 class="text-lg font-semibold"><i class="fas fa-archive mr-2 text-gray-500"></i>Archivo de Pedidos</h3>
            <p class="text-xs text-gray-500 mt-1">Pedidos entregados y cancelados.</p>
        </div>
        <a href="{{ route('almacen.orders.index') }}"
           class="text-sm text-blue-600 hover:underline flex items-center gap-1">
            <i class="fas fa-arrow-left"></i> Pedidos Activos
        </a>
    </div>

    <div class="bg-white rounded-lg shadow">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-left">
                    <tr>
                        <th class="px-5 py-3 text-gray-600 font-medium">Código</th>
                        <th class="px-5 py-3 text-gray-600 font-medium">Cliente</th>
                        <th class="px-5 py-3 text-gray-600 font-medium text-center">Items</th>
                        <th class="px-5 py-3 text-gray-600 font-medium text-right">Total Real</th>
                        <th class="px-5 py-3 text-gray-600 font-medium text-right">Adelanto</th>
                        <th class="px-5 py-3 text-gray-600 font-medium text-center">Estado</th>
                        <th class="px-5 py-3 text-gray-600 font-medium text-center">Fecha</th>
                        <th class="px-5 py-3 text-gray-600 font-medium text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($orders as $order)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3 font-mono text-xs font-medium">{{ $order->request_code }}</td>
                        <td class="px-5 py-3">
                            <p class="font-medium">{{ $order->customer->name ?? '—' }}</p>
                        </td>
                        <td class="px-5 py-3 text-center text-xs">{{ $order->items->count() }}</td>
                        <td class="px-5 py-3 text-right font-medium">
                            @if((float) $order->real_total > 0)
                            S/ {{ number_format($order->real_total, 2) }}
                            @else
                            <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-right text-green-700">S/ {{ number_format($order->advance_amount, 2) }}</td>
                        <td class="px-5 py-3 text-center">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium rounded-full {{ $order->status_color }}">
                                <i class="fas {{ $order->status_icon }} text-[10px]"></i>
                                {{ $order->status_label }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-center text-xs text-gray-500">
                            {{ $order->updated_at->format('d/m/Y') }}
                        </td>
                        <td class="px-5 py-3 text-center">
                            <a href="{{ route('almacen.orders.show', $order) }}" class="text-blue-600 hover:text-blue-800">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-5 py-12 text-center text-gray-400">
                            <i class="fas fa-archive text-3xl mb-2"></i>
                            <p>No hay pedidos archivados.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $orders->links() }}</div>
    </div>
</div>
@endsection