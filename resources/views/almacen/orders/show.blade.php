@extends('layouts.app')

@section('title', 'Pedido ' . $order->request_code)

@section('content')
<div class="max-w-5xl mx-auto">

    {{-- Alertas del pedido --}}
    @if(count($order->alerts) > 0)
    <div class="mb-4 space-y-2">
        @foreach($order->alerts as $alert)
        <div class="p-3 rounded-lg flex items-center gap-2 text-sm
            {{ $alert['type'] === 'danger' ? 'bg-red-50 border border-red-200 text-red-700' : '' }}
            {{ $alert['type'] === 'warning' ? 'bg-yellow-50 border border-yellow-200 text-yellow-700' : '' }}
            {{ $alert['type'] === 'info' ? 'bg-blue-50 border border-blue-200 text-blue-700' : '' }}">
            <i class="fas {{ $alert['icon'] }}"></i>
            <span>{{ $alert['message'] }}</span>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Cabecera --}}
    <div class="bg-white rounded-lg shadow p-5 mb-4">
        <div class="flex justify-between items-start">
            <div>
                <div class="flex items-center gap-3">
                    <h3 class="text-xl font-bold">{{ $order->request_code }}</h3>
                    <span class="inline-flex items-center gap-1 px-3 py-1 text-sm font-medium rounded-full {{ $order->status_color }}">
                        <i class="fas {{ $order->status_icon }} text-xs"></i>
                        {{ $order->status_label }}
                    </span>
                    @php $lc = ['rojo'=>'bg-red-500','azul'=>'bg-blue-500','verde'=>'bg-green-500','amarillo'=>'bg-yellow-400']; @endphp
                    <span class="w-4 h-4 rounded-full {{ $lc[$order->label_color] ?? 'bg-gray-400' }}"></span>
                    @if($order->is_late)
                    <span class="bg-red-600 text-white px-2 py-0.5 rounded text-[10px] font-bold animate-pulse">ATRASADO</span>
                    @endif
                </div>
                <p class="text-xs text-gray-500 mt-1">{{ $order->user->name }} — {{ $order->created_at->format('d/m/Y H:i') }}</p>
            </div>
            <a href="{{ route('almacen.orders.index') }}" class="text-sm text-blue-600 hover:underline">
                <i class="fas fa-arrow-left mr-1"></i>Volver
            </a>
        </div>

        {{-- Info --}}
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mt-4">
            <div class="bg-gray-50 rounded-lg p-3">
                <p class="text-[10px] text-gray-500 uppercase">Cliente</p>
                <p class="font-bold text-sm">{{ $order->customer->name }}</p>
                @if($order->customer->phone)
                <p class="text-xs text-gray-400">{{ $order->customer->phone }}</p>
                @endif
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
                <p class="text-[10px] text-gray-500 uppercase">Entrega</p>
                <p class="font-bold text-sm {{ $order->is_late ? 'text-red-600' : '' }}">
                    {{ $order->delivery_date?->format('d/m/Y') ?? '—' }}
                    @if($order->is_late) <i class="fas fa-exclamation-triangle text-xs"></i> @endif
                </p>
            </div>
            <div class="bg-green-50 rounded-lg p-3">
                <p class="text-[10px] text-green-600 uppercase">Adelanto</p>
                <p class="font-bold text-lg text-green-700">S/ {{ number_format($order->advance_amount, 2) }}</p>
            </div>
            <div class="{{ (float)$order->real_total > 0 ? 'bg-blue-50' : 'bg-orange-50' }} rounded-lg p-3">
                <p class="text-[10px] {{ (float)$order->real_total > 0 ? 'text-blue-600' : 'text-orange-600' }} uppercase">Total</p>
                @if((float)$order->real_total > 0)
                <p class="font-bold text-lg">S/ {{ number_format($order->real_total, 2) }}</p>
                @else
                <p class="text-sm text-orange-600 font-medium"><i class="fas fa-clock mr-1"></i>Pendiente</p>
                @endif
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
                <p class="text-[10px] text-gray-500 uppercase">Cargamento</p>
                @if($order->transfer)
                <a href="{{ route('almacen.transfers.show', $order->transfer) }}" class="text-purple-600 hover:underline font-bold text-sm">
                    <i class="fas fa-truck mr-1"></i>{{ $order->transfer->transfer_code }}
                </a>
                <p class="text-[10px] text-gray-400 capitalize">{{ str_replace('_', ' ', $order->transfer->status) }}</p>
                @else
                <p class="text-sm text-gray-400">Sin vincular</p>
                @endif
            </div>
        </div>

        {{-- Notas --}}
        @if($order->customer_notes)
        <div class="mt-3 p-3 bg-yellow-50 border-l-4 border-yellow-400 rounded-r-lg">
            <p class="text-xs text-yellow-800"><strong>Cliente:</strong> {{ $order->customer_notes }}</p>
        </div>
        @endif

        {{-- Timeline --}}
        <div class="mt-3 flex flex-wrap gap-2 text-[10px]">
            @if($order->confirmed_at)
            <span class="bg-blue-50 text-blue-700 px-2 py-1 rounded"><i class="fas fa-check mr-1"></i>Confirmado: {{ $order->confirmed_at->format('d/m H:i') }}</span>
            @endif
            @if($order->preparing_at)
            <span class="bg-purple-50 text-purple-700 px-2 py-1 rounded"><i class="fas fa-boxes-stacked mr-1"></i>Preparando: {{ $order->preparing_at->format('d/m H:i') }}</span>
            @endif
            @if($order->dispatched_at)
            <span class="bg-indigo-50 text-indigo-700 px-2 py-1 rounded"><i class="fas fa-truck mr-1"></i>Despachado: {{ $order->dispatched_at->format('d/m H:i') }}</span>
            @endif
            @if($order->received_at)
            <span class="bg-teal-50 text-teal-700 px-2 py-1 rounded"><i class="fas fa-clipboard-check mr-1"></i>Recibido: {{ $order->received_at->format('d/m H:i') }}</span>
            @endif
            @if($order->ready_at)
            <span class="bg-green-50 text-green-700 px-2 py-1 rounded"><i class="fas fa-check-double mr-1"></i>Listo: {{ $order->ready_at->format('d/m H:i') }}</span>
            @endif
            @if($order->delivered_at)
            <span class="bg-gray-100 text-gray-700 px-2 py-1 rounded"><i class="fas fa-handshake mr-1"></i>Entregado: {{ $order->delivered_at->format('d/m H:i') }}</span>
            @endif
            @if($order->cancelled_at)
            <span class="bg-red-50 text-red-700 px-2 py-1 rounded"><i class="fas fa-times mr-1"></i>Cancelado: {{ $order->cancelled_at->format('d/m H:i') }}{{ $order->cancel_reason ? ' — '.$order->cancel_reason : '' }}</span>
            @endif
        </div>
    </div>

    {{-- Bultos del pedido: estado en tienda --}}
    @if($order->transfer && $order->order_packages_total > 0)
    <div class="bg-white rounded-lg shadow p-4 mb-4">
        <h4 class="text-sm font-semibold mb-3"><i class="fas fa-boxes-stacked text-purple-500 mr-2"></i>Bultos del Pedido</h4>
        <div class="grid grid-cols-3 gap-3 text-center">
            <div class="bg-purple-50 rounded-lg p-3">
                <p class="text-2xl font-bold text-purple-700">{{ $order->order_packages_total }}</p>
                <p class="text-[10px] text-purple-600 uppercase">Total asignados</p>
            </div>
            <div class="bg-green-50 rounded-lg p-3">
                <p class="text-2xl font-bold text-green-700">{{ $order->order_packages_in_store }}</p>
                <p class="text-[10px] text-green-600 uppercase">En tienda</p>
            </div>
            <div class="{{ $order->order_packages_sold > 0 ? 'bg-red-50' : 'bg-gray-50' }} rounded-lg p-3">
                <p class="text-2xl font-bold {{ $order->order_packages_sold > 0 ? 'text-red-700' : 'text-gray-400' }}">{{ $order->order_packages_sold }}</p>
                <p class="text-[10px] {{ $order->order_packages_sold > 0 ? 'text-red-600' : 'text-gray-500' }} uppercase">Vendidos (error)</p>
            </div>
        </div>

        @if($order->order_packages_sold > 0)
        <div class="mt-3 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
            <i class="fas fa-exclamation-triangle mr-1"></i>
            <strong>¡Atención!</strong> Algunos bultos del pedido fueron vendidos por error.
            El cajero puede agregar productos de tienda al pedido para reponer.
        </div>
        @endif
    </div>
    @endif

    {{-- Items del pedido --}}
    <div class="bg-white rounded-lg shadow mb-4">
        <div class="p-4 border-b">
            <h4 class="text-sm font-semibold"><i class="fas fa-box text-blue-500 mr-2"></i>Productos del Pedido</h4>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs text-gray-600">Producto</th>
                        <th class="px-4 py-2 text-center text-xs text-gray-600">Pedido</th>
                        <th class="px-4 py-2 text-right text-xs text-gray-600">Precio</th>
                        <th class="px-4 py-2 text-center text-xs text-gray-600">Enviado</th>
                        <th class="px-4 py-2 text-right text-xs text-gray-600">Subtotal</th>
                        <th class="px-4 py-2 text-center text-xs text-gray-600">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach($order->items as $item)
                    @php $has = (float)$item->quantity_sent > 0; @endphp
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $item->variant->product->name }} — {{ $item->variant->name }}</td>
                        <td class="px-4 py-3 text-center"><span class="font-bold">{{ (int)$item->quantity_requested }}</span> <span class="text-[10px] text-gray-400">{{ $item->package_type }}(s)</span></td>
                        <td class="px-4 py-3 text-right text-xs">S/ {{ number_format($item->sale_price, 2) }}/{{ $item->unit }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($has)
                            <span class="font-bold text-green-700">{{ number_format($item->quantity_sent, 3) }} {{ $item->unit }}</span>
                            @else
                            <span class="text-orange-500 text-xs"><i class="fas fa-clock"></i> Pendiente</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if($has) <span class="font-bold">S/ {{ number_format((float)$item->quantity_sent * (float)$item->sale_price, 2) }}</span>
                            @else — @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($has)
                            <span class="bg-green-100 text-green-800 px-2 py-0.5 rounded-full text-[10px]"><i class="fas fa-check"></i> Pesado</span>
                            @else
                            <span class="bg-yellow-100 text-yellow-800 px-2 py-0.5 rounded-full text-[10px]"><i class="fas fa-weight-hanging"></i> Sin pesar</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Acciones --}}
    @if(!in_array($order->status, ['delivered', 'cancelled']))
    <div class="bg-white rounded-lg shadow p-4 mb-4">
        <div class="flex justify-between items-center flex-wrap gap-3">
            <div class="text-sm text-gray-600">
                @if($order->status === 'pending')
                    <i class="fas fa-clock text-yellow-500 mr-1"></i>Confirma el pedido para empezar.
                @elseif($order->status === 'confirmed' && !$order->transfer)
                    <i class="fas fa-truck text-blue-500 mr-1"></i>Crea o vincula un cargamento.
                @elseif($order->status === 'preparing')
                    <i class="fas fa-boxes-stacked text-purple-500 mr-1"></i>Pesa los sacos en el cargamento.
                @elseif($order->status === 'dispatched')
                    <i class="fas fa-truck text-indigo-500 mr-1"></i>Cargamento en camino a tienda.
                @elseif($order->status === 'received')
                    <i class="fas fa-clipboard-check text-teal-500 mr-1"></i>Recibido. Marca como listo cuando proceda.
                @elseif($order->status === 'ready')
                    <i class="fas fa-check-double text-green-500 mr-1"></i>Listo. El cajero entregará al cliente.
                @endif
            </div>

            <div class="flex gap-2 flex-wrap">
                {{-- Confirmar --}}
                @if($order->status === 'pending')
                <form method="POST" action="{{ route('almacen.orders.confirm', $order) }}">
                    @csrf
                    <button type="submit" class="bg-blue-600 text-white px-5 py-2 rounded-lg hover:bg-blue-700 text-sm">
                        <i class="fas fa-check mr-1"></i>Confirmar
                    </button>
                </form>
                @endif

                {{-- Crear cargamento --}}
                @if($order->status === 'confirmed' && !$order->transfer)
                <form method="POST" action="{{ route('almacen.orders.prepare', $order) }}">
                    @csrf
                    <button type="submit" class="bg-purple-600 text-white px-5 py-2 rounded-lg hover:bg-purple-700 text-sm">
                        <i class="fas fa-plus mr-1"></i>Crear Cargamento
                    </button>
                </form>
                @endif

                {{-- Vincular a cargamento existente --}}
                @if(in_array($order->status, ['confirmed', 'preparing']) && !$order->transfer && count($availableTransfers) > 0)
                <div x-data="{ open: false }">
                    <button @click="open = !open" class="bg-indigo-100 text-indigo-700 px-5 py-2 rounded-lg hover:bg-indigo-200 text-sm">
                        <i class="fas fa-link mr-1"></i>Vincular Existente
                    </button>
                    <div x-show="open" class="mt-2 p-3 bg-indigo-50 rounded-lg border">
                        <form method="POST" action="{{ route('almacen.orders.link-transfer', $order) }}" class="flex gap-2">
                            @csrf
                            <select name="transfer_id" required class="flex-1 border-gray-300 rounded text-sm">
                                @foreach($availableTransfers as $t)
                                <option value="{{ $t->id }}">{{ $t->transfer_code }} ({{ $t->created_at->format('H:i') }})</option>
                                @endforeach
                            </select>
                            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded text-sm">Vincular</button>
                        </form>
                    </div>
                </div>
                @endif

                {{-- Ir al cargamento --}}
                @if($order->transfer && in_array($order->status, ['preparing', 'dispatched', 'received']))
                <a href="{{ route('almacen.transfers.show', $order->transfer) }}"
                   class="bg-purple-100 text-purple-700 px-5 py-2 rounded-lg hover:bg-purple-200 text-sm">
                    <i class="fas fa-boxes-stacked mr-1"></i>Ir al Cargamento
                </a>
                @endif

                {{-- Marcar listo --}}
                @if(in_array($order->status, ['preparing', 'dispatched', 'received']))
                <form method="POST" action="{{ route('almacen.orders.ready', $order) }}">
                    @csrf
                    <button type="submit" class="bg-green-600 text-white px-5 py-2 rounded-lg hover:bg-green-700 text-sm"
                            onclick="return confirm('¿Marcar como listo? Se calculará el total real.')">
                        <i class="fas fa-check-double mr-1"></i>Listo para Entrega
                    </button>
                </form>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- Pagos --}}
    @if($order->payments->count() > 0)
    <div class="bg-white rounded-lg shadow">
        <div class="p-4 border-b">
            <h4 class="text-sm font-semibold"><i class="fas fa-money-bill-wave text-green-500 mr-2"></i>Pagos Registrados</h4>
        </div>
        <div class="p-4">
            @foreach($order->payments as $payment)
            <div class="flex justify-between items-center py-2 border-b last:border-0 text-sm">
                <div>
                    <span class="font-bold">S/ {{ number_format($payment->amount, 2) }}</span>
                    <span class="text-xs text-gray-500 ml-2">{{ ucfirst($payment->method) }}</span>
                    <span class="px-2 py-0.5 text-[10px] rounded-full ml-1 {{ $payment->payment_type === 'advance' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800' }}">
                        {{ $payment->payment_type === 'advance' ? 'Adelanto' : 'Pago final' }}
                    </span>
                </div>
                <span class="text-xs text-gray-400">{{ $payment->created_at->format('d/m/Y H:i') }} — {{ $payment->user->name }}</span>
            </div>
            @endforeach

            <div class="mt-3 p-3 bg-gray-50 rounded-lg text-sm flex justify-between">
                <span>Total pagado:</span>
                <span class="font-bold text-green-700">S/ {{ number_format($order->total_paid, 2) }}</span>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
