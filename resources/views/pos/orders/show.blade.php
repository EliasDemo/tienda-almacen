@extends('layouts.app')

@section('title', 'Pedido ' . $order->request_code)

@section('content')
<div class="max-w-5xl mx-auto">

    {{-- Alerta de caja --}}
    @if(!$register)
    <div class="mb-4 p-3 bg-red-50 border border-red-300 rounded-lg flex items-center gap-2 text-sm text-red-700">
        <i class="fas fa-cash-register text-lg"></i>
        <div>
            <strong>No tienes caja abierta.</strong> No podrás registrar pagos ni entregar pedidos.
            <a href="{{ route('pos.open-register') }}" class="underline font-bold ml-1">Abrir caja →</a>
        </div>
    </div>
    @endif

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
                <p class="text-xs text-gray-500 mt-1">{{ $order->created_at->format('d/m/Y H:i') }} — {{ $order->user->name }}</p>
            </div>
            <div class="flex gap-3 items-center">
                @if($order->status === 'delivered')
                <a href="{{ route('pos.orders.final-receipt', $order) }}" target="_blank"
                   class="text-sm text-green-600 hover:underline font-medium">
                    <i class="fas fa-file-invoice mr-1"></i>Boleta Final
                </a>
                @endif
                <a href="{{ route('pos.orders.receipt', $order) }}" target="_blank"
                   class="text-sm text-blue-600 hover:underline">
                    <i class="fas fa-print mr-1"></i>Boleta
                </a>
                <a href="{{ route('pos.orders.index') }}" class="text-sm text-gray-500 hover:underline">
                    <i class="fas fa-arrow-left mr-1"></i>Volver
                </a>
            </div>
        </div>

        {{-- Info en tarjetas --}}
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mt-4">
            <div class="bg-gray-50 rounded-lg p-3">
                <p class="text-[10px] text-gray-500 uppercase tracking-wide">Cliente</p>
                <p class="font-bold text-sm">{{ $order->customer->name }}</p>
                @if($order->customer->phone)
                <p class="text-xs text-gray-400">{{ $order->customer->phone }}</p>
                @endif
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
                <p class="text-[10px] text-gray-500 uppercase tracking-wide">Entrega</p>
                <p class="font-bold text-sm {{ $order->is_late ? 'text-red-600' : '' }}">
                    {{ $order->delivery_date?->format('d/m/Y') ?? '—' }}
                    @if($order->is_late) <i class="fas fa-exclamation-triangle text-xs"></i> @endif
                </p>
            </div>
            <div class="bg-green-50 rounded-lg p-3">
                <p class="text-[10px] text-green-600 uppercase tracking-wide">Adelanto</p>
                <p class="font-bold text-lg text-green-700">S/ {{ number_format($order->advance_amount, 2) }}</p>
            </div>
            <div class="{{ (float)$order->real_total > 0 ? 'bg-blue-50' : 'bg-orange-50' }} rounded-lg p-3">
                <p class="text-[10px] {{ (float)$order->real_total > 0 ? 'text-blue-600' : 'text-orange-600' }} uppercase tracking-wide">Total</p>
                @if((float)$order->real_total > 0)
                <p class="font-bold text-lg">S/ {{ number_format($order->real_total, 2) }}</p>
                @else
                <p class="text-sm text-orange-600 font-medium"><i class="fas fa-clock mr-1"></i>Pendiente</p>
                @endif
            </div>
            <div class="{{ $order->remaining_amount > 0 ? 'bg-red-50' : 'bg-green-50' }} rounded-lg p-3">
                <p class="text-[10px] {{ $order->remaining_amount > 0 ? 'text-red-600' : 'text-green-600' }} uppercase tracking-wide">Saldo</p>
                @if((float)$order->real_total > 0)
                <p class="font-bold text-lg {{ $order->remaining_amount > 0 ? 'text-red-700' : 'text-green-700' }}">
                    S/ {{ number_format($order->remaining_amount, 2) }}
                </p>
                @else
                <p class="text-xs text-gray-400">Sin calcular</p>
                @endif
            </div>
        </div>

        {{-- Notas --}}
        @if($order->customer_notes)
        <div class="mt-3 p-3 bg-yellow-50 border-l-4 border-yellow-400 rounded-r-lg">
            <p class="text-xs text-yellow-800"><strong>Cliente:</strong> {{ $order->customer_notes }}</p>
        </div>
        @endif
        @if($order->notes)
        <div class="mt-2 p-3 bg-gray-50 border-l-4 border-gray-300 rounded-r-lg">
            <p class="text-xs text-gray-600"><strong>Interno:</strong> {{ $order->notes }}</p>
        </div>
        @endif

        {{-- Cargamento vinculado --}}
        @if($order->transfer)
        <div class="mt-3 p-3 bg-indigo-50 border border-indigo-200 rounded-lg flex justify-between items-center">
            <div class="text-sm text-indigo-700">
                <i class="fas fa-truck mr-1"></i>
                Cargamento: <strong>{{ $order->transfer->transfer_code }}</strong>
                — <span class="capitalize">{{ str_replace('_', ' ', $order->transfer->status) }}</span>
            </div>
        </div>
        @endif

        {{-- Timeline --}}
        <div class="mt-3 flex flex-wrap gap-2 text-[10px]">
            @if($order->confirmed_at)
            <span class="bg-blue-50 text-blue-700 px-2 py-1 rounded"><i class="fas fa-check mr-1"></i>{{ $order->confirmed_at->format('d/m H:i') }}</span>
            @endif
            @if($order->preparing_at)
            <span class="bg-purple-50 text-purple-700 px-2 py-1 rounded"><i class="fas fa-boxes-stacked mr-1"></i>{{ $order->preparing_at->format('d/m H:i') }}</span>
            @endif
            @if($order->dispatched_at)
            <span class="bg-indigo-50 text-indigo-700 px-2 py-1 rounded"><i class="fas fa-truck mr-1"></i>{{ $order->dispatched_at->format('d/m H:i') }}</span>
            @endif
            @if($order->ready_at)
            <span class="bg-green-50 text-green-700 px-2 py-1 rounded"><i class="fas fa-check-double mr-1"></i>{{ $order->ready_at->format('d/m H:i') }}</span>
            @endif
            @if($order->delivered_at)
            <span class="bg-gray-100 text-gray-700 px-2 py-1 rounded"><i class="fas fa-handshake mr-1"></i>{{ $order->delivered_at->format('d/m H:i') }}</span>
            @endif
            @if($order->cancelled_at)
            <span class="bg-red-50 text-red-700 px-2 py-1 rounded"><i class="fas fa-times mr-1"></i>{{ $order->cancelled_at->format('d/m H:i') }}{{ $order->cancel_reason ? ' — '.$order->cancel_reason : '' }}</span>
            @endif
        </div>
    </div>

    {{-- Productos del pedido --}}
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
                    @php
                        $has = (float)$item->quantity_sent > 0;
                        $sub = $has ? round((float)$item->quantity_sent * (float)$item->sale_price, 2) : 0;
                    @endphp
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $item->variant->product->name }} — {{ $item->variant->name }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="font-bold">{{ (int)$item->quantity_requested }}</span>
                            <span class="text-[10px] text-gray-400">{{ $item->package_type ?? 'saco' }}(s)</span>
                        </td>
                        <td class="px-4 py-3 text-right text-xs">S/ {{ number_format($item->sale_price, 2) }}/{{ $item->unit }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($has)
                            <span class="font-bold text-green-700">{{ number_format($item->quantity_sent, $item->unit === 'kg' ? 3 : 0) }} {{ $item->unit }}</span>
                            @else
                            <span class="text-orange-500 text-xs"><i class="fas fa-clock"></i> Pendiente</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if($has) <span class="font-bold">S/ {{ number_format($sub, 2) }}</span>
                            @else <span class="text-gray-400">—</span> @endif
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
                @php
                    $totalReal = $order->items->sum(fn($i) => (float)$i->quantity_sent > 0 ? round((float)$i->quantity_sent * (float)$i->sale_price, 2) : 0);
                    $allWeighed = $order->items->every(fn($i) => (float)$i->quantity_sent > 0);
                @endphp
                @if($allWeighed && $totalReal > 0)
                <tfoot>
                    <tr class="bg-blue-50">
                        <td colspan="4" class="px-4 py-3 text-right font-bold text-blue-800">TOTAL REAL:</td>
                        <td class="px-4 py-3 text-right font-bold text-blue-800 text-lg">S/ {{ number_format($totalReal, 2) }}</td>
                        <td></td>
                    </tr>
                    <tr class="bg-green-50">
                        <td colspan="4" class="px-4 py-2 text-right text-xs text-green-800">Total pagado:</td>
                        <td class="px-4 py-2 text-right text-xs font-bold text-green-800">- S/ {{ number_format($order->total_paid, 2) }}</td>
                        <td></td>
                    </tr>
                    @php $saldo = max(0, $totalReal - $order->total_paid); @endphp
                    <tr class="{{ $saldo > 0 ? 'bg-red-50' : 'bg-green-50' }}">
                        <td colspan="4" class="px-4 py-2 text-right font-bold {{ $saldo > 0 ? 'text-red-800' : 'text-green-800' }}">SALDO:</td>
                        <td class="px-4 py-2 text-right font-bold text-lg {{ $saldo > 0 ? 'text-red-800' : 'text-green-800' }}">S/ {{ number_format($saldo, 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
                @elseif(!$allWeighed)
                <tfoot class="bg-orange-50">
                    <tr>
                        <td colspan="6" class="px-4 py-3 text-center text-xs text-orange-700">
                            <i class="fas fa-info-circle mr-1"></i>El total se calculará cuando almacén pese y despache los productos.
                        </td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

    {{-- Bultos del pedido vendidos por error --}}
    @if($order->order_packages_sold > 0)
    <div class="bg-red-50 border border-red-300 rounded-lg shadow p-4 mb-4">
        <h4 class="text-sm font-bold text-red-700 mb-2">
            <i class="fas fa-exclamation-triangle mr-1"></i>
            {{ $order->order_packages_sold }} bulto(s) del pedido fueron vendidos por error
        </h4>
        <p class="text-xs text-red-600 mb-3">
            Puedes reponer escaneando un producto que tengas en tienda y asignándolo al pedido.
        </p>
        @if($register)
        <button onclick="addStoreItem()" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 text-sm">
            <i class="fas fa-qrcode mr-1"></i>Escanear producto de tienda para reponer
        </button>
        <form id="store-item-form" method="POST" action="{{ route('pos.orders.add-store-item', $order) }}" class="hidden">
            @csrf
            <input type="hidden" name="package_uuid" id="store-pkg-uuid">
            <input type="hidden" name="quantity" id="store-pkg-qty">
        </form>
        @else
        <p class="text-xs text-red-500"><i class="fas fa-lock mr-1"></i>Abre una caja para poder reponer.</p>
        @endif
    </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

        {{-- Pagos --}}
        <div class="bg-white rounded-lg shadow">
            <div class="p-4 border-b">
                <h4 class="text-sm font-semibold"><i class="fas fa-money-bill-wave text-green-500 mr-2"></i>Pagos / Adelantos</h4>
            </div>
            <div class="p-4">
                @forelse($order->payments as $payment)
                <div class="flex justify-between items-center py-2 border-b last:border-0">
                    <div>
                        <p class="text-sm font-medium">S/ {{ number_format($payment->amount, 2) }}</p>
                        <p class="text-xs text-gray-500">
                            {{ $payment->created_at->format('d/m/Y H:i') }} — {{ ucfirst($payment->method) }}
                            {{ $payment->reference ? '('.$payment->reference.')' : '' }}
                        </p>
                        <p class="text-xs text-gray-400">{{ $payment->user->name }}</p>
                    </div>
                    <span class="px-2 py-0.5 text-xs rounded-full {{ $payment->payment_type === 'advance' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800' }}">
                        {{ $payment->payment_type === 'advance' ? 'Adelanto' : 'Pago final' }}
                    </span>
                </div>
                @empty
                <p class="text-gray-400 text-sm text-center py-4">Sin pagos registrados.</p>
                @endforelse

                <div class="mt-3 p-3 bg-gray-50 rounded-lg text-sm space-y-1">
                    <div class="flex justify-between">
                        <span>Total pagado:</span>
                        <span class="font-bold text-green-700">S/ {{ number_format($order->total_paid, 2) }}</span>
                    </div>
                    @if((float)$order->real_total > 0)
                    <div class="flex justify-between">
                        <span>Total real:</span>
                        <span class="font-bold">S/ {{ number_format($order->real_total, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Saldo:</span>
                        <span class="font-bold {{ $order->remaining_amount > 0 ? 'text-red-600' : 'text-green-600' }}">
                            S/ {{ number_format($order->remaining_amount, 2) }}
                        </span>
                    </div>
                    @endif
                </div>

                {{-- Botón registrar pago --}}
                @if(!in_array($order->status, ['cancelled']) && (float)$order->real_total > 0 && $order->remaining_amount > 0)
                    @if($register)
                    <div class="mt-4 border-t pt-4">
                        <button onclick="addPayment()" class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 text-sm">
                            <i class="fas fa-plus mr-1"></i>Registrar Pago
                        </button>
                        <form id="payment-form" method="POST" action="{{ route('pos.orders.payment', $order) }}" class="hidden">
                            @csrf
                            <input type="hidden" name="amount" id="pay-amount">
                            <input type="hidden" name="method" id="pay-method">
                            <input type="hidden" name="reference" id="pay-reference">
                        </form>
                    </div>
                    @else
                    <div class="mt-4 p-3 bg-red-50 rounded-lg text-xs text-red-700 text-center">
                        <i class="fas fa-cash-register mr-1"></i>Abre una caja para poder registrar pagos.
                        <a href="{{ route('pos.open-register') }}" class="underline font-bold">Abrir caja</a>
                    </div>
                    @endif
                @elseif(!in_array($order->status, ['cancelled']) && (float)$order->real_total <= 0)
                <div class="mt-4 p-3 bg-orange-50 rounded-lg text-xs text-orange-700 text-center">
                    <i class="fas fa-info-circle mr-1"></i>No se puede registrar pagos hasta que almacén pese los productos.
                </div>
                @endif
            </div>
        </div>

        {{-- Acciones --}}
        <div class="bg-white rounded-lg shadow">
            <div class="p-4 border-b">
                <h4 class="text-sm font-semibold"><i class="fas fa-cogs text-gray-500 mr-2"></i>Acciones</h4>
            </div>
            <div class="p-4 space-y-3">

                {{-- Boletas --}}
                <a href="{{ route('pos.orders.receipt', $order) }}" target="_blank"
                   class="w-full bg-blue-50 text-blue-600 px-4 py-3 rounded-lg hover:bg-blue-100 text-sm flex items-center justify-center gap-2 border border-blue-200">
                    <i class="fas fa-print"></i> Boleta del Pedido
                </a>

                @if($order->status === 'delivered')
                <a href="{{ route('pos.orders.final-receipt', $order) }}" target="_blank"
                   class="w-full bg-green-50 text-green-600 px-4 py-3 rounded-lg hover:bg-green-100 text-sm flex items-center justify-center gap-2 border border-green-200">
                    <i class="fas fa-file-invoice"></i> Boleta Final de Entrega
                </a>
                @endif

                {{-- Entregar --}}
                @if($order->status === 'ready' && $allWeighed && $order->remaining_amount <= 0)
                    @if($register)
                    <form method="POST" action="{{ route('pos.orders.deliver', $order) }}">
                        @csrf
                        <button type="submit" onclick="return confirm('¿Marcar como entregado al cliente?')"
                                class="w-full bg-green-600 text-white px-4 py-3 rounded-lg hover:bg-green-700 text-sm flex items-center justify-center gap-2">
                            <i class="fas fa-handshake"></i> Entregar al Cliente
                        </button>
                    </form>
                    @else
                    <div class="p-3 bg-red-50 rounded-lg text-xs text-red-700 text-center border border-red-200">
                        <i class="fas fa-cash-register mr-1"></i>Abre una caja para entregar.
                        <a href="{{ route('pos.open-register') }}" class="underline font-bold">Abrir caja</a>
                    </div>
                    @endif
                @elseif($order->status === 'ready' && $allWeighed && $order->remaining_amount > 0)
                <div class="p-3 bg-red-50 rounded-lg text-xs text-red-700 text-center border border-red-200">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    Cobra S/ {{ number_format($order->remaining_amount, 2) }} antes de entregar.
                </div>
                @elseif($order->status === 'ready' && !$allWeighed)
                <div class="p-3 bg-orange-50 rounded-lg text-xs text-orange-700 text-center">
                    <i class="fas fa-weight-hanging mr-1"></i>Faltan productos por pesar.
                </div>
                @endif

                {{-- Cancelar --}}
                @if(!in_array($order->status, ['delivered', 'cancelled']))
                <button onclick="cancelOrder()" class="w-full bg-red-50 text-red-600 px-4 py-3 rounded-lg hover:bg-red-100 text-sm flex items-center justify-center gap-2 border border-red-200">
                    <i class="fas fa-times-circle"></i> Cancelar Pedido
                </button>
                <form id="cancel-form" method="POST" action="{{ route('pos.orders.cancel', $order) }}" class="hidden">
                    @csrf
                    <input type="hidden" name="reason" id="cancel-reason">
                </form>
                @endif

                {{-- Estado informativo --}}
                @if(in_array($order->status, ['delivered', 'cancelled']))
                <div class="text-center text-gray-400 py-4">
                    <i class="fas {{ $order->status === 'delivered' ? 'fa-check-circle text-green-400' : 'fa-times-circle text-red-400' }} text-3xl mb-2"></i>
                    <p class="text-sm">Pedido {{ $order->status_label }}</p>
                </div>
                @endif

                @if($order->status === 'pending')
                <div class="p-3 bg-yellow-50 rounded-lg text-xs text-yellow-700">
                    <i class="fas fa-clock mr-1"></i>Esperando confirmación de almacén.
                </div>
                @elseif($order->status === 'confirmed')
                <div class="p-3 bg-blue-50 rounded-lg text-xs text-blue-700">
                    <i class="fas fa-check mr-1"></i>Confirmado. Esperando preparación.
                </div>
                @elseif($order->status === 'preparing')
                <div class="p-3 bg-purple-50 rounded-lg text-xs text-purple-700">
                    <i class="fas fa-boxes-stacked mr-1"></i>Almacén pesando los sacos.
                </div>
                @elseif($order->status === 'dispatched')
                <div class="p-3 bg-indigo-50 rounded-lg text-xs text-indigo-700">
                    <i class="fas fa-truck mr-1"></i>Cargamento en camino a tienda.
                </div>
                @elseif($order->status === 'received')
                <div class="p-3 bg-teal-50 rounded-lg text-xs text-teal-700">
                    <i class="fas fa-clipboard-check mr-1"></i>Recibido en tienda. Esperando que se marque como listo.
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
function addPayment() {
    const max = {{ $order->remaining_amount ?? 0 }};
    Swal.fire({
        title: '<i class="fas fa-money-bill-wave text-green-500 mr-2"></i>Registrar Pago',
        html: `
            <div class="text-left space-y-3 mt-2">
                <p class="text-sm text-gray-600">Saldo: <strong class="text-red-600">S/ ${max.toFixed(2)}</strong></p>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Monto (S/) *</label>
                    <input id="swal-amount" type="number" step="0.01" min="0.01" max="${max}"
                           class="swal2-input" style="margin:0;width:100%;" value="${max.toFixed(2)}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Método</label>
                    <select id="swal-method" class="swal2-select" style="margin:0;width:100%;">
                        <option value="cash">Efectivo</option>
                        <option value="transfer">Transferencia</option>
                        <option value="other">Otro</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Referencia</label>
                    <input id="swal-ref" type="text" class="swal2-input" style="margin:0;width:100%;" placeholder="N° operación (opcional)">
                </div>
            </div>
        `,
        showCancelButton: true, confirmButtonColor: '#16A34A', cancelButtonColor: '#6B7280',
        confirmButtonText: '<i class="fas fa-check mr-1"></i> Registrar',
        cancelButtonText: 'Cancelar', reverseButtons: true,
        preConfirm: () => {
            const amount = parseFloat(document.getElementById('swal-amount').value);
            if (!amount || amount <= 0) { Swal.showValidationMessage('Monto inválido'); return false; }
            if (amount > max + 0.01) { Swal.showValidationMessage('Excede el saldo'); return false; }
            return { amount, method: document.getElementById('swal-method').value, reference: document.getElementById('swal-ref').value };
        }
    }).then((r) => {
        if (r.isConfirmed) {
            document.getElementById('pay-amount').value = r.value.amount;
            document.getElementById('pay-method').value = r.value.method;
            document.getElementById('pay-reference').value = r.value.reference;
            document.getElementById('payment-form').submit();
        }
    });
}

function addStoreItem() {
    Swal.fire({
        title: '<i class="fas fa-qrcode text-purple-500 mr-2"></i>Reponer desde Tienda',
        html: `
            <div class="text-left space-y-3 mt-2">
                <p class="text-xs text-gray-600">Escanea o ingresa el UUID del bulto que está en tienda.</p>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">UUID del bulto *</label>
                    <input id="swal-uuid" type="text" class="swal2-input" style="margin:0;width:100%;" placeholder="Escanear QR o escribir UUID" autofocus>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Peso / Cantidad a asignar *</label>
                    <input id="swal-qty" type="number" step="0.001" min="0.001" class="swal2-input" style="margin:0;width:100%;" placeholder="0.000">
                </div>
            </div>
        `,
        showCancelButton: true, confirmButtonColor: '#7C3AED', cancelButtonColor: '#6B7280',
        confirmButtonText: '<i class="fas fa-plus mr-1"></i> Agregar al Pedido',
        cancelButtonText: 'Cancelar', reverseButtons: true,
        preConfirm: () => {
            const uuid = document.getElementById('swal-uuid').value.trim();
            const qty = parseFloat(document.getElementById('swal-qty').value);
            if (!uuid) { Swal.showValidationMessage('Ingresa el UUID'); return false; }
            if (!qty || qty <= 0) { Swal.showValidationMessage('Ingresa una cantidad'); return false; }
            return { uuid, qty };
        }
    }).then((r) => {
        if (r.isConfirmed) {
            document.getElementById('store-pkg-uuid').value = r.value.uuid;
            document.getElementById('store-pkg-qty').value = r.value.qty;
            document.getElementById('store-item-form').submit();
        }
    });
}

function cancelOrder() {
    Swal.fire({
        title: '<i class="fas fa-times-circle text-red-500 mr-2"></i>Cancelar Pedido',
        html: `
            <div class="text-left mt-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Motivo</label>
                <input id="swal-reason" type="text" class="swal2-input" style="margin:0;width:100%;" placeholder="Motivo (opcional)">
                @if((float)$order->advance_amount > 0)
                <p class="mt-3 text-sm text-red-600"><i class="fas fa-exclamation-triangle mr-1"></i>
                    Adelanto de S/ {{ number_format($order->advance_amount, 2) }} pendiente de devolución.</p>
                @endif
            </div>
        `,
        icon: 'warning', showCancelButton: true, confirmButtonColor: '#DC2626', cancelButtonColor: '#6B7280',
        confirmButtonText: '<i class="fas fa-times-circle mr-1"></i> Cancelar Pedido',
        cancelButtonText: 'No, volver', reverseButtons: true,
    }).then((r) => {
        if (r.isConfirmed) {
            document.getElementById('cancel-reason').value = document.getElementById('swal-reason').value;
            document.getElementById('cancel-form').submit();
        }
    });
}
</script>
@endsection
