<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductPrice;
use App\Models\Category;
use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with(['category', 'variants.prices', 'images'])
            ->orderBy('name')
            ->get();

        return view('admin.products.index', compact('products'));
    }

    public function create()
    {
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        return view('admin.products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_id'                => 'required|exists:categories,id',
            'name'                       => 'required|string|max:255',
            'unit_type'                  => 'required|in:kg,unit',
            'description'                => 'nullable|string',
            'image'                      => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'variants'                   => 'required|array|min:1',
            'variants.*.name'            => 'required|string|max:255',
            'variants.*.sku_code'        => 'required|string|unique:product_variants,sku_code',
            'variants.*.barcode'         => 'nullable|string|unique:product_variants,barcode',
            'variants.*.sale_unit'       => 'required|in:kg,unit',
            'variants.*.price_minorista' => 'required|numeric|min:0',
            'variants.*.price_mayorista' => 'required|numeric|min:0',
        ]);

        $product = Product::create([
            'category_id' => $request->category_id,
            'name'        => $request->name,
            'slug'        => Str::slug($request->name . '-' . Str::random(4)),
            'unit_type'   => $request->unit_type,
            'description' => $request->description,
        ]);

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $path = $file->store('products', 'public');
            Image::create([
                'imageable_type' => Product::class,
                'imageable_id'   => $product->id,
                'path'           => $path,
                'filename'       => $file->getClientOriginalName(),
                'mime_type'      => $file->getMimeType(),
                'size'           => $file->getSize(),
            ]);
        }

        foreach ($request->variants as $v) {
            $variant = ProductVariant::create([
                'product_id'        => $product->id,
                'name'              => $v['name'],
                'sku_code'          => $v['sku_code'],
                'barcode'           => $v['barcode'] ?? null,
                'sale_unit'         => $v['sale_unit'],
                'conversion_factor' => $v['conversion_factor'] ?? null,
            ]);

            ProductPrice::create([
                'product_variant_id' => $variant->id,
                'price_type'         => 'minorista',
                'price'              => $v['price_minorista'],
            ]);

            ProductPrice::create([
                'product_variant_id' => $variant->id,
                'price_type'         => 'mayorista',
                'price'              => $v['price_mayorista'],
            ]);
        }

        return redirect()->route('admin.products.index')
            ->with('success', "Producto '{$product->name}' creado con {$product->variants()->count()} variantes.");
    }

    public function edit(Product $product)
    {
        $product->load(['variants.prices', 'images']);
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        return view('admin.products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name'        => 'required|string|max:255',
            'unit_type'   => 'required|in:kg,unit',
            'description' => 'nullable|string',
            'image'       => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $product->update([
            'category_id' => $request->category_id,
            'name'        => $request->name,
            'unit_type'   => $request->unit_type,
            'description' => $request->description,
        ]);

        if ($request->hasFile('image')) {
            $oldImage = $product->images()->first();
            if ($oldImage) {
                Storage::disk('public')->delete($oldImage->path);
                $oldImage->delete();
            }

            $file = $request->file('image');
            $path = $file->store('products', 'public');
            Image::create([
                'imageable_type' => Product::class,
                'imageable_id'   => $product->id,
                'path'           => $path,
                'filename'       => $file->getClientOriginalName(),
                'mime_type'      => $file->getMimeType(),
                'size'           => $file->getSize(),
            ]);
        }

        return redirect()->route('admin.products.index')->with('success', 'Producto actualizado.');
    }

    public function deleteImage(Product $product)
    {
        $image = $product->images()->first();
        if ($image) {
            Storage::disk('public')->delete($image->path);
            $image->delete();
        }

        return back()->with('success', 'Imagen eliminada.');
    }

    public function toggleActive(Product $product)
    {
        $product->update(['is_active' => !$product->is_active]);
        return back()->with('success', $product->is_active ? 'Producto activado.' : 'Producto desactivado.');
    }

    public function updateVariant(Request $request, ProductVariant $variant)
    {
        $request->validate([
            'name'            => 'required|string|max:255',
            'sku_code'        => 'required|string|unique:product_variants,sku_code,' . $variant->id,
            'barcode'         => 'nullable|string|unique:product_variants,barcode,' . $variant->id,
            'sale_unit'       => 'required|in:kg,unit',
            'price_minorista' => 'required|numeric|min:0',
            'price_mayorista' => 'required|numeric|min:0',
        ]);

        $variant->update([
            'name'      => $request->name,
            'sku_code'  => $request->sku_code,
            'barcode'   => $request->barcode,
            'sale_unit' => $request->sale_unit,
        ]);

        $variant->prices()->updateOrCreate(
            ['price_type' => 'minorista'],
            ['price' => $request->price_minorista, 'is_active' => true]
        );

        $variant->prices()->updateOrCreate(
            ['price_type' => 'mayorista'],
            ['price' => $request->price_mayorista, 'is_active' => true]
        );

        return back()->with('success', 'Variante actualizada.');
    }

    public function addVariant(Request $request, Product $product)
    {
        $request->validate([
            'name'            => 'required|string|max:255',
            'sku_code'        => 'required|string|unique:product_variants,sku_code',
            'barcode'         => 'nullable|string|unique:product_variants,barcode',
            'sale_unit'       => 'required|in:kg,unit',
            'price_minorista' => 'required|numeric|min:0',
            'price_mayorista' => 'required|numeric|min:0',
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'name'       => $request->name,
            'sku_code'   => $request->sku_code,
            'barcode'    => $request->barcode,
            'sale_unit'  => $request->sale_unit,
        ]);

        ProductPrice::create([
            'product_variant_id' => $variant->id,
            'price_type'         => 'minorista',
            'price'              => $request->price_minorista,
        ]);

        ProductPrice::create([
            'product_variant_id' => $variant->id,
            'price_type'         => 'mayorista',
            'price'              => $request->price_mayorista,
        ]);

        return back()->with('success', 'Variante agregada.');
    }
}