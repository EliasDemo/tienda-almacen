@extends('layouts.app')

@section('title', $customer->name)

@section('content')
<div class="max-w-5xl mx-auto">

    {{-- Perfil --}}
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex justify-between items-start">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-full flex items-center justify-center text-xl font-bold text-white"
                     style="background-color: var(--brand-primary);">
                    {{ strtoupper(substr($customer->name, 0, 2)) }}
                </div>
                <div>
                    <h3 class="text-xl font-bold text-gray-800">{{ $customer->name }}</h3>
                    <div class="flex flex-wrap items-center gap-3 mt-1 text-sm text-gray-500">
                        @if($customer->phone)
                        <span><i class="fas fa-phone mr-1"></i>{{ $customer->phone }}</span>
                        @endif
                        @if($customer->document)
                        <span><i class="fas fa-id-card mr-1"></i>{{ $customer->document }}</span>
                        @endif
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium
                            {{ $customer->price_type === 'mayorista' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600' }}">
                            {{ ucfirst($customer->price_type) }}
                        </span>
                        @if((float)$customer->discount_percent > 0)
                        <span class="bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full text-xs font-medium">
                            {{ $customer->discount_percent }}% desc.
                        </span>
                        @endif
                        @if((float)$customer->credit_limit > 0)
                        <span class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full text-xs font-medium">
                            <i class="fas fa-credit-card mr-1"></i>Límite: S/ {{ number_format($customer->credit_limit, 2) }}
                        </span>
                        @endif
                        @if($customer->credit_blocked)
                        <span class="bg-red-100 text-red-700 px-2 py-0.5 rounded-full text-xs font-medium">
                            <i class="fas fa-ban mr-1"></i>Crédito bloqueado
                        </span>
                        @endif
                    </div>
                    @if($customer->notes)
                    <p class="text-xs text-gray-400 mt-1"><i class="fas fa-sticky-note mr-1"></i>{{ $customer->notes }}</p>
                    @endif
                </div>
            </div>
            <div class="flex items-center gap-3">
                <button onclick="toggleCreditBlock()"
                        class="text-sm px-3 py-1.5 rounded-lg flex items-center gap-1
                        {{ $customer->credit_blocked ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-red-100 text-red-700 hover:bg-red-200' }}">
                    <i class="fas {{ $customer->credit_blocked ? 'fa-lock-open' : 'fa-ban' }}"></i>
                    {{ $customer->credit_blocked ? 'Desbloquear' : 'Bloquear' }} Crédito
                </button>
                <a href="{{ route('admin.customers.index') }}" class="text-sm text-blue-600 hover:underline">
                    <i class="fas fa-arrow-left mr-1"></i>Volver
                </a>
            </div>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <p class="text-xs text-gray-500"><i class="fas fa-shopping-bag mr-1"></i>Compras</p>
            <p class="text-2xl font-bold text-gray-800">{{ $stats['total_purchases'] }}</p>
        </div>
        <div class="bg-green-50 rounded-lg shadow p-4 text-center">
            <p class="text-xs text-green-600"><i class="fas fa-coins mr-1"></i>Total Gastado</p>
            <p class="text-2xl font-bold text-green-700">S/ {{ number_format($stats['total_spent'], 2) }}</p>
        </div>
        <div class="bg-orange-50 rounded-lg shadow p-4 text-center">
            <p class="text-xs text-orange-600"><i class="fas fa-hand-holding-dollar mr-1"></i>Total Fiado</p>
            <p class="text-2xl font-bold text-orange-700">S/ {{ number_format($stats['total_credited'], 2) }}</p>
        </div>
        <div class="bg-blue-50 rounded-lg shadow p-4 text-center">
            <p class="text-xs text-blue-600"><i class="fas fa-money-bill-transfer mr-1"></i>Pagado</p>
            <p class="text-2xl font-bold text-blue-700">S/ {{ number_format($stats['total_paid_debt'], 2) }}</p>
        </div>
        <div class="{{ $stats['pending_debt'] > 0 ? 'bg-red-50' : 'bg-green-50' }} rounded-lg shadow p-4 text-center">
            <p class="text-xs {{ $stats['pending_debt'] > 0 ? 'text-red-600' : 'text-green-600' }}">
                <i class="fas {{ $stats['pending_debt'] > 0 ? 'fa-exclamation-triangle' : 'fa-check-circle' }} mr-1"></i>Deuda
            </p>
            <p class="text-2xl font-bold {{ $stats['pending_debt'] > 0 ? 'text-red-700' : 'text-green-700' }}">
                S/ {{ number_format($stats['pending_debt'], 2) }}
            </p>
        </div>
    </div>

    {{-- Fiados pendientes --}}
    @if($customer->credits->where('status', '!=', 'paid')->count() > 0)
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="p-4 border-b bg-red-50 rounded-t-lg">
            <h4 class="font-semibold text-red-800">
                <i class="fas fa-exclamation-triangle mr-2"></i>Fiados Pendientes ({{ $customer->credits->where('status', '!=', 'paid')->count() }})
            </h4>
        </div>
        <div class="divide-y">
            @foreach($customer->credits->where('status', '!=', 'paid') as $credit)
            <div class="p-4 flex justify-between items-center">
                <div>
                    <span class="font-mono text-xs text-gray-500">{{ $credit->sale->sale_number ?? '—' }}</span>
                    <span class="ml-2 text-xs text-gray-400">{{ $credit->created_at->format('d/m/Y') }}</span>
                    <span class="ml-2 inline-flex px-2 py-0.5 text-[10px] font-medium rounded-full
                        {{ $credit->status === 'pending' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700' }}">
                        {{ $credit->status === 'pending' ? 'Pendiente' : 'Parcial' }}
                    </span>
                </div>
                <div class="text-right text-sm">
                    <span class="text-gray-500">Fiado: S/ {{ number_format($credit->original_amount, 2) }}</span>
                    <span class="mx-1 text-gray-300">|</span>
                    <span class="text-green-600">Pagado: S/ {{ number_format($credit->paid_amount, 2) }}</span>
                    <span class="mx-1 text-gray-300">|</span>
                    <span class="font-bold text-red-600">Debe: S/ {{ number_format($credit->balance, 2) }}</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Historial --}}
    <div class="mb-4">
        <h4 class="text-lg font-semibold text-gray-800">
            <i class="fas fa-clock-rotate-left mr-2 text-blue-500"></i>Historial de Compras
        </h4>
    </div>

    @forelse($salesByDate as $date => $sales)
    <div class="bg-white rounded-lg shadow mb-4">
        <div class="p-3 border-b bg-gray-50 rounded-t-lg flex justify-between items-center">
            <div class="flex items-center gap-3">
                <div class="bg-blue-100 rounded-lg p-2">
                    <i class="fas fa-calendar-day text-blue-600"></i>
                </div>
                <div>
                    <p class="font-semibold text-gray-800">{{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</p>
                    <p class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($date)->translatedFormat('l') }}</p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <span class="text-xs text-gray-500">{{ $sales->count() }} {{ $sales->count() === 1 ? 'venta' : 'ventas' }}</span>
                <span class="font-bold text-green-700">S/ {{ number_format($sales->sum('total'), 2) }}</span>
            </div>
        </div>
        <div class="divide-y">
            @foreach($sales as $sale)
            <div class="p-4 hover:bg-gray-50 transition-colors">
                <div class="flex justify-between items-start">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="font-mono text-xs text-gray-500">{{ $sale->sale_number }}</span>
                            <span class="text-xs text-gray-400">{{ $sale->created_at->format('H:i') }}</span>
                            <span class="text-xs text-gray-400">— {{ $sale->user->name }}</span>
                            @php $saleCredit = $customer->credits->where('sale_id', $sale->id)->first(); @endphp
                            @if($saleCredit)
                            <span class="inline-flex px-2 py-0.5 text-[10px] font-medium rounded-full
                                {{ $saleCredit->status === 'paid' ? 'bg-green-100 text-green-700' : 'bg-orange-100 text-orange-700' }}">
                                <i class="fas fa-hand-holding-dollar mr-0.5"></i>
                                {{ $saleCredit->status === 'paid' ? 'Fiado pagado' : 'Fiado: S/ ' . number_format($saleCredit->balance, 2) }}
                            </span>
                            @endif
                        </div>
                        <div class="space-y-0.5">
                            @foreach($sale->items as $item)
                            <div class="text-xs text-gray-600">
                                <i class="fas {{ $item->sell_mode === 'bulk' ? 'fa-box' : 'fa-weight-hanging' }} mr-1 text-gray-400 text-[10px]"></i>
                                {{ $item->variant->product->name }} — {{ $item->variant->name }}
                                <span class="text-gray-400">({{ number_format($item->quantity, 3) }} {{ $item->unit }} x S/ {{ number_format($item->unit_price, 2) }})</span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="font-bold text-gray-800">S/ {{ number_format($sale->total, 2) }}</p>
                        <p class="text-[10px] text-gray-400">
                            @foreach($sale->payments as $p)
                                <i class="fas {{ $p->method === 'cash' ? 'fa-money-bill' : ($p->method === 'transfer' ? 'fa-building-columns' : 'fa-credit-card') }} mr-0.5"></i>
                                {{ ucfirst($p->method) }}: S/ {{ number_format($p->amount, 2) }}
                                @if(!$loop->last) | @endif
                            @endforeach
                        </p>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @empty
    <div class="bg-white rounded-lg shadow p-12 text-center">
        <i class="fas fa-receipt text-4xl text-gray-300 mb-3"></i>
        <p class="text-gray-400">Este cliente no tiene compras registradas.</p>
    </div>
    @endforelse
</div>

<form id="toggle-credit-form" method="POST" action="{{ route('admin.customers.toggle-credit', $customer) }}" class="hidden">
    @csrf @method('PATCH')
    <input type="hidden" name="reason" id="block-reason">
</form>

<script>
function toggleCreditBlock() {
    @if($customer->credit_blocked)
        Swal.fire({
            title: '<i class="fas fa-lock-open text-green-500 mr-2"></i>¿Desbloquear crédito?',
            text: '{{ $customer->name }} podrá volver a comprar fiado.',
            icon: 'question',
            showCancelButton: true, confirmButtonColor: '#16A34A', cancelButtonColor: '#6B7280',
            confirmButtonText: '<i class="fas fa-lock-open mr-1"></i> Desbloquear',
            cancelButtonText: 'Cancelar', reverseButtons: true,
        }).then((r) => { if (r.isConfirmed) document.getElementById('toggle-credit-form').submit(); });
    @else
        Swal.fire({
            title: '<i class="fas fa-ban text-red-500 mr-2"></i>¿Bloquear crédito?',
            html: `
                <p class="text-sm text-gray-600 mb-3">{{ $customer->name }} no podrá comprar fiado, solo al contado.</p>
                <div class="text-left">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-comment mr-1 text-gray-400"></i>Motivo
                    </label>
                    <input id="swal-block-reason" class="swal2-input !ml-0 !mr-0" placeholder="Motivo (opcional)" style="width:100%;margin:0;">
                </div>
            `,
            icon: 'warning',
            showCancelButton: true, confirmButtonColor: '#DC2626', cancelButtonColor: '#6B7280',
            confirmButtonText: '<i class="fas fa-ban mr-1"></i> Bloquear',
            cancelButtonText: 'Cancelar', reverseButtons: true,
            preConfirm: () => {
                document.getElementById('block-reason').value = document.getElementById('swal-block-reason').value;
            }
        }).then((r) => { if (r.isConfirmed) document.getElementById('toggle-credit-form').submit(); });
    @endif
}
</script>
@endsection