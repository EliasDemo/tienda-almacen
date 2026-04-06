@extends('layouts.app')

@section('title', 'Pedidos')

@section('content')
<div class="max-w-6xl mx-auto">

    <div class="bg-white rounded-lg shadow p-4 mb-4 flex justify-between items-center">
        <div>
            <h3 class="text-lg font-semibold"><i class="fas fa-clipboard-list mr-2 text-blue-500"></i>Pedidos de Clientes</h3>
            <p class="text-xs text-gray-500 mt-1">Gestiona pedidos, adelantos y entregas.</p>
        </div>
        <a href="{{ route('pos.orders.create') }}"
           class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm flex items-center gap-2">
            <i class="fas fa-plus"></i> Nuevo Pedido
        </a>
    </div>

    {{-- Filtros --}}
    <div class="bg-white rounded-lg shadow p-4 mb-4" x-data="{ status: '{{ request('status', '') }}' }">
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('pos.orders.index') }}"
               class="px-3 py-1.5 rounded-lg text-xs font-medium {{ !request('status') ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                Todos
            </a>
            @foreach(['pending' => 'Pendientes', 'confirmed' => 'Confirmados', 'preparing' => 'En preparación', 'ready' => 'Listos', 'delivered' => 'Entregados', 'cancelled' => 'Cancelados'] as $key => $label)
            <a href="{{ route('pos.orders.index', ['status' => $key]) }}"
               class="px-3 py-1.5 rounded-lg text-xs font-medium {{ request('status') === $key ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                {{ $label }}
            </a>
            @endforeach
        </div>
    </div>

    {{-- Tabla --}}
    <div class="bg-white rounded-lg shadow">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-left">
                    <tr>
                        <th class="px-5 py-3 text-gray-600 font-medium"><i class="fas fa-hashtag mr-1 text-xs"></i>Código</th>
                        <th class="px-5 py-3 text-gray-600 font-medium"><i class="fas fa-user mr-1 text-xs"></i>Cliente</th>
                        <th class="px-5 py-3 text-gray-600 font-medium text-center"><i class="fas fa-box mr-1 text-xs"></i>Items</th>
                        <th class="px-5 py-3 text-gray-600 font-medium text-center"><i class="fas fa-calendar mr-1 text-xs"></i>Entrega</th>
                        <th class="px-5 py-3 text-gray-600 font-medium text-right">Total</th>
                        <th class="px-5 py-3 text-gray-600 font-medium text-right">Adelanto</th>
                        <th class="px-5 py-3 text-gray-600 font-medium text-right">Saldo</th>
                        <th class="px-5 py-3 text-gray-600 font-medium text-center">Estado</th>
                        <th class="px-5 py-3 text-gray-600 font-medium text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($orders as $order)
                    @php
                        $isLate = $order->delivery_date && $order->delivery_date->isPast() && !in_array($order->status, ['delivered', 'cancelled']);
                    @endphp
                    <tr class="hover:bg-gray-50 transition-colors {{ $isLate ? 'bg-red-50' : '' }}">
                        <td class="px-5 py-3">
                            <span class="font-mono text-xs font-medium">{{ $order->request_code }}</span>
                            <p class="text-[10px] text-gray-400">{{ $order->created_at->format('d/m/Y H:i') }}</p>
                        </td>
                        <td class="px-5 py-3">
                            <p class="font-medium text-gray-800">{{ $order->customer->name ?? '—' }}</p>
                            @if($order->customer?->phone)
                            <p class="text-[10px] text-gray-400"><i class="fas fa-phone mr-0.5"></i>{{ $order->customer->phone }}</p>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-center">
                            <span class="bg-gray-100 px-2 py-0.5 rounded text-xs">{{ $order->items->count() }}</span>
                        </td>
                        <td class="px-5 py-3 text-center">
                            @if($order->delivery_date)
                            <span class="text-xs {{ $isLate ? 'text-red-600 font-bold' : 'text-gray-700' }}">
                                {{ $order->delivery_date->format('d/m/Y') }}
                            </span>
                            @if($isLate)
                            <p class="text-[10px] text-red-500"><i class="fas fa-exclamation-triangle mr-0.5"></i>Vencido</p>
                            @endif
                            @endif
                        </td>
                        <td class="px-5 py-3 text-right font-medium">S/ {{ number_format($order->estimated_total, 2) }}</td>
                        <td class="px-5 py-3 text-right text-green-700 font-medium">S/ {{ number_format($order->advance_amount, 2) }}</td>
                        <td class="px-5 py-3 text-right {{ $order->remaining_amount > 0 ? 'text-red-600' : 'text-green-600' }} font-bold">
                            S/ {{ number_format($order->remaining_amount, 2) }}
                        </td>
                        <td class="px-5 py-3 text-center">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium rounded-full {{ $order->status_color }}">
                                <i class="fas {{ $order->status_icon }} text-[10px]"></i>
                                {{ $order->status_label }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-center">
                            <a href="{{ route('pos.orders.show', $order) }}"
                               class="text-blue-600 hover:text-blue-800">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-5 py-12 text-center">
                            <i class="fas fa-clipboard-list text-4xl text-gray-300 mb-3"></i>
                            <p class="text-gray-400">No hay pedidos registrados.</p>
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