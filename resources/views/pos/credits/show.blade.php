@extends('layouts.app')

@section('title', 'Fiados - ' . $customer->name)

@section('content')
<div x-data="creditManager()" class="max-w-5xl mx-auto">

    {{-- Cabecera --}}
    <div class="bg-white rounded-lg shadow p-4 mb-4 flex justify-between items-center">
        <div>
            <h3 class="text-lg font-semibold">{{ $customer->name }}</h3>
            <p class="text-sm text-gray-500">
                {{ $customer->phone ?? '' }} {{ $customer->document ? '— ' . $customer->document : '' }}
                — {{ ucfirst($customer->price_type) }}
            </p>
        </div>
        <div class="flex items-center gap-4">
            <div class="bg-red-50 rounded-lg px-4 py-2 text-center">
                <p class="text-xs text-red-600">Deuda Total</p>
                <p class="text-xl font-bold text-red-700" id="total-balance">S/ {{ number_format($pendingBalance, 2) }}</p>
            </div>
            <a href="{{ route('pos.credits.index') }}" class="text-blue-600 hover:underline text-sm">← Volver</a>
        </div>
    </div>

    {{-- Lista de fiados --}}
    @foreach($customer->credits as $credit)
    <div class="bg-white rounded-lg shadow mb-4" id="credit-{{ $credit->id }}">
        <div class="p-4 border-b flex justify-between items-center">
            <div>
                <div class="flex items-center gap-3">
                    <span class="font-mono text-xs text-gray-600">{{ $credit->sale->sale_number }}</span>
                    <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full
                        {{ $credit->status === 'pending' ? 'bg-red-100 text-red-800' : '' }}
                        {{ $credit->status === 'partial' ? 'bg-yellow-100 text-yellow-800' : '' }}
                        {{ $credit->status === 'paid' ? 'bg-green-100 text-green-800' : '' }}
                    ">
                        {{ $credit->status === 'pending' ? 'Pendiente' : '' }}
                        {{ $credit->status === 'partial' ? 'Parcial' : '' }}
                        {{ $credit->status === 'paid' ? 'Pagado' : '' }}
                    </span>
                    <span class="text-xs text-gray-500">{{ $credit->created_at->format('d/m/Y H:i') }}</span>
                </div>
                <div class="mt-1 text-xs text-gray-500">
                    @foreach($credit->sale->items as $item)
                        {{ $item->variant->product->name }} ({{ number_format($item->quantity, 3) }} {{ $item->unit }})
                        @if(!$loop->last) — @endif
                    @endforeach
                </div>
            </div>

            <div class="flex items-center gap-4">
                <div class="text-right">
                    <p class="text-xs text-gray-500">Venta: S/ {{ number_format($credit->sale->total, 2) }}</p>
                    <p class="text-xs text-gray-500">Fiado: S/ {{ number_format($credit->original_amount, 2) }}</p>
                    <p class="text-xs text-gray-500">Pagado: S/ {{ number_format($credit->paid_amount, 2) }}</p>
                    <p class="font-bold text-red-600 credit-balance" data-id="{{ $credit->id }}">
                        Debe: S/ {{ number_format($credit->balance, 2) }}
                    </p>
                </div>

                @if($credit->status !== 'paid')
                <button @click="openPayForm({{ $credit->id }}, {{ $credit->balance }})"
                        class="bg-green-600 text-white px-3 py-1.5 rounded-lg hover:bg-green-700 text-xs">
                    Registrar Pago
                </button>
                @endif
            </div>
        </div>

        {{-- Historial de pagos --}}
        @if($credit->payments->count() > 0)
        <div class="px-4 py-2 bg-gray-50">
            <p class="text-xs font-medium text-gray-600 mb-1">Pagos:</p>
            @foreach($credit->payments as $payment)
            <div class="flex justify-between text-xs text-gray-500 py-1 border-b border-gray-100 last:border-0">
                <span>
                    S/ {{ number_format($payment->amount, 2) }} —
                    {{ ucfirst($payment->method) }}
                    @if($payment->reference) ({{ $payment->reference }}) @endif
                    — {{ $payment->user->name }}
                </span>
                <span>{{ $payment->created_at->format('d/m/Y H:i') }}</span>
            </div>
            @endforeach
        </div>
        @endif
    </div>
    @endforeach

    {{-- Modal pago --}}
    <div x-show="showPayForm" x-transition class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md" @click.away="showPayForm = false">
            <h3 class="text-lg font-semibold mb-4">Registrar Pago de Fiado</h3>

            <div class="mb-3">
                <p class="text-sm text-gray-500">Deuda pendiente:</p>
                <p class="text-xl font-bold text-red-600">S/ <span x-text="payBalance"></span></p>
            </div>

            <div class="space-y-3">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Monto a pagar (S/) *</label>
                    <input type="number" step="0.01" min="0.01" :max="payBalance"
                           x-model="payAmount"
                           x-ref="payAmountInput"
                           class="w-full border-gray-300 rounded-lg text-lg text-center font-bold h-12"
                           placeholder="0.00">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Método *</label>
                    <select x-model="payMethod" class="w-full border-gray-300 rounded-lg text-sm">
                        <option value="cash">Efectivo</option>
                        <option value="transfer">Transferencia</option>
                        <option value="other">Otro</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Referencia</label>
                    <input type="text" x-model="payReference"
                           class="w-full border-gray-300 rounded-lg text-sm" placeholder="N° operación, etc.">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Nota</label>
                    <input type="text" x-model="payNotes"
                           class="w-full border-gray-300 rounded-lg text-sm" placeholder="Opcional">
                </div>
            </div>

            <div x-show="payMessage" x-transition
                 :class="payMessageType === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                 class="mt-3 px-3 py-2 rounded text-sm">
                <span x-text="payMessage"></span>
            </div>

            <div class="flex gap-3 mt-4">
                <button @click="showPayForm = false"
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Cancelar
                </button>
                <button @click="submitPayment()"
                        :disabled="!payAmount || processing"
                        class="flex-1 bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 font-medium disabled:opacity-50">
                    <span x-show="!processing">Pagar</span>
                    <span x-show="processing">...</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function creditManager() {
    return {
        showPayForm: false,
        payCreditId: null,
        payBalance: '0.00',
        payAmount: '',
        payMethod: 'cash',
        payReference: '',
        payNotes: '',
        payMessage: '',
        payMessageType: 'success',
        processing: false,

        openPayForm(creditId, balance) {
            this.payCreditId = creditId;
            this.payBalance = parseFloat(balance).toFixed(2);
            this.payAmount = this.payBalance;
            this.payMethod = 'cash';
            this.payReference = '';
            this.payNotes = '';
            this.payMessage = '';
            this.showPayForm = true;
            this.$nextTick(() => this.$refs.payAmountInput?.focus());
        },

        async submitPayment() {
            if (!this.payAmount || this.processing) return;
            this.processing = true;
            this.payMessage = '';

            try {
                const res = await fetch('/pos/credits/' + this.payCreditId + '/pay', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        amount: this.payAmount,
                        method: this.payMethod,
                        reference: this.payReference,
                        notes: this.payNotes,
                    }),
                });

                const data = await res.json();

                if (data.success) {
                    this.payMessage = data.message;
                    this.payMessageType = 'success';

                    setTimeout(() => {
                        this.showPayForm = false;
                        location.reload();
                    }, 1000);
                } else {
                    this.payMessage = data.message;
                    this.payMessageType = 'error';
                }
            } catch (e) {
                this.payMessage = 'Error de conexión.';
                this.payMessageType = 'error';
            }

            this.processing = false;
        },
    }
}
</script>
@endsection