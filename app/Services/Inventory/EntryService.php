<?php

namespace App\Services\Inventory;

use App\Models\Lot;
use App\Models\InventoryMovement;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

class EntryService
{
    public function registerEntry(array $data, int $userId): Lot
    {
        return DB::transaction(function () use ($data, $userId) {

            $variant = ProductVariant::findOrFail($data['product_variant_id']);
            $unit = $variant->product->unit_type;

            // Crear el lote
            $lot = Lot::create([
                'product_variant_id'      => $variant->id,
                'lot_code'                => $this->generateLotCode($variant),
                'supplier'                => $data['supplier'] ?? null,
                'purchase_price_per_kg'   => $data['purchase_price_per_kg'] ?? null,
                'purchase_price_per_unit' => $data['purchase_price_per_unit'] ?? null,
                'total_quantity'          => $data['total_quantity'],
                'unit'                    => $unit,
                'remaining_quantity'      => $data['total_quantity'],
                'entry_date'              => $data['entry_date'] ?? now()->toDateString(),
                'expiry_date'             => $data['expiry_date'] ?? null,
                'notes'                   => $data['notes'] ?? null,
            ]);

            // Movimiento kardex: IN
            InventoryMovement::create([
                'product_variant_id' => $variant->id,
                'package_id'         => null,
                'location'           => 'almacen',
                'movement_type'      => 'IN',
                'quantity'           => $data['total_quantity'],
                'unit'               => $unit,
                'reference_type'     => Lot::class,
                'reference_id'       => $lot->id,
                'user_id'            => $userId,
                'note'               => 'Entrada de producto al almacén',
                'occurred_at'        => now(),
            ]);

            return $lot;
        });
    }

    private function generateLotCode(ProductVariant $variant): string
    {
        $date = now()->format('Ymd');
        $prefix = 'LOT-' . $variant->sku_code . '-' . $date;

        $count = Lot::where('lot_code', 'like', $prefix . '%')->count();
        $sequence = str_pad($count + 1, 3, '0', STR_PAD_LEFT);

        return $prefix . '-' . $sequence;
    }
}