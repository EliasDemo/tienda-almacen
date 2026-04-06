<?php

namespace App\Services;

use App\Models\StockRequest;
use App\Models\StockRequestItem;
use App\Models\StockRequestPayment;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function createOrder(array $data, int $userId): StockRequest
    {
        return DB::transaction(function () use ($data, $userId) {

            $orderCode = $this->generateOrderCode();

            $order = StockRequest::create([
                'request_code'     => $orderCode,
                'requested_by'     => $userId,
                'cash_register_id' => $data['cash_register_id'] ?? null,
                'request_type'     => 'customer_order',
                'customer_id'      => $data['customer_id'],
                'status'           => 'pending',
                'delivery_date'    => $data['delivery_date'],
                'estimated_total'  => 0,
                'real_total'       => 0,
                'remaining_amount' => 0,
                'advance_amount'   => 0,
                'label_color'      => $data['label_color'] ?? 'rojo',
                'notes'            => $data['notes'] ?? null,
                'customer_notes'   => $data['customer_notes'] ?? null,
                'requested_at'     => now(),
            ]);

            $estimatedTotal = 0;

            foreach ($data['items'] as $item) {
                $unitEstimate = 0;
                if (($item['unit'] ?? 'kg') === 'unit') {
                    $unitEstimate = round((float) $item['quantity'] * (float) $item['unit_price'], 2);
                }
                $estimatedTotal += $unitEstimate;

                StockRequestItem::create([
                    'stock_request_id'   => $order->id,
                    'product_variant_id' => $item['product_variant_id'],
                    'quantity_requested' => $item['quantity'],
                    'unit'               => $item['unit'] ?? 'kg',
                    'package_type'       => $item['package_type'] ?? 'saco',
                    'sale_price'         => $item['unit_price'],
                    'notes'              => $item['notes'] ?? null,
                ]);
            }

            $order->update([
                'estimated_total'  => $estimatedTotal,
                'remaining_amount' => 0,
            ]);

            if (!empty($data['advance_amount']) && (float) $data['advance_amount'] > 0) {
                $advanceAmount = (float) $data['advance_amount'];

                StockRequestPayment::create([
                    'stock_request_id' => $order->id,
                    'cash_register_id' => $data['cash_register_id'] ?? null,
                    'user_id'          => $userId,
                    'amount'           => $advanceAmount,
                    'method'           => $data['advance_method'] ?? 'cash',
                    'payment_type'     => 'advance',
                    'reference'        => $data['advance_reference'] ?? null,
                    'notes'            => 'Adelanto al crear pedido',
                ]);

                $order->update([
                    'advance_amount'    => $advanceAmount,
                    'advance_method'    => $data['advance_method'] ?? 'cash',
                    'advance_reference' => $data['advance_reference'] ?? null,
                ]);
            }

            return $order->fresh(['items.variant.product', 'payments', 'customer']);
        });
    }

    public function addPayment(StockRequest $order, array $data, int $userId): StockRequestPayment
    {
        return DB::transaction(function () use ($order, $data, $userId) {

            if ($order->status === 'cancelled') {
                throw new \Exception('No se puede agregar pagos a un pedido cancelado.');
            }

            $payment = StockRequestPayment::create([
                'stock_request_id' => $order->id,
                'cash_register_id' => $data['cash_register_id'] ?? null,
                'user_id'          => $userId,
                'amount'           => (float) $data['amount'],
                'method'           => $data['method'] ?? 'cash',
                'payment_type'     => $data['payment_type'] ?? 'advance',
                'reference'        => $data['reference'] ?? null,
                'notes'            => $data['notes'] ?? null,
            ]);

            $order->recalculateTotals();

            return $payment;
        });
    }

    public function updateStatus(StockRequest $order, string $status, int $userId, ?string $reason = null): void
    {
        $validTransitions = [
            'pending'    => ['confirmed', 'cancelled'],
            'confirmed'  => ['preparing', 'cancelled'],
            'preparing'  => ['dispatched', 'ready', 'cancelled'],
            'dispatched' => ['received', 'ready', 'cancelled'],
            'received'   => ['ready', 'cancelled'],
            'ready'      => ['delivered', 'cancelled'],
        ];

        $allowed = $validTransitions[$order->status] ?? [];

        if (!in_array($status, $allowed)) {
            throw new \Exception("No se puede cambiar de '{$order->status_label}' a '{$status}'.");
        }

        $update = ['status' => $status];

        match ($status) {
            'confirmed'  => $update += ['confirmed_at' => now(), 'confirmed_by' => $userId],
            'preparing'  => $update += ['preparing_at' => now()],
            'dispatched' => $update += ['dispatched_at' => now()],
            'received'   => $update += ['received_at' => now()],
            'ready'      => $update += ['ready_at' => now()],
            'delivered'  => $update += ['delivered_at' => now()],
            'cancelled'  => $update += ['cancelled_at' => now(), 'cancel_reason' => $reason],
            default      => null,
        };

        $order->update($update);
    }

    private function generateOrderCode(): string
    {
        $date = now()->format('Ymd');
        $prefix = 'PED-' . $date;
        $count = StockRequest::where('request_code', 'like', $prefix . '%')->count();
        return $prefix . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    }
}