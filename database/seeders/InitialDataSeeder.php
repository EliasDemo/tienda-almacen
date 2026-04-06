<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductPrice;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class InitialDataSeeder extends Seeder
{
    public function run(): void
    {
        // ── Usuario Admin ────────────────────────────────────────

        $admin = User::firstOrCreate(
            ['email' => 'admin@sistema.local'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('password'),
            ]
        );
        $admin->assignRole('admin');

        // ── Categorías ───────────────────────────────────────────

        $aves = Category::create(['name' => 'Aves', 'slug' => 'aves']);
        $embutidos = Category::create(['name' => 'Embutidos', 'slug' => 'embutidos']);
        $res = Category::create(['name' => 'Res', 'slug' => 'res']);
        $cerdo = Category::create(['name' => 'Cerdo', 'slug' => 'cerdo']);

        // ── Pollo (por kg) ───────────────────────────────────────

        $pollo = Product::create([
            'category_id' => $aves->id,
            'name' => 'Pollo',
            'slug' => 'pollo',
            'unit_type' => 'kg',
        ]);

        $polloEntero = ProductVariant::create([
            'product_id' => $pollo->id,
            'name' => 'Pollo entero',
            'sku_code' => 'AVE-POLL-ENT',
            'sale_unit' => 'kg',
        ]);

        ProductPrice::create([
            'product_variant_id' => $polloEntero->id,
            'price_type' => 'minorista',
            'price' => 12.50,
        ]);

        ProductPrice::create([
            'product_variant_id' => $polloEntero->id,
            'price_type' => 'mayorista',
            'price' => 10.80,
        ]);

        // ── Gallina (por kg) ─────────────────────────────────────

        $gallina = Product::create([
            'category_id' => $aves->id,
            'name' => 'Gallina',
            'slug' => 'gallina',
            'unit_type' => 'kg',
        ]);

        $gallinaEntera = ProductVariant::create([
            'product_id' => $gallina->id,
            'name' => 'Gallina entera',
            'sku_code' => 'AVE-GALL-ENT',
            'sale_unit' => 'kg',
        ]);

        ProductPrice::create([
            'product_variant_id' => $gallinaEntera->id,
            'price_type' => 'minorista',
            'price' => 14.00,
        ]);

        ProductPrice::create([
            'product_variant_id' => $gallinaEntera->id,
            'price_type' => 'mayorista',
            'price' => 12.00,
        ]);

        // ── Salchicha (por unidad) ───────────────────────────────

        $salchicha = Product::create([
            'category_id' => $embutidos->id,
            'name' => 'Salchicha',
            'slug' => 'salchicha',
            'unit_type' => 'unit',
        ]);

        $salchichaBraedt = ProductVariant::create([
            'product_id' => $salchicha->id,
            'name' => 'Salchicha Braedt x paquete',
            'sku_code' => 'EMB-SALC-BRD',
            'sale_unit' => 'unit',
        ]);

        ProductPrice::create([
            'product_variant_id' => $salchichaBraedt->id,
            'price_type' => 'minorista',
            'price' => 3.50,
        ]);

        ProductPrice::create([
            'product_variant_id' => $salchichaBraedt->id,
            'price_type' => 'mayorista',
            'price' => 2.80,
        ]);
    }
}