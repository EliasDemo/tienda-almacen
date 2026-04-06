<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Lot;
use App\Models\ProductVariant;
use App\Models\StockRequest;
use App\Services\CashRegisterService;
use App\Services\OrderService;
use App\Services\SaleService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService,
        private CashRegisterService $cashService,
        private SaleService $saleService
    ) {}

    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // Verificar caja abierta para mostrar alerta
        $register = $this->cashService->getOpenRegister($user->id);

        $query = StockRequest::with(['customer', 'user', 'items'])
            ->where('request_type', 'customer_order')
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $orders = $query->paginate(20)->withQueryString();

        return view('pos.orders.index', compact('orders', 'register'));
    }

    public function create(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // Si hay adelanto, necesita caja abierta. Verificamos pero no bloqueamos la vista.
        $register = $this->cashService->getOpenRegister($user->id);
        $customers = Customer::where('is_active', true)->orderBy('name')->get();

        $variants = ProductVariant::with(['product.category', 'prices'])
            ->where('is_active', true)
            ->whereHas('product', fn($q) => $q->where('is_active', true))
            ->get();

        $stockInfo = [];
        foreach ($variants as $v) {
            $lotsData = Lot::where('product_variant_id', $v->id)
                ->where('remaining_quantity', '>', 0)
                ->get();

            $stockInfo[$v->id] = [
                'total_remaining' => round($lotsData->sum('remaining_quantity'), 3),
                'unit'            => $v->product->unit_type,
                'lots_count'      => $lotsData->count(),
            ];
        }

        return view('pos.orders.create', compact('register', 'customers', 'variants', 'stockInfo'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id'                => 'required|exists:customers,id',
            'delivery_date'              => 'required|date|after_or_equal:today',
            'label_color'                => 'nullable|in:rojo,azul,verde,amarillo',
            'customer_notes'             => 'nullable|string',
            'notes'                      => 'nullable|string',
            'items'                      => 'required|array|min:1',
            'items.*.product_variant_id' => 'required|exists:product_variants,id',
            'items.*.quantity'           => 'required|numeric|min:1',
            'items.*.unit'               => 'required|in:kg,unit',
            'items.*.unit_price'         => 'required|numeric|min:0',
            'items.*.package_type'       => 'required|in:saco,caja',
            'advance_amount'             => 'nullable|numeric|min:0',
            'advance_method'             => 'nullable|in:cash,transfer,other',
            'advance_reference'          => 'nullable|string',
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();

        // Si hay adelanto > 0, EXIGIR caja abierta
        $advanceAmount = (float) $request->input('advance_amount', 0);
        $register = null;

        if ($advanceAmount > 0) {
            try {
                $register = $this->cashService->requireOpenRegister($user->id);
            } catch (\Exception $e) {
                return back()->with('error', 'Para registrar un adelanto necesitas tener una caja abierta. ' . $e->getMessage())->withInput();
            }
        } else {
            $register = $this->cashService->getOpenRegister($user->id);
        }

        try {
            $order = $this->orderService->createOrder(
                array_merge($request->all(), ['cash_register_id' => $register?->id]),
                $user->id
            );

            return redirect()->route('pos.orders.show', $order)
                ->with('success', "Pedido {$order->request_code} creado. <a href='" . route('pos.orders.receipt', $order) . "' target='_blank' class='underline font-bold'>Imprimir Boleta</a>");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function show(Request $request, StockRequest $order)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $order->load([
            'customer', 'user', 'confirmedByUser',
            'items.variant.product',
            'payments.user',
            'transfer',
        ]);

        // Verificar si tiene caja abierta (para saber si puede cobrar)
        $register = $this->cashService->getOpenRegister($user->id);

        return view('pos.orders.show', compact('order', 'register'));
    }

    public function receipt(StockRequest $order)
    {
        $order->load(['customer', 'user', 'items.variant.product', 'payments']);
        return view('pos.orders.receipt', compact('order'));
    }

    public function finalReceipt(StockRequest $order)
    {
        $order->load(['customer', 'user', 'items.variant.product', 'payments.user']);
        return view('pos.orders.final-receipt', compact('order'));
    }

    public function checkStock(Request $request)
    {
        $request->validate([
            'product_variant_id' => 'required|exists:product_variants,id',
            'quantity'           => 'required|numeric|min:1',
        ]);

        $variantId = $request->input('product_variant_id');
        $quantity = (int) $request->input('quantity');
        $variant = ProductVariant::with('product')->findOrFail($variantId);
        $unit = $variant->product->unit_type;

        $lots = Lot::where('product_variant_id', $variantId)
            ->where('remaining_quantity', '>', 0)->get();
        $totalRemaining = round($lots->sum('remaining_quantity'), 3);

        $hasEnough = true;
        $warningMessage = null;

        if ($unit === 'unit' && $totalRemaining < $quantity) {
            $hasEnough = false;
            $warningMessage = "Stock insuficiente. Disponible: {$totalRemaining} unidades, pedido: {$quantity}";
        } elseif ($unit === 'kg' && $totalRemaining <= 0) {
            $hasEnough = false;
            $warningMessage = "No hay stock de este producto en almacén.";
        }

        return response()->json([
            'has_enough'      => $hasEnough,
            'total_remaining' => $totalRemaining,
            'unit'            => $unit,
            'lots_count'      => $lots->count(),
            'warning'         => $warningMessage,
        ]);
    }

    /**
     * Registrar pago — REQUIERE CAJA ABIERTA
     */
    public function addPayment(Request $request, StockRequest $order)
    {
        $request->validate([
            'amount'    => 'required|numeric|min:0.01',
            'method'    => 'required|in:cash,transfer,other',
            'reference' => 'nullable|string',
            'notes'     => 'nullable|string',
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();

        // EXIGIR caja abierta para cualquier pago
        try {
            $register = $this->cashService->requireOpenRegister($user->id);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        try {
            $this->orderService->addPayment(
                $order,
                array_merge($request->all(), [
                    'cash_register_id' => $register->id,
                    'payment_type'     => in_array($order->status, ['ready', 'delivered']) ? 'final' : 'advance',
                ]),
                $user->id
            );

            return back()->with('success', 'Pago registrado en caja ' . $register->id . '.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Entregar pedido — REQUIERE CAJA ABIERTA
     */
    public function deliver(Request $request, StockRequest $order)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // EXIGIR caja abierta para entregar
        try {
            $register = $this->cashService->requireOpenRegister($user->id);
        } catch (\Exception $e) {
            return back()->with('error', 'No puedes entregar pedidos sin caja abierta. ' . $e->getMessage());
        }

        try {
            $order->load('items.variant.product');
            $order->refresh();

            if ((float) $order->real_total <= 0) {
                throw new \Exception('No se puede entregar: el pedido no tiene total real calculado.');
            }

            if ((float) $order->remaining_amount > 0.01) {
                throw new \Exception('El cliente tiene saldo pendiente de S/ ' . number_format($order->remaining_amount, 2) . '. Cobra antes de entregar.');
            }

            $this->orderService->updateStatus($order, 'delivered', $user->id);

            return redirect()->route('pos.orders.show', $order)
                ->with('success', "Pedido {$order->request_code} entregado. <a href='" . route('pos.orders.final-receipt', $order) . "' target='_blank' class='underline font-bold'>Imprimir Boleta Final</a>");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function cancel(Request $request, StockRequest $order)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        try {
            $this->orderService->updateStatus($order, 'cancelled', $user->id, $request->input('reason'));
            return back()->with('success', 'Pedido cancelado.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Agregar producto de tienda al pedido (para reponer sacos perdidos/vendidos)
     */
    public function addStoreItem(Request $request, StockRequest $order)
    {
        $request->validate([
            'package_uuid' => 'required|string',
            'quantity'     => 'required|numeric|min:0.001',
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();

        try {
            $register = $this->cashService->requireOpenRegister($user->id);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        try {
            $package = \App\Models\Package::where('uuid', $request->package_uuid)
                ->where('location', 'tienda')
                ->whereIn('status', ['closed', 'opened'])
                ->firstOrFail();

            $variant = $package->lot->variant;
            $quantity = (float) $request->quantity;

            // Buscar el item del pedido que corresponde a este producto
            $orderItem = $order->items()->where('product_variant_id', $variant->id)->first();

            if (!$orderItem) {
                throw new \Exception("Este producto no está en el pedido.");
            }

            // Actualizar quantity_sent sumando lo nuevo
            $newSent = (float) $orderItem->quantity_sent + $quantity;
            $orderItem->update([
                'quantity_sent' => $newSent,
                'real_total'    => round($newSent * (float) $orderItem->sale_price, 2),
            ]);

            // Recalcular totales del pedido
            $order->recalculateRealTotal();

            return back()->with('success', "Se agregaron {$quantity} {$orderItem->unit} de {$variant->product->name} al pedido desde tienda.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}