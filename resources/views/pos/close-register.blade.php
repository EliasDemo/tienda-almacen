@extends('layouts.app')

@section('title', 'Cerrar Caja')

@section('content')
<div class="max-w-lg mx-auto mt-10">
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4 text-center">
            <i class="fas fa-lock text-red-500 mr-2"></i>Cerrar Caja
        </h3>

        @php
            $totalSales = 0;
            $totalOther = 0;

            foreach ($register->sales as $sale) {
                if ($sale->status === 'completed') {
                    $totalSales += (float) $sale->total;
                    foreach ($sale->payments as $p) {
                        if ($p->method !== 'cash') {
                            $totalOther += (float) $p->amount;
                        }
                    }
                }
            }

            $totalCash = $totalSales - $totalOther;

            // Cobros de fiados
            $creditPaymentsCash = \App\Models\CreditPayment::where('cash_register_id', $register->id)
                ->where('method', 'cash')->sum('amount');
            $creditPaymentsOther = \App\Models\CreditPayment::where('cash_register_id', $register->id)
                ->where('method', '!=', 'cash')->sum('amount');
            $totalCreditPayments = (float) $creditPaymentsCash + (float) $creditPaymentsOther;

            // Fiados generados (deudas nuevas)
            $fiadosGenerados = \App\Models\CustomerCredit::whereHas('sale', fn($q) => $q->where('cash_register_id', $register->id))
                ->sum('original_amount');

            $expectedCash = (float) $register->opening_amount + $totalCash + (float) $creditPaymentsCash;
            $salesCount = $register->sales->where('status', 'completed')->count();
        @endphp

        <div class="space-y-3 mb-6">
            {{-- Info cajero --}}
            <div class="bg-gray-50 rounded-lg p-3 flex justify-between items-center">
                <div class="flex items-center gap-2">
                    <i class="fas fa-user text-gray-400"></i>
                    <span class="text-sm">{{ $register->user->name }}</span>
                </div>
                <span class="text-xs text-gray-500">Desde {{ $register->opened_at->format('H:i') }}</span>
            </div>

            {{-- Ventas --}}
            <div class="border rounded-lg p-3">
                <p class="text-xs text-gray-500 font-medium mb-2">
                    <i class="fas fa-shopping-cart mr-1"></i>VENTAS ({{ $salesCount }})
                </p>
                <div class="space-y-1">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Total ventas:</span>
                        <span class="font-medium">S/ {{ number_format($totalSales, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Efectivo neto:</span>
                        <span class="font-medium text-green-600">S/ {{ number_format($totalCash, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Otros medios:</span>
                        <span class="font-medium">S/ {{ number_format($totalOther, 2) }}</span>
                    </div>
                </div>
            </div>

            {{-- Fiados --}}
            @if($fiadosGenerados > 0 || $totalCreditPayments > 0)
            <div class="border border-orange-200 rounded-lg p-3 bg-orange-50">
                <p class="text-xs text-orange-700 font-medium mb-2">
                    <i class="fas fa-hand-holding-dollar mr-1"></i>FIADOS
                </p>
                <div class="space-y-1">
                    @if($fiadosGenerados > 0)
                    <div class="flex justify-between text-sm">
                        <span class="text-orange-700">Fiados generados:</span>
                        <span class="font-medium text-red-600">S/ {{ number_format($fiadosGenerados, 2) }}</span>
                    </div>
                    @endif
                    @if($totalCreditPayments > 0)
                    <div class="flex justify-between text-sm">
                        <span class="text-orange-700">Cobros de fiados:</span>
                        <span class="font-medium text-green-600">S/ {{ number_format($totalCreditPayments, 2) }}</span>
                    </div>
                    @if($creditPaymentsCash > 0)
                    <div class="flex justify-between text-xs pl-4">
                        <span class="text-gray-500">En efectivo:</span>
                        <span>S/ {{ number_format($creditPaymentsCash, 2) }}</span>
                    </div>
                    @endif
                    @if($creditPaymentsOther > 0)
                    <div class="flex justify-between text-xs pl-4">
                        <span class="text-gray-500">Otros medios:</span>
                        <span>S/ {{ number_format($creditPaymentsOther, 2) }}</span>
                    </div>
                    @endif
                    @endif
                </div>
            </div>
            @endif

            {{-- Resumen caja --}}
            <div class="border-2 border-blue-200 rounded-lg p-3 bg-blue-50">
                <p class="text-xs text-blue-700 font-medium mb-2">
                    <i class="fas fa-calculator mr-1"></i>RESUMEN DE CAJA
                </p>
                <div class="space-y-1">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Fondo inicial:</span>
                        <span class="font-medium">S/ {{ number_format($register->opening_amount, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">+ Efectivo de ventas:</span>
                        <span class="font-medium text-green-600">S/ {{ number_format($totalCash, 2) }}</span>
                    </div>
                    @if($creditPaymentsCash > 0)
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">+ Cobros fiados (efectivo):</span>
                        <span class="font-medium text-green-600">S/ {{ number_format($creditPaymentsCash, 2) }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between text-sm border-t border-blue-200 pt-2 mt-1">
                        <span class="text-blue-800 font-semibold">Efectivo esperado en caja:</span>
                        <span class="font-bold text-xl text-blue-800">S/ {{ number_format($expectedCash, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('pos.close-register.store') }}">
            @csrf

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-money-bill-wave mr-1 text-green-500"></i>Efectivo contado (S/)
                </label>
                <input type="number" step="0.01" min="0" name="closing_amount"
                       class="w-full border-gray-300 rounded-lg shadow-sm text-2xl text-center focus:ring-blue-500 focus:border-blue-500 h-14"
                       required autofocus>
            </div>

            <button type="submit"
                    class="w-full bg-red-600 text-white py-3 rounded-lg hover:bg-red-700 font-medium flex items-center justify-center gap-2"
                    onclick="return confirm('¿Cerrar caja? No se podrán registrar más ventas.')">
                <i class="fas fa-lock"></i>
                Cerrar Caja
            </button>
        </form>
    </div>
</div>
@endsection