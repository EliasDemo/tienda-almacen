<?php

namespace App\Http\Controllers\Tienda;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Transfer;
use App\Services\Inventory\ReceptionService;
use Illuminate\Http\Request;

class ReceptionController extends Controller
{
    public function __construct(
        private ReceptionService $receptionService
    ) {}

    /**
     * Lista de cargamentos en camino
     */
    public function index()
    {
        $transfers = Transfer::with(['dispatcher', 'lines.variant.product', 'lines.packages'])
            ->whereIn('status', ['in_transit', 'partial'])
            ->latest()
            ->get();

        return view('tienda.reception.index', compact('transfers'));
    }

    /**
     * Pantalla de recepción de un cargamento
     */
    public function show(Transfer $transfer)
    {
        if (!in_array($transfer->status, ['in_transit', 'partial'])) {
            return redirect()->route('tienda.reception.index')
                ->with('error', 'Este cargamento no está disponible para recepción.');
        }

        $transfer->load([
            'dispatcher',
            'lines.variant.product.category',
            'lines.packages.lot',
        ]);

        $initialPackages = $transfer->lines->flatMap(function ($line) {
            return $line->packages->map(function ($pkg) {
                return [
                    'id'       => $pkg->id,
                    'location' => $pkg->location,
                    'status'   => $pkg->status,
                ];
            });
        })->values()->toArray();

        return view('tienda.reception.show', compact('transfer', 'initialPackages'));
    }

    /**
     * AJAX: Buscar bulto por UUID (escaneo)
     */
    public function scanPackage(Request $request, Transfer $transfer)
    {
        $request->validate(['uuid' => 'required|string']);

        $uuid = trim($request->input('uuid'));

        $package = Package::where('uuid', $uuid)
            ->whereHas('transferLine', function ($q) use ($transfer) {
                $q->where('transfer_id', $transfer->id);
            })
            ->with(['lot.variant.product', 'transferLine'])
            ->first();

        if (!$package) {
            return response()->json([
                'success' => false,
                'message' => 'Bulto no encontrado en este cargamento.',
            ], 404);
        }

        $quantity = $package->gross_weight ?? $package->unit_count;
        $unit = $package->lot->variant->product->unit_type;

        return response()->json([
            'success' => true,
            'package' => [
                'id'           => $package->id,
                'uuid'         => $package->uuid,
                'product'      => $package->lot->variant->product->name . ' — ' . $package->lot->variant->name,
                'lot_code'     => $package->lot->lot_code,
                'package_type' => $package->package_type,
                'quantity'     => number_format((float)$quantity, 3, '.', ''),
                'unit'         => $unit,
                'status'       => $package->status,
                'location'     => $package->location,
                'already_processed' => $package->location === 'tienda' || $package->status === 'sold_in_transit',
            ],
        ]);
    }

    /**
     * AJAX: Confirmar recepción de un bulto
     */
    public function receivePackage(Request $request, Transfer $transfer, Package $package)
    {
        try {
            $this->receptionService->receivePackage($package, $request->user()->id);

            return response()->json([
                'success' => true,
                'message' => 'Bulto recibido en tienda.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * AJAX: Marcar bulto como vendido en tránsito
     */
    public function transitSale(Request $request, Transfer $transfer, Package $package)
    {
        try {
            $this->receptionService->markSoldInTransit($package, $request->user()->id);

            return response()->json([
                'success' => true,
                'message' => 'Bulto marcado como vendido en tránsito.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Finalizar recepción
     */
    public function finish(Request $request, Transfer $transfer)
    {
        try {
            $this->receptionService->finishReception($transfer, $request->user()->id);

            return redirect()->route('tienda.reception.index')
                ->with('success', "Recepción del cargamento {$transfer->transfer_code} finalizada.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * AJAX: Escaneo rápido desde el listado (sin entrar al cargamento)
     */
    public function quickScan(Request $request)
    {
        $request->validate(['uuid' => 'required|string']);

        $uuid = trim($request->input('uuid'));

        $package = Package::where('uuid', $uuid)
            ->with(['lot.variant.product', 'transferLine.transfer'])
            ->first();

        if (!$package) {
            return response()->json([
                'success' => false,
                'message' => 'Bulto no encontrado en el sistema.',
            ], 404);
        }

        if ($package->location === 'tienda') {
            return response()->json([
                'success' => false,
                'message' => 'Este bulto ya fue recibido en tienda.',
                'package' => [
                    'uuid'    => $package->uuid,
                    'product' => $package->lot->variant->product->name,
                    'status'  => 'ya_recibido',
                ],
            ], 422);
        }

        if ($package->status === 'sold_in_transit') {
            return response()->json([
                'success' => false,
                'message' => 'Este bulto está marcado como vendido en tránsito.',
                'package' => [
                    'uuid'    => $package->uuid,
                    'product' => $package->lot->variant->product->name,
                    'status'  => 'sold_in_transit',
                ],
            ], 422);
        }

        if ($package->location !== 'transito') {
            return response()->json([
                'success' => false,
                'message' => 'Este bulto no está en tránsito.',
            ], 422);
        }

        try {
            $this->receptionService->receivePackage($package, $request->user()->id);

            $quantity = $package->gross_weight ?? $package->unit_count;
            $unit = $package->lot->variant->product->unit_type;

            return response()->json([
                'success' => true,
                'message' => 'Bulto recibido correctamente.',
                'package' => [
                    'uuid'         => $package->uuid,
                    'product'      => $package->lot->variant->product->name . ' — ' . $package->lot->variant->name,
                    'lot_code'     => $package->lot->lot_code,
                    'package_type' => $package->package_type,
                    'quantity'     => number_format((float)$quantity, 3, '.', ''),
                    'unit'         => $unit,
                    'transfer'     => $package->transferLine->transfer->transfer_code,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}