<?php

namespace App\Services\Inventory;

use App\Models\InventoryMovement;
use App\Models\Lot;
use App\Models\Package;
use App\Models\Transfer;
use App\Models\TransferLine;
use Illuminate\Support\Facades\DB;

class DispatchService
{
    public function createTransfer(int $userId, ?string $notes = null): Transfer
    {
        return Transfer::create([
            'transfer_code' => $this->generateTransferCode(),
            'dispatched_by' => $userId,
            'status'        => 'preparing',
            'notes'         => $notes,
        ]);
    }

    public function addPackage(Transfer $transfer, array $data, int $userId): Package
    {
        return DB::transaction(function () use ($transfer, $data, $userId) {

            $lot = Lot::findOrFail($data['lot_id']);
            $variantId = $lot->product_variant_id;
            $unit = $lot->unit;
            $quantity = $data['quantity'];
            $forOrder = filter_var($data['for_order'] ?? false, FILTER_VALIDATE_BOOLEAN);

            // DEBUG - quitar después de verificar
            \Illuminate\Support\Facades\Log::info('FOR_ORDER', [
                'raw'    => $data['for_order'] ?? 'NULO',
                'parsed' => $forOrder,
                'type'   => gettype($data['for_order'] ?? null),
                'all_keys' => array_keys($data),
            ]);

            if ($lot->remaining_quantity < $quantity) {
                throw new \Exception("Stock insuficiente en lote {$lot->lot_code}. Disponible: {$lot->remaining_quantity} {$unit}");
            }

            $line = TransferLine::firstOrCreate(
                [
                    'transfer_id'       => $transfer->id,
                    'product_variant_id' => $variantId,
                ],
                [
                    'merma_kg'       => $data['merma_kg'] ?? 0,
                    'total_packages' => 0,
                ]
            );

            $package = Package::create([
                'lot_id'           => $lot->id,
                'transfer_line_id' => $line->id,
                'package_type'     => $data['package_type'] ?? 'saco',
                'gross_weight'     => $unit === 'kg' ? $quantity : null,
                'unit_count'       => $unit === 'unit' ? (int) $quantity : null,
                'status'           => 'closed',
                'location'         => 'transito',
                'for_order'        => $forOrder,
            ]);

            // DEBUG - verificar qué se guardó
            \Illuminate\Support\Facades\Log::info('PACKAGE_CREATED', [
                'id'        => $package->id,
                'for_order' => $package->for_order,
                'raw_db'    => DB::table('packages')->where('id', $package->id)->value('for_order'),
            ]);

            $line->increment('total_packages');
            $lot->decrement('remaining_quantity', $quantity);

            InventoryMovement::create([
                'product_variant_id' => $variantId,
                'package_id'         => $package->id,
                'location'           => 'almacen',
                'movement_type'      => 'TRANSFER_OUT',
                'quantity'           => -$quantity,
                'unit'               => $unit,
                'reference_type'     => Transfer::class,
                'reference_id'       => $transfer->id,
                'user_id'            => $userId,
                'note'               => "Despacho {$transfer->transfer_code}" . ($forOrder ? ' [PEDIDO]' : ''),
                'occurred_at'        => now(),
            ]);

            return $package;
        });
    }

    public function removePackage(Package $package, int $userId): void
    {
        DB::transaction(function () use ($package, $userId) {

            $line = $package->transferLine;
            $lot = $package->lot;
            $quantity = $package->gross_weight ?? $package->unit_count;

            $lot->increment('remaining_quantity', $quantity);

            InventoryMovement::where('package_id', $package->id)
                ->where('movement_type', 'TRANSFER_OUT')
                ->delete();

            $package->delete();
            $line->decrement('total_packages');

            if ($line->total_packages <= 0) {
                $line->delete();
            }
        });
    }

    public function updateMerma(TransferLine $line, float $mermaKg): void
    {
        $line->update(['merma_kg' => $mermaKg]);
    }

    public function markAsDispatched(Transfer $transfer): void
    {
        DB::transaction(function () use ($transfer) {

            $transfer->update([
                'status'        => 'in_transit',
                'dispatched_at' => now(),
            ]);

            if ($transfer->stock_request_id) {
                $this->updateOrderFromTransfer($transfer);
            }
        });
    }

    private function updateOrderFromTransfer(Transfer $transfer): void
    {
        $transfer->load(['stockRequestOrder.items.variant.product', 'lines.packages']);

        $order = $transfer->stockRequestOrder;
        if (!$order) return;

        $realTotal = 0;

        foreach ($order->items as $item) {
            $line = $transfer->lines->firstWhere('product_variant_id', $item->product_variant_id);

            if ($line) {
                $orderPackages = $line->packages->where('for_order', true);

                if ($item->unit === 'kg') {
                    $totalWeight = $orderPackages->sum('gross_weight');
                    $item->update([
                        'quantity_sent' => $totalWeight,
                        'real_total'    => round((float) $totalWeight * (float) $item->sale_price, 2),
                    ]);
                } else {
                    $totalUnits = $orderPackages->sum('unit_count');
                    $item->update([
                        'quantity_sent' => $totalUnits,
                        'real_total'    => round((float) $totalUnits * (float) $item->sale_price, 2),
                    ]);
                }

                $realTotal += (float) $item->real_total;
            }
        }

        $totalPaid = $order->payments()
            ->whereIn('payment_type', ['advance', 'final'])
            ->sum('amount');

        $order->update([
            'real_total'       => $realTotal,
            'remaining_amount' => max(0, $realTotal - (float) $totalPaid),
            'dispatched_at'    => now(),
            'status'           => 'ready',
            'ready_at'         => now(),
        ]);
    }

    private function generateTransferCode(): string
    {
        $date = now()->format('Ymd');
        $prefix = 'CARG-' . $date;
        $count = Transfer::where('transfer_code', 'like', $prefix . '%')->count();
        return $prefix . '-' . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
    }
}
