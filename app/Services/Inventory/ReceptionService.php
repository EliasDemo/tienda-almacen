<?php

namespace App\Services\Inventory;

use App\Models\Package;
use App\Models\Transfer;
use App\Models\TransferLine;
use App\Models\InventoryMovement;
use Illuminate\Support\Facades\DB;

class ReceptionService
{
    /**
     * Recibir un bulto en tienda (escaneado)
     */
    public function receivePackage(Package $package, int $userId): Package
    {
        return DB::transaction(function () use ($package, $userId) {

            if ($package->status !== 'closed') {
                throw new \Exception("Este bulto ya fue procesado (estado: {$package->status}).");
            }

            if ($package->location !== 'transito') {
                throw new \Exception("Este bulto no está en tránsito.");
            }

            $package->update([
                'status'      => 'closed',
                'location'    => 'tienda',
                'received_at' => now(),
            ]);

            $line = $package->transferLine;
            $variant = $line->variant;
            $unit = $variant->product->unit_type;
            $quantity = $package->gross_weight ?? $package->unit_count;

            // Movimiento kardex: TRANSFER_IN
            InventoryMovement::create([
                'product_variant_id' => $variant->id,
                'package_id'         => $package->id,
                'location'           => 'tienda',
                'movement_type'      => 'TRANSFER_IN',
                'quantity'           => $quantity,
                'unit'               => $unit,
                'reference_type'     => Transfer::class,
                'reference_id'       => $line->transfer_id,
                'user_id'            => $userId,
                'note'               => 'Recibido en tienda',
                'occurred_at'        => now(),
            ]);

            // Actualizar contador de recibidos
            $line->increment('received_packages');

            return $package;
        });
    }

    /**
     * Marcar bulto como vendido en tránsito
     */
    public function markSoldInTransit(Package $package, int $userId): Package
    {
        return DB::transaction(function () use ($package, $userId) {

            if ($package->status !== 'closed' || $package->location !== 'transito') {
                throw new \Exception("Este bulto no se puede marcar como vendido en tránsito.");
            }

            $package->update([
                'status'   => 'sold_in_transit',
                'location' => 'transito',
            ]);

            $line = $package->transferLine;
            $variant = $line->variant;
            $unit = $variant->product->unit_type;
            $quantity = $package->gross_weight ?? $package->unit_count;

            // Movimiento kardex: TRANSIT_SALE
            InventoryMovement::create([
                'product_variant_id' => $variant->id,
                'package_id'         => $package->id,
                'location'           => 'almacen',
                'movement_type'      => 'TRANSIT_SALE',
                'quantity'           => -$quantity,
                'unit'               => $unit,
                'reference_type'     => Transfer::class,
                'reference_id'       => $line->transfer_id,
                'user_id'            => $userId,
                'note'               => 'Vendido en tránsito (pendiente validación gerente)',
                'occurred_at'        => now(),
            ]);

            // Actualizar contador
            $line->increment('transit_sold_packages');

            return $package;
        });
    }

    /**
     * Finalizar recepción del cargamento
     */
    public function finishReception(Transfer $transfer, int $userId): void
    {
        $transfer->load('lines.packages');

        $totalPackages = 0;
        $receivedPackages = 0;
        $transitSold = 0;

        foreach ($transfer->lines as $line) {
            foreach ($line->packages as $pkg) {
                $totalPackages++;
                if ($pkg->location === 'tienda') $receivedPackages++;
                if ($pkg->status === 'sold_in_transit') $transitSold++;
            }
        }

        $pending = $totalPackages - $receivedPackages - $transitSold;

        if ($pending > 0) {
            throw new \Exception("Faltan {$pending} bultos por procesar.");
        }

        $status = $transitSold > 0 ? 'partial' : 'received';

        $transfer->update([
            'status'      => $status,
            'received_by' => $userId,
            'received_at' => now(),
        ]);
    }
}