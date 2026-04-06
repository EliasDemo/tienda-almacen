<?php

namespace App\Services;

use App\Models\CustomerCredit;
use App\Models\CreditPayment;
use Illuminate\Support\Facades\DB;

class CreditService
{
    /**
     * Registrar pago de fiado
     */
    public function registerPayment(CustomerCredit $credit, array $data, int $userId): CreditPayment
    {
        return DB::transaction(function () use ($credit, $data, $userId) {

            $amount = (float) $data['amount'];

            if ($amount > (float) $credit->balance) {
                throw new \Exception('El monto excede la deuda pendiente (S/ ' . number_format($credit->balance, 2) . ').');
            }

            $payment = CreditPayment::create([
                'customer_credit_id' => $credit->id,
                'cash_register_id'   => $data['cash_register_id'] ?? null,
                'user_id'            => $userId,
                'amount'             => $amount,
                'method'             => $data['method'] ?? 'cash',
                'reference'          => $data['reference'] ?? null,
                'notes'              => $data['notes'] ?? null,
            ]);

            $credit->recalculateBalance();

            return $payment;
        });
    }
}