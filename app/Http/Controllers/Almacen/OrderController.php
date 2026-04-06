<?php

namespace App\Http\Controllers\Almacen;

use App\Http\Controllers\Controller;
use App\Models\StockRequest;
use App\Models\Transfer;
use App\Services\OrderService;
use App\Services\Inventory\DispatchService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService,
        private DispatchService $dispatchService
    ) {}

    public function index()
    {
        $orders = StockRequest::with(['customer', 'user', 'items.variant.product', 'transfer'])
            ->where('request_type', 'customer_order')
            ->whereNotIn('status', ['delivered', 'cancelled'])
            ->orderByRaw("FIELD(status, 'ready', 'preparing', 'dispatched', 'received', 'confirmed', 'pending')")
            ->orderBy('delivery_date')
            ->get();

        // Contar alertas
        $alertCount = $orders->filter(fn($o) => count($o->alerts) > 0)->count();

        return view('almacen.orders.index', compact('orders', 'alertCount'));
    }

    public function archive(Request $request)
    {
        $query = StockRequest::with(['customer', 'user', 'items.variant.product'])
            ->where('request_type', 'customer_order')
            ->whereIn('status', ['delivered', 'cancelled'])
            ->orderBy('updated_at', 'desc');

        $orders = $query->paginate(20);

        return view('almacen.orders.archive', compact('orders'));
    }

    public function show(StockRequest $order)
    {
        $order->load([
            'customer', 'user', 'confirmedByUser',
            'items.variant.product',
            'payments.user',
            'transfer.lines.packages',
        ]);

        // Cargamentos disponibles para vincular (en preparación, sin pedido asignado)
        $availableTransfers = [];
        if (in_array($order->status, ['confirmed', 'preparing']) && !$order->transfer_id) {
            $availableTransfers = Transfer::where('status', 'preparing')
                ->whereNull('stock_request_id')
                ->latest()
                ->get();
        }

        return view('almacen.orders.show', compact('order', 'availableTransfers'));
    }

    public function confirm(Request $request, StockRequest $order)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        try {
            $this->orderService->updateStatus($order, 'confirmed', $user->id);
            return back()->with('success', "Pedido {$order->request_code} confirmado.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function startPreparing(Request $request, StockRequest $order)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        try {
            $this->orderService->updateStatus($order, 'preparing', $user->id);

            $transfer = $this->dispatchService->createTransfer(
                $user->id,
                "Pedido {$order->request_code} — Cliente: {$order->customer->name}"
            );

            $transfer->update(['stock_request_id' => $order->id]);
            $order->update(['transfer_id' => $transfer->id]);

            return redirect()->route('almacen.transfers.show', $transfer)
                ->with('success', "Cargamento creado para pedido {$order->request_code}.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function linkToTransfer(Request $request, StockRequest $order)
    {
        $request->validate(['transfer_id' => 'required|exists:transfers,id']);

        /** @var \App\Models\User $user */
        $user = $request->user();

        try {
            $transfer = Transfer::findOrFail($request->transfer_id);

            if ($transfer->status !== 'preparing') {
                throw new \Exception('Solo se puede vincular a cargamentos en preparación.');
            }

            if ($transfer->stock_request_id && $transfer->stock_request_id !== $order->id) {
                throw new \Exception('Este cargamento ya está vinculado a otro pedido.');
            }

            if ($order->status === 'confirmed') {
                $this->orderService->updateStatus($order, 'preparing', $user->id);
            }

            $transfer->update(['stock_request_id' => $order->id]);
            $order->update(['transfer_id' => $transfer->id]);

            return redirect()->route('almacen.transfers.show', $transfer)
                ->with('success', "Pedido vinculado al cargamento {$transfer->transfer_code}.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function markReady(Request $request, StockRequest $order)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        try {
            // Verificar que haya bultos for_order
            if ($order->order_packages_total === 0) {
                throw new \Exception('No hay bultos marcados como pedido en el cargamento. Verifica que hayas marcado los sacos como "para pedido".');
            }

            $this->orderService->updateStatus($order, 'ready', $user->id);

            $order->load('items');
            $order->recalculateRealTotal();

            return back()->with('success', "Pedido listo. Total real: S/ " . number_format($order->real_total, 2));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}