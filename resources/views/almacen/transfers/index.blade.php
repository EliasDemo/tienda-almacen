@extends('layouts.app')

@section('title', 'Despachos del Día')

@section('content')
<div class="bg-white rounded-lg shadow">
    <div class="flex justify-between items-center p-6 border-b">
        <div>
            <h3 class="text-lg font-semibold">Despachos del Día</h3>
            <p class="text-sm text-gray-500">{{ now()->format('d/m/Y') }}</p>
        </div>
        <form method="POST" action="{{ route('almacen.transfers.store') }}">
            @csrf
            <button type="submit"
                    class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm">
                + Nuevo Cargamento
            </button>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left">
                <tr>
                    <th class="px-6 py-3 text-gray-600 font-medium">Código</th>
                    <th class="px-6 py-3 text-gray-600 font-medium">Productos</th>
                    <th class="px-6 py-3 text-gray-600 font-medium">Bultos</th>
                    <th class="px-6 py-3 text-gray-600 font-medium">Estado</th>
                    <th class="px-6 py-3 text-gray-600 font-medium">Hora</th>
                    <th class="px-6 py-3 text-gray-600 font-medium">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($transfers as $transfer)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 font-mono text-xs font-medium">{{ $transfer->transfer_code }}</td>
                    <td class="px-6 py-4">
                        @foreach($transfer->lines as $line)
                            <div class="text-xs">
                                <span class="font-medium">{{ $line->variant->product->name }}</span>
                                <span class="text-gray-400">({{ $line->total_packages }})</span>
                            </div>
                        @endforeach
                        @if($transfer->lines->isEmpty())
                            <span class="text-gray-400 text-xs">Vacío</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 font-medium">{{ $transfer->total_packages }}</td>
                    <td class="px-6 py-4">
                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full
                            {{ $transfer->status === 'preparing' ? 'bg-yellow-100 text-yellow-800' : '' }}
                            {{ $transfer->status === 'in_transit' ? 'bg-blue-100 text-blue-800' : '' }}
                            {{ $transfer->status === 'received' ? 'bg-green-100 text-green-800' : '' }}
                        ">
                            {{ $transfer->status === 'preparing' ? 'Preparando' : '' }}
                            {{ $transfer->status === 'in_transit' ? 'En camino' : '' }}
                            {{ $transfer->status === 'received' ? 'Recibido' : '' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-xs">{{ $transfer->created_at->format('H:i') }}</td>
                    <td class="px-6 py-4">
                        <div class="flex gap-2">
                            <a href="{{ route('almacen.transfers.show', $transfer) }}"
                            class="text-blue-600 hover:underline text-sm font-medium">
                                {{ $transfer->status === 'preparing' ? 'Trabajar' : 'Ver' }}
                            </a>
                            @if($transfer->status === 'preparing' && $transfer->lines->sum('total_packages') === 0)
                            <form method="POST" action="{{ route('almacen.transfers.destroy', $transfer) }}"
                                onsubmit="return confirm('¿Eliminar este cargamento vacío?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-500 hover:underline text-sm">Eliminar</button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-gray-400">
                        No hay cargamentos hoy. Crea el primero.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection