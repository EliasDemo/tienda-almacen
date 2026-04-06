@extends('layouts.app')

@section('title', 'Pedidos Recibidos')

@section('content')
<div class="max-w-6xl mx-auto">

    <div class="bg-white rounded-lg shadow p-4 mb-4 flex justify-between items-center">
        <div>
            <h3 class="text-lg font-semibold"><i class="fas fa-clipboard-list mr-2 text-yellow-500"></i>Pedidos Activos</h3>
            <p class="text-xs text-gray-500 mt-1">Pedidos de caja que necesitan preparación.</p>
        </div>
        <a href="{{ route('almacen.orders.archive') }}"
           class="text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1">
            <i class="fas fa-archive"></i> Archivo
        </a>
    </div>

    @php
        $openTransfers = \App\Models\Transfer::where('status', 'preparing')->whereNull('stock_request_id')->get();
    @endphp

    @forelse($orders as $order)
    @php
        $isLate = $order->delivery_date?->isPast();
        $borderColor = match($order->status) {
            'pending' => 'border-l-yellow-400',
            'confirmed' => 'border-l-blue-400',
            'preparing' => 'border-l-purple-400',
            'ready' => 'border-l-green-400',
            default => 'border-l-gray-300',
        };
        $labelColors = ['rojo'=>'bg-red-500','azul'=>'bg-blue-500','verde'=>'bg-green-500','amarillo'=>'bg-yellow-400'];
    @endphp
    <div class="bg-white rounded-lg shadow mb-3 border-l-4 {{ $borderColor }} {{ $isLate ? 'ring-2 ring-red-200' : '' }}">
        <div class="p-4">
            <div class="flex justify-between items-start">
                <div>
                    <div class="flex items-center gap-3">
                        <span class="font-mono text-sm font-bold">{{ $order->request_code }}</span>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium rounded-full {{ $order->status_color }}">
                            <i class="fas {{ $order->status_icon }} text-[10px]"></i>
                            {{ $order->status_label }}
                        </span>
                        <span class="w-3 h-3 rounded-full {{ $labelColors[$order->label_color] ?? 'bg-gray-400' }}" title="Color: {{ $order->label_color }}"></span>
                        @if($isLate)
                        <span class="text-xs text-red-600 font-bold"><i class="fas fa-exclamation-triangle mr-0.5"></i>VENCIDO</span>
                        @endif
                    </div>
                    <p class="text-sm text-gray-700 mt-1">
                        <i class="fas fa-user mr-1 text-gray-400"></i><strong>{{ $order->customer->name }}</strong>
                        @if($order->customer->phone)
                        <span class="text-gray-400 text-xs ml-1">{{ $order->customer->phone }}</span>
                        @endif
                        <span class="text-gray-400 mx-2">|</span>
                        <i class="fas fa-calendar mr-1 text-gray-400"></i>Entrega: <strong>{{ $order->delivery_date?->format('d/m/Y') ?? '—' }}</strong>
                    </p>
                    @if($order->transfer)
                    <p class="text-xs text-purple-600 mt-1">
                        <i class="fas fa-truck mr-1"></i>Cargamento: <strong>{{ $order->transfer->transfer_code }}</strong>
                    </p>
                    @endif
                </div>
                <div class="text-right">
                    @if((float) $order->real_total > 0)
                    <p class="text-lg font-bold">S/ {{ number_format($order->real_total, 2) }}</p>
                    @else
                    <p class="text-sm text-orange-500"><i class="fas fa-weight-hanging mr-1"></i>Sin pesar</p>
                    @endif
                    <p class="text-xs text-green-600">Adelanto: S/ {{ number_format($order->advance_amount, 2) }}</p>
                </div>
            </div>

            {{-- Items resumen --}}
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach($order->items as $item)
                <span class="bg-gray-100 px-2 py-1 rounded text-xs">
                    {{ $item->variant->product->name }} — {{ (int) $item->quantity_requested }} {{ $item->package_type ?? 'saco' }}(s)
                    <span class="text-gray-400">× S/{{ number_format($item->sale_price, 2) }}/{{ $item->unit }}</span>
                    @if((float) $item->quantity_sent > 0)
                    <span class="text-green-600 font-bold ml-1">✓ {{ number_format($item->quantity_sent, 3) }} {{ $item->unit }}</span>
                    @endif
                </span>
                @endforeach
            </div>

            {{-- Acciones --}}
            <div class="mt-3 flex gap-2 justify-end flex-wrap">
                @if($order->status === 'pending')
                <form method="POST" action="{{ route('almacen.orders.confirm', $order) }}">
                    @csrf
                    <button type="submit" class="bg-blue-600 text-white px-4 py-1.5 rounded-lg hover:bg-blue-700 text-xs">
                        <i class="fas fa-check mr-1"></i>Confirmar
                    </button>
                </form>
                @endif

                @if($order->status === 'confirmed')
                {{-- Crear cargamento nuevo --}}
                <form method="POST" action="{{ route('almacen.orders.prepare', $order) }}">
                    @csrf
                    <button type="submit" class="bg-purple-600 text-white px-4 py-1.5 rounded-lg hover:bg-purple-700 text-xs">
                        <i class="fas fa-plus mr-1"></i>Crear Cargamento y Preparar
                    </button>
                </form>

                {{-- O vincular a cargamento existente --}}
                @if($openTransfers->count() > 0)
                <button onclick="linkToTransfer({{ $order->id }})" class="bg-indigo-600 text-white px-4 py-1.5 rounded-lg hover:bg-indigo-700 text-xs">
                    <i class="fas fa-link mr-1"></i>Vincular a Cargamento
                </button>
                @endif
                @endif

                @if($order->status === 'preparing' && $order->transfer)
                <a href="{{ route('almacen.transfers.show', $order->transfer) }}"
                   class="bg-purple-100 text-purple-700 px-4 py-1.5 rounded-lg hover:bg-purple-200 text-xs">
                    <i class="fas fa-boxes-stacked mr-1"></i>Ir al Cargamento
                </a>
                @endif

                @if($order->status === 'preparing')
                <form method="POST" action="{{ route('almacen.orders.ready', $order) }}">
                    @csrf
                    <button type="submit" class="bg-green-600 text-white px-4 py-1.5 rounded-lg hover:bg-green-700 text-xs"
                            onclick="return confirm('¿Marcar como listo? Se calculará el total real.')">
                        <i class="fas fa-check-double mr-1"></i>Marcar Listo
                    </button>
                </form>
                @endif

                <a href="{{ route('almacen.orders.show', $order) }}"
                   class="bg-gray-100 text-gray-700 px-4 py-1.5 rounded-lg hover:bg-gray-200 text-xs">
                    <i class="fas fa-eye mr-1"></i>Detalle
                </a>
            </div>
        </div>
    </div>
    @empty
    <div class="bg-white rounded-lg shadow p-12 text-center">
        <i class="fas fa-clipboard-check text-4xl text-gray-300 mb-3"></i>
        <p class="text-gray-400">No hay pedidos pendientes.</p>
    </div>
    @endforelse
</div>

{{-- Forms ocultos para vincular a cargamento --}}
@foreach($orders->where('status', 'confirmed') as $order)
<form id="link-form-{{ $order->id }}" method="POST" action="{{ route('almacen.orders.link-transfer', $order) }}" class="hidden">
    @csrf
    <input type="hidden" name="transfer_id" id="link-transfer-{{ $order->id }}">
</form>
@endforeach

<script>
function linkToTransfer(orderId) {
    const transfers = @json($openTransfers->map(fn($t) => ['id' => $t->id, 'code' => $t->transfer_code]));
    let options = {};
    transfers.forEach(t => { options[t.id] = t.code; });

    Swal.fire({
        title: '<i class="fas fa-link text-indigo-500 mr-2"></i>Vincular a Cargamento',
        html: '<p class="text-sm text-gray-600 mb-2">Selecciona un cargamento abierto:</p>',
        input: 'select',
        inputOptions: options,
        inputPlaceholder: '-- Seleccionar cargamento --',
        showCancelButton: true, confirmButtonColor: '#4F46E5', cancelButtonColor: '#6B7280',
        confirmButtonText: '<i class="fas fa-link mr-1"></i> Vincular',
        cancelButtonText: 'Cancelar', reverseButtons: true,
        inputValidator: (value) => { if (!value) return 'Selecciona un cargamento'; }
    }).then((r) => {
        if (r.isConfirmed) {
            document.getElementById('link-transfer-' + orderId).value = r.value;
            document.getElementById('link-form-' + orderId).submit();
        }
    });
}
</script>
@endsection