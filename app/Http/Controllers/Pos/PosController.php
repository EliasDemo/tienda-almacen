<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Customer;
use App\Models\ProductVariant;
use App\Models\Category;
use App\Services\CashRegisterService;
use App\Services\SaleService;
use Illuminate\Http\Request;

class PosController extends Controller
{
    public function __construct(
        private CashRegisterService $cashService,
        private SaleService $saleService
    ) {}

    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $register = $this->cashService->getOpenRegister($user->id);
        if (!$register) return redirect()->route('pos.open-register');

        $customers = Customer::where('is_active', true)->orderBy('name')->get();
        $categories = Category::where('is_active', true)->orderBy('name')->get();

        return view('pos.index', compact('register', 'customers', 'categories'));
    }

    public function getProducts(Request $request)
    {
        $categoryId = $request->input('category_id');

        $query = ProductVariant::with(['product.category', 'prices'])
            ->where('is_active', true)
            ->whereHas('product', fn($q) => $q->where('is_active', true));

        if ($categoryId) {
            $query->whereHas('product', fn($q) => $q->where('category_id', $categoryId));
        }

        $variants = $query->get();

        $products = $variants->map(function ($variant) {
            $closedPackages = Package::where('location', 'tienda')
                ->where('status', 'closed')
                ->whereHas('lot', fn($q) => $q->where('product_variant_id', $variant->id))
                ->count();

            $openedPackages = Package::where('location', 'tienda')
                ->where('status', 'opened')
                ->whereHas('lot', fn($q) => $q->where('product_variant_id', $variant->id))
                ->get();

            $looseStock = 0;
            foreach ($openedPackages as $pkg) {
                $looseStock += $pkg->available_quantity;
            }

            $prices = [];
            foreach ($variant->prices as $price) {
                if ($price->is_active) {
                    $prices[$price->price_type] = (float) $price->price;
                }
            }

            $image = $variant->images()->first() ?? $variant->product->images()->first();

            return [
                'id'              => $variant->id,
                'name'            => $variant->product->name,
                'variant_name'    => $variant->name,
                'category'        => $variant->product->category->name,
                'unit'            => $variant->product->unit_type,
                'closed_packages' => $closedPackages,
                'loose_stock'     => round($looseStock, 3),
                'prices'          => $prices,
                'image'           => $image ? asset('storage/' . $image->path) : null,
                'has_stock'       => $closedPackages > 0 || $looseStock > 0,
            ];
        })->filter(fn($p) => $p['has_stock'])->values();

        return response()->json($products);
    }

    public function showOpenRegister(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $register = $this->cashService->getOpenRegister($user->id);
        if ($register) return redirect()->route('pos.index');

        return view('pos.open-register');
    }

    public function openRegister(Request $request)
    {
        $request->validate(['opening_amount' => 'required|numeric|min:0']);

        /** @var \App\Models\User $user */
        $user = $request->user();

        try {
            $this->cashService->open($user->id, $request->input('opening_amount'));
            return redirect()->route('pos.index')->with('success', 'Caja abierta correctamente.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function showCloseRegister(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $register = $this->cashService->getOpenRegister($user->id);
        if (!$register) return redirect()->route('pos.open-register');

        $register->load(['sales.payments', 'user']);

        return view('pos.close-register', compact('register'));
    }

    public function closeRegister(Request $request)
    {
        $request->validate(['closing_amount' => 'required|numeric|min:0']);

        /** @var \App\Models\User $user */
        $user = $request->user();

        $register = $this->cashService->getOpenRegister($user->id);
        if (!$register) return redirect()->route('pos.open-register')->with('error', 'No hay caja abierta.');

        try {
            $register = $this->cashService->close($register, $request->input('closing_amount'));
            return redirect()->route('pos.register-report', $register)->with('success', 'Caja cerrada.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function registerReport(Request $request, int $registerId)
    {
        $register = \App\Models\CashRegister::with(['sales.items.variant.product', 'sales.payments', 'user'])
            ->findOrFail($registerId);

        return view('pos.register-report', compact('register'));
    }

    public function scanPackage(Request $request)
    {
        $request->validate(['uuid' => 'required|string']);

        $package = Package::where('uuid', trim($request->input('uuid')))
            ->where('location', 'tienda')
            ->whereIn('status', ['closed', 'opened'])
            ->with(['lot.variant.product.category', 'lot.variant.prices', 'transferLine'])
            ->first();

        if (!$package) {
            return response()->json([
                'success' => false,
                'message' => 'Bulto no encontrado en tienda o ya fue vendido.',
            ], 404);
        }

        $variant = $package->lot->variant;
        $prices = [];
        foreach ($variant->prices as $price) {
            $prices[$price->price_type] = (float) $price->price;
        }

        return response()->json([
            'success' => true,
            'package' => [
                'id'                 => $package->id,
                'uuid'               => $package->uuid,
                'product_variant_id' => $variant->id,
                'product'            => $variant->product->name . ' — ' . $variant->name,
                'category'           => $variant->product->category->name,
                'package_type'       => $package->package_type,
                'status'             => $package->status,
                'gross_weight'       => $package->gross_weight ? number_format((float)$package->gross_weight, 3, '.', '') : null,
                'net_weight'         => $package->net_weight ? number_format((float)$package->net_weight, 3, '.', '') : null,
                'unit_count'         => $package->unit_count,
                'net_units'          => $package->net_units,
                'available'          => $package->status === 'opened' ? number_format($package->available_quantity, 3, '.', '') : null,
                'unit'               => $variant->product->unit_type,
                'merma_kg'           => number_format((float) $package->transferLine->merma_kg, 3, '.', ''),
                'prices'             => $prices,
                'for_order'          => (bool) $package->for_order,
            ],
        ]);
    }

    public function openPackage(Request $request, Package $package)
    {
        try {
            $package = $this->saleService->openPackage($package);

            return response()->json([
                'success'    => true,
                'message'    => 'Bulto abierto. Disponible: ' . number_format($package->available_quantity, 3) . ' ' . ($package->net_weight ? 'kg' : 'und'),
                'net_weight' => $package->net_weight ? number_format((float)$package->net_weight, 3, '.', '') : null,
                'net_units'  => $package->net_units,
                'available'  => number_format($package->available_quantity, 3, '.', ''),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function checkCredit(Request $request)
    {
        $request->validate(['customer_id' => 'required|exists:customers,id']);

        $customer = Customer::findOrFail($request->input('customer_id'));

        return response()->json([
            'can_credit'      => $customer->canReceiveCredit(),
            'credit_blocked'  => $customer->credit_blocked,
            'credit_limit'    => (float) $customer->credit_limit,
            'pending_debt'    => $customer->total_credit_balance,
            'block_reason'    => $customer->credit_block_reason,
        ]);
    }

    /**
     * Registrar venta — REQUIERE CAJA ABIERTA (ya validado por cash_register_id)
     */
    public function storeSale(Request $request)
    {
        $request->validate([
            'cash_register_id'           => 'required|exists:cash_registers,id',
            'customer_id'                => 'nullable|exists:customers,id',
            'price_type'                 => 'required|in:minorista,mayorista',
            'discount'                   => 'nullable|numeric|min:0',
            'items'                      => 'required|array|min:1',
            'items.*.product_variant_id' => 'required|exists:product_variants,id',
            'items.*.package_id'         => 'nullable|exists:packages,id',
            'items.*.sell_mode'          => 'required|in:bulk,fraction',
            'items.*.quantity'           => 'required|numeric|min:0.001',
            'items.*.unit'               => 'required|in:kg,unit',
            'items.*.unit_price'         => 'required|numeric|min:0',
            'payments'                   => 'required|array|min:1',
            'payments.*.method'          => 'required|in:cash,transfer,other',
            'payments.*.amount'          => 'required|numeric|min:0',
            'payments.*.reference'       => 'nullable|string',
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();

        // Doble verificación: la caja debe estar abierta Y ser del usuario
        $register = $this->cashService->getOpenRegister($user->id);
        if (!$register || $register->id != $request->input('cash_register_id')) {
            return response()->json([
                'success' => false,
                'message' => 'Tu caja no está abierta o no coincide. Recarga la página.',
            ], 422);
        }

        try {
            $sale = $this->saleService->createSale($request->all(), $user->id);

            return response()->json([
                'success'     => true,
                'message'     => 'Venta registrada correctamente.',
                'sale_number' => $sale->sale_number,
                'total'       => number_format((float)$sale->total, 2),
                'sale_id'     => $sale->id,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}