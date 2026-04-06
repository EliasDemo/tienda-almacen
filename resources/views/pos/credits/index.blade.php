@extends('layouts.app')

@section('title', 'Fiados de Clientes')

@section('content')
<div class="max-w-5xl mx-auto">

    {{-- Resumen --}}
    <div class="bg-white rounded-lg shadow p-4 mb-4 flex justify-between items-center">
        <div>
            <h3 class="text-lg font-semibold">Fiados de Clientes</h3>
            <p class="text-sm text-gray-500">Clientes con deuda pendiente en tienda</p>
        </div>
        <div class="bg-red-50 rounded-lg px-6 py-3 text-center">
            <p class="text-xs text-red-600">Total Pendiente</p>
            <p class="text-2xl font-bold text-red-700">S/ {{ number_format($totalPending, 2) }}</p>
        </div>
    </div>

    {{-- Lista de clientes con deuda --}}
    <div class="bg-white rounded-lg shadow">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-left">
                    <tr>
                        <th class="px-6 py-3 text-gray-600 font-medium">Cliente</th>
                        <th class="px-6 py-3 text-gray-600 font-medium">Teléfono</th>
                        <th class="px-6 py-3 text-gray-600 font-medium">Tipo</th>
                        <th class="px-6 py-3 text-gray-600 font-medium">Fiados Activos</th>
                        <th class="px-6 py-3 text-gray-600 font-medium">Deuda Total</th>
                        <th class="px-6 py-3 text-gray-600 font-medium">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($customers as $customer)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 font-medium">{{ $customer->name }}</td>
                        <td class="px-6 py-4 text-xs">{{ $customer->phone ?? '—' }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-0.5 rounded text-xs {{ $customer->price_type === 'mayorista' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700' }}">
                                {{ ucfirst($customer->price_type) }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="bg-red-100 text-red-700 px-2 py-0.5 rounded-full text-xs font-medium">
                                {{ $customer->credits->count() }}
                            </span>
                        </td>
                        <td class="px-6 py-4 font-bold text-red-600">
                            S/ {{ number_format($customer->pending_balance, 2) }}
                        </td>
                        <td class="px-6 py-4">
                            <a href="{{ route('pos.credits.show', $customer) }}"
                               class="bg-blue-600 text-white px-3 py-1.5 rounded-lg hover:bg-blue-700 text-xs">
                                Ver Detalle
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-400">
                            No hay clientes con fiados pendientes.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection