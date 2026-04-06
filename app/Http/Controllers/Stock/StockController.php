<?php

namespace App\Http\Controllers\Stock;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Package;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductPrice;
use App\Models\Lot;
use App\Models\InventoryMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StockController extends Controller
{
    public function index(Request $request)
    {
        $categoryId = $request->input('category_id');
        $search = $request->input('search');

        $categories = Category::orderBy('name')->get();

        $stock = ProductVariant::query()
            ->select([
                'product_variants.id',
                'product_variants.name as variant_name',
                'product_variants.sale_unit',
                'products.name as product_name',
                'products.category_id',
                'products.unit_type',
                'categories.name as category_name',
            ])
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->selectRaw("
                COALESCE(SUM(CASE WHEN p.status = 'closed' AND p.package_type = 'saco' THEN 1 ELSE 0 END), 0) as closed_sacos,
                COALESCE(SUM(CASE WHEN p.status = 'closed' AND p.package_type = 'caja' THEN 1 ELSE 0 END), 0) as closed_cajas,
                COALESCE(SUM(CASE WHEN p.status = 'opened' THEN 1 ELSE 0 END), 0) as opened_packages,
                COALESCE(SUM(CASE WHEN p.status = 'closed' THEN p.gross_weight ELSE 0 END), 0) as closed_weight,
                COALESCE(SUM(CASE WHEN p.status = 'closed' THEN COALESCE(p.unit_count, 0) ELSE 0 END), 0) as closed_units,
                COALESCE(SUM(CASE WHEN p.status = 'opened' THEN p.net_weight ELSE 0 END), 0) as loose_kg,
                COALESCE(SUM(CASE WHEN p.status = 'opened' THEN COALESCE(p.net_units, 0) ELSE 0 END), 0) as loose_units,
                COALESCE(SUM(CASE WHEN p.status IN ('closed','opened') THEN COALESCE(p.net_weight, p.gross_weight, 0) ELSE 0 END), 0) as total_weight,
                COALESCE(SUM(CASE WHEN p.status IN ('closed','opened') THEN COALESCE(p.unit_count, 0) + COALESCE(p.net_units, 0) ELSE 0 END), 0) as total_units
            ")
            ->leftJoin('lots', 'lots.product_variant_id', '=', 'product_variants.id')
            ->leftJoin('packages as p', function ($join) {
                $join->on('p.lot_id', '=', 'lots.id')
                    ->where('p.location', '=', 'tienda')
                    ->whereIn('p.status', ['closed', 'opened']);
            })
            ->groupBy('product_variants.id', 'product_variants.name', 'product_variants.sale_unit', 'products.name', 'products.category_id', 'products.unit_type', 'categories.name')
            ->when($categoryId, fn($q) => $q->where('products.category_id', $categoryId))
            ->when($search, fn($q) => $q->where(function ($q2) use ($search) {
                $q2->where('products.name', 'like', "%{$search}%")
                   ->orWhere('product_variants.name', 'like', "%{$search}%");
            }))
            ->orderBy('categories.name')
            ->orderBy('products.name')
            ->orderBy('product_variants.name')
            ->get();

        // Imágenes de productos
        $productIds = Product::whereIn('category_id', $categories->pluck('id'))->pluck('id');
        $images = \App\Models\Image::where('imageable_type', 'App\Models\Product')
            ->whereIn('imageable_id', $productIds)
            ->get()
            ->keyBy('imageable_id');

        // Map variant_id → product_id para buscar imagen
        $variantProductMap = ProductVariant::whereIn('id', $stock->pluck('id'))
            ->pluck('product_id', 'id');

        $variantIds = $stock->pluck('id');
        $prices = ProductPrice::whereIn('product_variant_id', $variantIds)
            ->get()
            ->groupBy('product_variant_id');

        $totals = [
            'total_weight' => $stock->sum('total_weight'),
            'total_units' => $stock->sum('total_units'),
            'closed_sacos' => $stock->sum('closed_sacos'),
            'closed_cajas' => $stock->sum('closed_cajas'),
            'opened_packages' => $stock->sum('opened_packages'),
            'variants_count' => $stock->count(),
            'variants_with_stock' => $stock->filter(fn($i) => $i->total_weight > 0 || $i->total_units > 0)->count(),
            'variants_empty' => $stock->filter(fn($i) => $i->total_weight <= 0 && $i->total_units <= 0)->count(),
        ];

        return view('stock.index', compact('stock', 'categories', 'prices', 'totals', 'categoryId', 'search', 'images', 'variantProductMap'));
    }

    public function updatePrice(Request $request, ProductVariant $variant)
    {
        $request->validate([
            'price_type' => 'required|string|in:minorista,mayorista',
            'price' => 'required|numeric|min:0',
        ]);

        ProductPrice::updateOrCreate(
            ['product_variant_id' => $variant->id, 'price_type' => $request->price_type],
            ['price' => $request->price]
        );

        return response()->json(['success' => true, 'message' => 'Precio actualizado']);
    }

    /**
     * Agregar stock manualmente (sin pasar por almacén/despacho)
     */
    public function addStock(Request $request)
    {
        $request->validate([
            'product_variant_id' => 'required|exists:product_variants,id',
            'package_type' => 'required|in:saco,caja',
            'quantity' => 'required|numeric|min:0.001',
            'packages_count' => 'required|integer|min:1',
            'supplier' => 'nullable|string|max:255',
            'purchase_price' => 'nullable|numeric|min:0',
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();
        $variant = ProductVariant::with('product')->findOrFail($request->product_variant_id);
        $isKg = ($variant->sale_unit === 'kg');
        $totalQty = $request->quantity;
        $pkgCount = $request->packages_count;

        // Crear lote
        $lot = Lot::create([
            'product_variant_id' => $variant->id,
            'lot_code' => 'LOT-' . $variant->sku_code . '-' . now()->format('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT),
            'supplier' => $request->supplier,
            'purchase_price_per_kg' => $isKg ? $request->purchase_price : null,
            'purchase_price_per_unit' => !$isKg ? $request->purchase_price : null,
            'total_quantity' => $totalQty,
            'unit' => $variant->sale_unit,
            'remaining_quantity' => $totalQty,
            'entry_date' => now()->toDateString(),
        ]);

        // Movimiento IN
        InventoryMovement::create([
            'product_variant_id' => $variant->id,
            'location' => 'tienda',
            'movement_type' => 'IN',
            'quantity' => $totalQty,
            'unit' => $variant->sale_unit,
            'reference_type' => 'App\Models\Lot',
            'reference_id' => $lot->id,
            'user_id' => $user->id,
            'note' => 'Entrada manual a tienda',
            'occurred_at' => now(),
        ]);

        // Crear paquetes
        $qtyPerPkg = $isKg ? round($totalQty / $pkgCount, 3) : (int) ceil($totalQty / $pkgCount);

        for ($i = 0; $i < $pkgCount; $i++) {
            $isLast = ($i === $pkgCount - 1);

            if ($isKg) {
                $pkgQty = $isLast ? round($totalQty - ($qtyPerPkg * ($pkgCount - 1)), 3) : $qtyPerPkg;
                Package::create([
                    'lot_id' => $lot->id,
                    'package_type' => $request->package_type,
                    'gross_weight' => $pkgQty,
                    'status' => 'closed',
                    'location' => 'tienda',
                    'received_at' => now(),
                ]);
            } else {
                $pkgQty = $isLast ? ($totalQty - ($qtyPerPkg * ($pkgCount - 1))) : $qtyPerPkg;
                Package::create([
                    'lot_id' => $lot->id,
                    'package_type' => $request->package_type,
                    'unit_count' => $pkgQty,
                    'status' => 'closed',
                    'location' => 'tienda',
                    'received_at' => now(),
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Stock agregado: {$totalQty} " . ($isKg ? 'kg' : 'unid.') . " en {$pkgCount} " . ($request->package_type === 'saco' ? 'sacos' : 'cajas'),
        ]);
    }

    public function packages(ProductVariant $variant)
    {
        $variant->load('product.category');

        $packages = Package::whereHas('lot', function ($q) use ($variant) {
                $q->where('product_variant_id', $variant->id);
            })
            ->where('location', 'tienda')
            ->whereIn('status', ['closed', 'opened'])
            ->with('lot', 'transferLine.transfer')
            ->orderByDesc('created_at')
            ->get();

        $prices = ProductPrice::where('product_variant_id', $variant->id)->get()->keyBy('price_type');

        // Imagen del producto
        $image = \App\Models\Image::where('imageable_type', 'App\Models\Product')
            ->where('imageable_id', $variant->product_id)
            ->first();

        return view('stock.packages', compact('variant', 'packages', 'prices', 'image'));
    }

    public function printReport(Request $request)
    {
        $categoryId = $request->input('category_id');

        $stock = ProductVariant::query()
            ->select([
                'product_variants.id',
                'product_variants.name as variant_name',
                'product_variants.sale_unit',
                'products.name as product_name',
                'products.unit_type',
                'categories.name as category_name',
            ])
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->selectRaw("
                COALESCE(SUM(CASE WHEN p.status = 'closed' AND p.package_type = 'saco' THEN 1 ELSE 0 END), 0) as closed_sacos,
                COALESCE(SUM(CASE WHEN p.status = 'closed' AND p.package_type = 'caja' THEN 1 ELSE 0 END), 0) as closed_cajas,
                COALESCE(SUM(CASE WHEN p.status = 'opened' THEN 1 ELSE 0 END), 0) as opened_packages,
                COALESCE(SUM(CASE WHEN p.status = 'opened' THEN p.net_weight ELSE 0 END), 0) as loose_kg,
                COALESCE(SUM(CASE WHEN p.status IN ('closed','opened') THEN COALESCE(p.net_weight, p.gross_weight, 0) ELSE 0 END), 0) as total_weight,
                COALESCE(SUM(CASE WHEN p.status IN ('closed','opened') THEN COALESCE(p.unit_count, 0) ELSE 0 END), 0) as total_units
            ")
            ->leftJoin('lots', 'lots.product_variant_id', '=', 'product_variants.id')
            ->leftJoin('packages as p', function ($join) {
                $join->on('p.lot_id', '=', 'lots.id')
                    ->where('p.location', '=', 'tienda')
                    ->whereIn('p.status', ['closed', 'opened']);
            })
            ->groupBy('product_variants.id', 'product_variants.name', 'product_variants.sale_unit', 'products.name', 'products.unit_type', 'categories.name')
            ->havingRaw('total_weight > 0 OR total_units > 0')
            ->when($categoryId, fn($q) => $q->where('products.category_id', $categoryId))
            ->orderBy('categories.name')
            ->orderBy('products.name')
            ->get();

        $variantIds = $stock->pluck('id');
        $prices = ProductPrice::whereIn('product_variant_id', $variantIds)->get()->groupBy('product_variant_id');
        $totalWeight = $stock->sum('total_weight');
        $totalUnits = $stock->sum('total_units');
        $totalPackages = $stock->sum('closed_sacos') + $stock->sum('closed_cajas');

        return view('stock.print', compact('stock', 'prices', 'totalWeight', 'totalUnits', 'totalPackages', 'categoryId'));
    }

    /**
     * API: variantes para select de agregar stock
     */
    public function variants(Request $request)
    {
        $variants = ProductVariant::with('product.category')
            ->where('is_active', true)
            ->whereHas('product', fn($q) => $q->where('is_active', true))
            ->orderBy('product_id')
            ->get()
            ->map(fn($v) => [
                'id' => $v->id,
                'name' => $v->product->name . ' — ' . $v->name,
                'category' => $v->product->category->name,
                'sale_unit' => $v->sale_unit,
            ]);

        return response()->json($variants);
    }
}