<?php

namespace App\Http\Controllers\Almacen;

use App\Http\Controllers\Controller;
use App\Models\Lot;
use App\Models\Package;
use App\Models\Transfer;
use App\Models\TransferLine;
use App\Models\ProductVariant;
use App\Services\Inventory\DispatchService;
use Illuminate\Http\Request;

class TransferController extends Controller
{
    public function __construct(
        private DispatchService $dispatchService
    ) {}

    public function index()
    {
        $transfers = Transfer::with(['dispatcher', 'lines.variant.product', 'stockRequestOrder.customer'])
            ->whereDate('created_at', today())
            ->latest()
            ->get();

        return view('almacen.transfers.index', compact('transfers'));
    }

    public function store(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $transfer = $this->dispatchService->createTransfer(
            $user->id,
            $request->input('notes')
        );

        return redirect()
            ->route('almacen.transfers.show', $transfer)
            ->with('success', "Cargamento {$transfer->transfer_code} creado.");
    }

    public function show(Transfer $transfer)
    {
        $transfer->load([
            'dispatcher',
            'receiver',
            'stockRequestOrder.customer',
            'stockRequestOrder.items.variant.product',
            'lines.variant.product.category',
            'lines.packages.lot',
        ]);

        $variants = ProductVariant::with('product.category')
            ->where('is_active', true)
            ->get()
            ->map(fn($v) => [
                'id'        => $v->id,
                'label'     => $v->product->category->name . ' > ' . $v->product->name . ' > ' . $v->name,
                'unit_type' => $v->product->unit_type,
            ]);

        $lots = Lot::where('remaining_quantity', '>', 0)
            ->with('variant.product')
            ->get()
            ->map(fn($lot) => [
                'id'                 => $lot->id,
                'lot_code'           => $lot->lot_code,
                'product_variant_id' => $lot->product_variant_id,
                'remaining'          => (float) $lot->remaining_quantity,
                'unit'               => $lot->unit,
                'label'              => $lot->lot_code . ' (' . number_format($lot->remaining_quantity, 3) . ' ' . $lot->unit . ')',
            ]);

        $existingLines = $transfer->lines->map(function ($line) {
            return [
                'lineId'             => $line->id,
                'product_variant_id' => $line->product_variant_id,
                'productName'        => $line->variant->product->name . ' — ' . $line->variant->name,
                'merma'              => (float) $line->merma_kg,
                'unit'               => $line->variant->product->unit_type,
                'package_type'       => $line->packages->first()?->package_type ?? 'saco',
                'lot_id'             => $line->packages->last()?->lot_id ?? null,
                'packages'           => $line->packages->map(function ($pkg) {
                    return [
                        'id'           => $pkg->id,
                        'uuid'         => $pkg->uuid,
                        'lot_code'     => $pkg->lot->lot_code,
                        'lot_id'       => $pkg->lot_id,
                        'package_type' => $pkg->package_type,
                        'quantity'     => $pkg->gross_weight
                            ? number_format((float) $pkg->gross_weight, 3, '.', '')
                            : (string) $pkg->unit_count,
                        'label_url'    => route('almacen.packages.label', $pkg->id),
                        'forOrder'     => (bool) $pkg->for_order,
                    ];
                })->toArray(),
            ];
        })->toArray();

        $orderLimits = [];
        if ($transfer->stock_request_id && $transfer->stockRequestOrder) {
            foreach ($transfer->stockRequestOrder->items as $item) {
                $orderLimits[$item->product_variant_id] = [
                    'max_packages' => (int) $item->quantity_requested,
                    'product_name' => $item->variant->product->name . ' — ' . $item->variant->name,
                    'unit'         => $item->unit,
                    'sale_price'   => (float) $item->sale_price,
                ];
            }
        }

        return view('almacen.transfers.show', compact('transfer', 'variants', 'lots', 'existingLines', 'orderLimits'));
    }

    public function addPackage(Request $request, Transfer $transfer)
    {
        $request->validate([
            'lot_id'       => 'required|exists:lots,id',
            'package_type' => 'required|in:saco,caja',
            'quantity'     => 'required|numeric|min:0.001',
            'merma_kg'     => 'nullable|numeric|min:0',
            'for_order'    => 'nullable',
        ]);

        $forOrder = filter_var($request->input('for_order', false), FILTER_VALIDATE_BOOLEAN);

        // Validar límite solo si es para pedido
        if ($forOrder && $transfer->stock_request_id) {
            $transfer->load('stockRequestOrder.items');
            $lot = Lot::findOrFail($request->lot_id);
            $variantId = $lot->product_variant_id;

            if ($transfer->stockRequestOrder) {
                $orderItem = $transfer->stockRequestOrder->items
                    ->firstWhere('product_variant_id', $variantId);

                if ($orderItem) {
                    $currentCount = $transfer->packages()
                        ->where('for_order', true)
                        ->whereHas('transferLine', fn($q) => $q->where('product_variant_id', $variantId))
                        ->count();

                    if ($currentCount >= (int) $orderItem->quantity_requested) {
                        return response()->json([
                            'success' => false,
                            'message' => "Límite del pedido alcanzado: {$orderItem->quantity_requested} bulto(s).",
                        ], 422);
                    }
                }
            }
        }

        /** @var \App\Models\User $user */
        $user = $request->user();

        try {
            $package = $this->dispatchService->addPackage(
                $transfer,
                array_merge($request->all(), ['for_order' => $forOrder]),
                $user->id
            );

            $package->load('lot');

            return response()->json([
                'success' => true,
                'package' => [
                    'id'           => $package->id,
                    'uuid'         => $package->uuid,
                    'package_type' => $package->package_type,
                    'gross_weight' => $package->gross_weight,
                    'unit_count'   => $package->unit_count,
                    'lot_code'     => $package->lot->lot_code,
                    'label_url'    => route('almacen.packages.label', $package),
                    'for_order'    => (bool) $package->for_order,
                ],
                'message' => 'Bulto agregado.' . ($forOrder ? ' [PEDIDO]' : ''),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function removePackage(Request $request, Transfer $transfer, Package $package)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        try {
            $this->dispatchService->removePackage($package, $user->id);
            return response()->json(['success' => true, 'message' => 'Bulto eliminado.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function updateMerma(Request $request, TransferLine $line)
    {
        $request->validate(['merma_kg' => 'required|numeric|min:0']);
        $this->dispatchService->updateMerma($line, $request->input('merma_kg'));
        return response()->json(['success' => true, 'message' => 'Merma actualizada.']);
    }

    public function dispatch(Transfer $transfer)
    {
        if ($transfer->status !== 'preparing') {
            return back()->with('error', 'Este cargamento ya fue despachado.');
        }

        if ($transfer->lines->count() === 0) {
            return back()->with('error', 'No puedes despachar un cargamento vacío.');
        }

        $this->dispatchService->markAsDispatched($transfer);

        return back()->with('success', "Cargamento {$transfer->transfer_code} despachado.");
    }

    public function destroy(Transfer $transfer)
    {
        if ($transfer->status !== 'preparing') {
            return back()->with('error', 'Solo se pueden eliminar cargamentos en preparación.');
        }
        if ($transfer->lines()->exists()) {
            return back()->with('error', 'Quita los bultos primero.');
        }

        $code = $transfer->transfer_code;
        $transfer->delete();

        return redirect()->route('almacen.transfers.index')
            ->with('success', "Cargamento {$code} eliminado.");
    }
}