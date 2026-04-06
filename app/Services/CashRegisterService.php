<?php

namespace App\Services;

use App\Models\CashRegister;
use App\Models\CreditPayment;
use App\Models\StockRequestPayment;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;

class CashRegisterService
{
    public function open(int $userId, float $openingAmount): CashRegister
    {
        $existing = CashRegister::where('user_id', $userId)
            ->where('status', 'open')
            ->first();

        if ($existing) {
            throw new \Exception('Ya tienes una caja abierta.');
        }

        return CashRegister::create([
            'user_id'        => $userId,
            'opening_amount' => $openingAmount,
            'status'         => 'open',
            'opened_at'      => now(),
        ]);
    }

    public function close(CashRegister $register, float $closingAmount): CashRegister
    {
        if ($register->status !== 'open') {
            throw new \Exception('Esta caja ya está cerrada.');
        }

        // Ventas
        $sales = Sale::where('cash_register_id', $register->id)
            ->where('status', 'completed')
            ->with('payments')
            ->get();

        $totalSales = 0;
        $totalOther = 0;

        foreach ($sales as $sale) {
            $totalSales += (float) $sale->total;
            foreach ($sale->payments as $payment) {
                if ($payment->method !== 'cash') {
                    $totalOther += (float) $payment->amount;
                }
            }
        }

        $totalCash = $totalSales - $totalOther;

        // Cobros de fiados
        $creditPaymentsCash = CreditPayment::where('cash_register_id', $register->id)
            ->where('method', 'cash')
            ->sum('amount');

        $creditPaymentsOther = CreditPayment::where('cash_register_id', $register->id)
            ->where('method', '!=', 'cash')
            ->sum('amount');

        // Cobros de pedidos (adelantos y pagos finales)
        $orderPaymentsCash = StockRequestPayment::where('cash_register_id', $register->id)
            ->where('method', 'cash')
            ->sum('amount');

        $orderPaymentsOther = StockRequestPayment::where('cash_register_id', $register->id)
            ->where('method', '!=', 'cash')
            ->sum('amount');

        // Efectivo esperado = fondo + ventas efectivo + cobros fiados efectivo + cobros pedidos efectivo
        $expectedCash = (float) $register->opening_amount
            + $totalCash
            + (float) $creditPaymentsCash
            + (float) $orderPaymentsCash;

        $difference = $closingAmount - $expectedCash;

        $register->update([
            'total_sales'    => $totalSales,
            'total_cash'     => $totalCash + (float) $creditPaymentsCash + (float) $orderPaymentsCash,
            'total_other'    => $totalOther + (float) $creditPaymentsOther + (float) $orderPaymentsOther,
            'closing_amount' => $closingAmount,
            'difference'     => $difference,
            'status'         => 'closed',
            'closed_at'      => now(),
        ]);

        return $register->fresh();
    }

    /**
     * Obtener caja abierta (puede ser null)
     */
    public function getOpenRegister(int $userId): ?CashRegister
    {
        return CashRegister::where('user_id', $userId)
            ->where('status', 'open')
            ->first();
    }

    /**
     * EXIGIR caja abierta (lanza excepción si no hay)
     */
    public function requireOpenRegister(int $userId): CashRegister
    {
        $register = $this->getOpenRegister($userId);

        if (!$register) {
            throw new \Exception('No tienes una caja abierta. Abre una caja antes de registrar pagos.');
        }

        return $register;
    }
}