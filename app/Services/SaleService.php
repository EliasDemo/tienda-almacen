<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Package;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\InventoryMovement;
use App\Models\CustomerCredit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SaleService
{
    public function openPackage(Package $package): Package
    {
        if ($package->status !== 'closed') {
            throw new \Exception('Este bulto ya fue abierto o vendido.');
        }

        if ($package->location !== 'tienda') {
            throw new \Exception('Este bulto no está en la tienda.');
        }

        $mermaKg = (float) $package->transferLine->merma_kg;

        if ($package->gross_weight) {
            $netWeight = (float) $package->gross_weight - $mermaKg;
            if ($netWeight < 0) $netWeight = 0;

            $package->update([
                'status'     => 'opened',
                'net_weight' => $netWeight,
                'opened_at'  => now(),
            ]);

            if ($mermaKg > 0) {
                InventoryMovement::create([
                    'product_variant_id' => $package->lot->product_variant_id,
                    'package_id'         => $package->id,
                    'location'           => 'tienda',
                    'movement_type'      => 'OPENING_MERMA',
                    'quantity'           => -$mermaKg,
                    'unit'               => 'kg',
                    'user_id'            => Auth::id(),
                    'note'               => 'Merma al abrir bulto',
                    'occurred_at'        => now(),
                ]);
            }
        } else {
            $package->update([
                'status'    => 'opened',
                'net_units' => $package->unit_count,
                'opened_at' => now(),
            ]);
        }

        return $package->fresh();
    }

    public function createSale(array $data, int $userId): Sale
    {
        return DB::transaction(function () use ($data, $userId) {

            $saleNumber = $this->generateSaleNumber();

            $sale = Sale::create([
                'sale_number'      => $saleNumber,
                'cash_register_id' => $data['cash_register_id'],
                'customer_id'      => $data['customer_id'] ?? null,
                'user_id'          => $userId,
                'sale_type'        => 'caja',
                'price_type'       => $data['price_type'] ?? 'minorista',
                'subtotal'         => 0,
                'discount'         => $data['discount'] ?? 0,
                'total'            => 0,
                'status'           => 'completed',
            ]);

            $subtotal = 0;

            foreach ($data['items'] as $itemData) {
                $packageId = $itemData['package_id'] ?? null;
                $sellMode = $itemData['sell_mode'];
                $quantity = (float) $itemData['quantity'];
                $variantId = $itemData['product_variant_id'];
                $unit = $itemData['unit'];

                if ($sellMode === 'fraction' && !$packageId) {
                    $packageId = $this->deductFromOpenedPackages($variantId, $quantity, $unit, $sale, $userId);
                } else {
                    $this->deductFromPackage($packageId, $sellMode, $quantity, $unit, $variantId, $sale, $userId);
                }

                $itemSubtotal = round($quantity * (float) $itemData['unit_price'], 2);
                $subtotal += $itemSubtotal;

                SaleItem::create([
                    'sale_id'            => $sale->id,
                    'product_variant_id' => $variantId,
                    'package_id'         => $packageId,
                    'sell_mode'          => $sellMode,
                    'quantity'           => $quantity,
                    'unit'               => $unit,
                    'unit_price'         => $itemData['unit_price'],
                    'subtotal'           => $itemSubtotal,
                ]);
            }

            $discount = (float) ($data['discount'] ?? 0);
            $total = $subtotal - $discount;

            $sale->update([
                'subtotal' => $subtotal,
                'total'    => $total,
            ]);

            // Crear pagos
            foreach ($data['payments'] as $paymentData) {
                if ((float) $paymentData['amount'] > 0) {
                    SalePayment::create([
                        'sale_id'   => $sale->id,
                        'method'    => $paymentData['method'],
                        'amount'    => $paymentData['amount'],
                        'reference' => $paymentData['reference'] ?? null,
                    ]);
                }
            }

            // Verificar fiado
            $totalPaid = collect($data['payments'])->sum(fn($p) => (float) $p['amount']);
            $creditAmount = $total - $totalPaid;

            if ($creditAmount > 0.01) {
                if (empty($data['customer_id'])) {
                    throw new \Exception('Para fiar es obligatorio seleccionar un cliente.');
                }

                $customer = Customer::findOrFail($data['customer_id']);

                if (!$customer->canReceiveCredit()) {
                    throw new \Exception('Este cliente no puede fiar: ' . $customer->credit_block_reason);
                }

                CustomerCredit::create([
                    'customer_id'     => $data['customer_id'],
                    'sale_id'         => $sale->id,
                    'user_id'         => $userId,
                    'original_amount' => $creditAmount,
                    'paid_amount'     => 0,
                    'balance'         => $creditAmount,
                    'status'          => 'pending',
                    'notes'           => 'Fiado generado desde venta ' . $saleNumber,
                ]);
            }

            return $sale->fresh(['items.variant.product', 'payments', 'customer']);
        });
    }

    private function deductFromOpenedPackages(int $variantId, float $quantity, string $unit, Sale $sale, int $userId): ?int
    {
        $remaining = $quantity;
        $firstPackageId = null;

        $openedPackages = Package::where('location', 'tienda')
            ->where('status', 'opened')
            ->whereHas('lot', fn($q) => $q->where('product_variant_id', $variantId))
            ->orderBy('opened_at', 'asc')
            ->get();

        if ($openedPackages->isEmpty()) {
            throw new \Exception('No hay stock suelto disponible para este producto.');
        }

        foreach ($openedPackages as $package) {
            if ($remaining <= 0) break;

            $available = $package->available_quantity;
            if ($available <= 0) continue;

            $toDeduct = min($remaining, $available);

            if (!$firstPackageId) {
                $firstPackageId = $package->id;
            }

            InventoryMovement::create([
                'product_variant_id' => $variantId,
                'package_id'         => $package->id,
                'location'           => 'tienda',
                'movement_type'      => 'SALE',
                'quantity'           => -$toDeduct,
                'unit'               => $unit,
                'reference_type'     => Sale::class,
                'reference_id'       => $sale->id,
                'user_id'            => $userId,
                'note'               => "Venta {$sale->sale_number}",
                'occurred_at'        => now(),
            ]);

            $remaining -= $toDeduct;

            $newAvailable = $package->fresh()->available_quantity;
            if ($newAvailable <= 0.001) {
                $package->update(['status' => 'exhausted']);
            }
        }

        if ($remaining > 0.001) {
            throw new \Exception("Stock insuficiente. Faltan " . number_format($remaining, 3) . " {$unit}.");
        }

        return $firstPackageId;
    }

    private function deductFromPackage(?int $packageId, string $sellMode, float $quantity, string $unit, int $variantId, Sale $sale, int $userId): void
    {
        if (!$packageId) return;

        $package = Package::findOrFail($packageId);

        InventoryMovement::create([
            'product_variant_id' => $variantId,
            'package_id'         => $packageId,
            'location'           => 'tienda',
            'movement_type'      => 'SALE',
            'quantity'           => -$quantity,
            'unit'               => $unit,
            'reference_type'     => Sale::class,
            'reference_id'       => $sale->id,
            'user_id'            => $userId,
            'note'               => "Venta {$sale->sale_number}",
            'occurred_at'        => now(),
        ]);

        if ($sellMode === 'bulk') {
            $package->update(['status' => 'sold']);
        } else {
            $newAvailable = $package->fresh()->available_quantity;
            if ($newAvailable <= 0.001) {
                $package->update(['status' => 'exhausted']);
            }
        }
    }

    private function generateSaleNumber(): string
    {
        $date = now()->format('Ymd');
        $prefix = 'V-' . $date;
        $count = Sale::where('sale_number', 'like', $prefix . '%')->count();
        return $prefix . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    }
}