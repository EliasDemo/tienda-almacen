<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductPrice;
use App\Models\Lot;
use App\Models\Package;
use App\Models\InventoryMovement;
use App\Models\TransferLine;
use App\Models\Transfer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductStockSeeder extends Seeder
{
    public function run(): void
    {
        $user = \App\Models\User::first();

        // ══════════════════════════════════════════
        // CATEGORÍAS (las que faltan)
        // ══════════════════════════════════════════
        $aves      = Category::firstOrCreate(['slug' => 'aves'], ['name' => 'Aves']);
        $embutidos = Category::firstOrCreate(['slug' => 'embutidos'], ['name' => 'Embutidos']);
        $res       = Category::firstOrCreate(['slug' => 'res'], ['name' => 'Res']);
        $cerdo     = Category::firstOrCreate(['slug' => 'cerdo'], ['name' => 'Cerdo']);
        $cordero   = Category::firstOrCreate(['slug' => 'cordero'], ['name' => 'Cordero']);
        $verduras  = Category::firstOrCreate(['slug' => 'verduras'], ['name' => 'Verduras']);
        $huevos    = Category::firstOrCreate(['slug' => 'huevos'], ['name' => 'Huevos']);
        $abarrotes = Category::firstOrCreate(['slug' => 'abarrotes'], ['name' => 'Abarrotes y Condimentos']);

        // ══════════════════════════════════════════
        // PRODUCTOS + VARIANTES + PRECIOS
        // ══════════════════════════════════════════
        // Formato: [categoría, producto, slug, unit_type, [variantes]]
        // Variante: [nombre, sku, sale_unit, precio_min, precio_may]

        $catalogo = [
            // ── AVES ──
            [$aves, 'Pollo Fresco', 'pollo-fresco', 'kg', [
                ['Pollo entero fresco', 'AVE-PFRE-ENT', 'kg', 12.50, 10.80],
                ['Pechuga fresca', 'AVE-PFRE-PCH', 'kg', 14.00, 12.50],
                ['Pierna fresca', 'AVE-PFRE-PIE', 'kg', 11.00, 9.50],
                ['Menudencia', 'AVE-PFRE-MEN', 'kg', 5.00, 4.00],
            ]],
            [$aves, 'Pollo Congelado', 'pollo-congelado', 'kg', [
                ['Pollo entero congelado', 'AVE-PCON-ENT', 'kg', 10.50, 9.00],
                ['Pechuga congelada', 'AVE-PCON-PCH', 'kg', 12.00, 10.50],
                ['Pierna congelada', 'AVE-PCON-PIE', 'kg', 9.50, 8.00],
            ]],
            [$aves, 'Gallina', 'gallina', 'kg', [
                // ya existe gallina entera, no duplicar
            ]],

            // ── RES ──
            [$res, 'Chuleta de Res', 'chuleta-res', 'kg', [
                ['Chuleta normal', 'RES-CHUL-NOR', 'kg', 22.00, 19.00],
                ['Chuleta mariposa', 'RES-CHUL-MAR', 'kg', 24.00, 21.00],
            ]],
            [$res, 'Cortes de Res', 'cortes-res', 'kg', [
                ['Canuto', 'RES-CORT-CAN', 'kg', 18.00, 15.50],
                ['Ñuta', 'RES-CORT-NUT', 'kg', 16.00, 14.00],
                ['Pulpa', 'RES-CORT-PUL', 'kg', 25.00, 22.00],
                ['Osobuco', 'RES-CORT-OSO', 'kg', 14.00, 12.00],
                ['Bistec', 'RES-CORT-BIS', 'kg', 23.00, 20.00],
            ]],
            [$res, 'Pata de Res', 'pata-res', 'kg', [
                ['Pata de res entera', 'RES-PATA-ENT', 'kg', 8.00, 6.50],
                ['Pata de res picada', 'RES-PATA-PIC', 'kg', 9.00, 7.50],
            ]],

            // ── CERDO ──
            [$cerdo, 'Cerdo', 'cerdo-cortes', 'kg', [
                ['Chuleta de cerdo', 'CER-CHUL-NOR', 'kg', 18.00, 15.00],
                ['Costilla de cerdo', 'CER-COST-NOR', 'kg', 16.00, 14.00],
                ['Pierna de cerdo', 'CER-PIER-NOR', 'kg', 15.00, 13.00],
                ['Panceta', 'CER-PANC-NOR', 'kg', 20.00, 17.00],
            ]],

            // ── CORDERO ──
            [$cordero, 'Cordero', 'cordero-cortes', 'kg', [
                ['Brazo de cordero', 'COR-BRAZ-NOR', 'kg', 28.00, 25.00],
                ['Pierna de cordero', 'COR-PIER-NOR', 'kg', 30.00, 27.00],
                ['Lomo de cordero', 'COR-LOMO-NOR', 'kg', 35.00, 32.00],
            ]],

            // ── EMBUTIDOS ──
            [$embutidos, 'Hot Dog', 'hot-dog', 'unit', [
                ['Hot Dog Braedt x paquete', 'EMB-HOTD-BRD', 'unit', 4.50, 3.80],
                ['Hot Dog Otto Kunz x paquete', 'EMB-HOTD-OTK', 'unit', 5.00, 4.20],
            ]],
            [$embutidos, 'Jamonada', 'jamonada', 'kg', [
                ['Jamonada Braedt', 'EMB-JAMO-BRD', 'kg', 16.00, 14.00],
                ['Jamonada Otto Kunz', 'EMB-JAMO-OTK', 'kg', 18.00, 15.50],
            ]],
            [$embutidos, 'Chorizo', 'chorizo', 'kg', [
                ['Chorizo parrillero', 'EMB-CHOR-PAR', 'kg', 20.00, 17.00],
                ['Chorizo ahumado', 'EMB-CHOR-AHU', 'kg', 22.00, 19.00],
            ]],
            [$embutidos, 'Tocino', 'tocino', 'kg', [
                ['Tocino ahumado', 'EMB-TOCI-AHU', 'kg', 28.00, 24.00],
            ]],

            // ── HUEVOS ──
            [$huevos, 'Huevo', 'huevo', 'unit', [
                ['Huevo rosado x unidad', 'HUE-ROSA-UNI', 'unit', 0.50, 0.40],
                ['Huevo rosado x plancha (30)', 'HUE-ROSA-PLA', 'unit', 13.00, 11.50],
                ['Huevo de codorniz x bolsa', 'HUE-CODR-BOL', 'unit', 3.00, 2.50],
            ]],

            // ── VERDURAS ──
            [$verduras, 'Papa', 'papa', 'kg', [
                ['Papa blanca', 'VER-PAPA-BLA', 'kg', 3.00, 2.50],
                ['Papa amarilla', 'VER-PAPA-AMA', 'kg', 4.00, 3.50],
                ['Papa huayro', 'VER-PAPA-HUA', 'kg', 3.50, 3.00],
            ]],
            [$verduras, 'Cebolla', 'cebolla', 'kg', [
                ['Cebolla roja', 'VER-CEBO-ROJ', 'kg', 3.50, 3.00],
            ]],
            [$verduras, 'Tomate', 'tomate', 'kg', [
                ['Tomate italiano', 'VER-TOMA-ITA', 'kg', 4.00, 3.50],
            ]],
            [$verduras, 'Zanahoria', 'zanahoria', 'kg', [
                ['Zanahoria', 'VER-ZANA-NOR', 'kg', 2.50, 2.00],
            ]],
            [$verduras, 'Ajo', 'ajo-fresco', 'kg', [
                ['Ajo entero', 'VER-AJO-ENT', 'kg', 12.00, 10.00],
            ]],
            [$verduras, 'Ají', 'aji-fresco', 'kg', [
                ['Ají amarillo', 'VER-AJI-AMA', 'kg', 6.00, 5.00],
                ['Ají panca seco', 'VER-AJI-PAN', 'kg', 15.00, 13.00],
                ['Rocoto', 'VER-ROCO-NOR', 'kg', 5.00, 4.00],
            ]],
            [$verduras, 'Limón', 'limon', 'kg', [
                ['Limón', 'VER-LIMO-NOR', 'kg', 5.00, 4.00],
            ]],

            // ── ABARROTES / CONDIMENTOS ──
            [$abarrotes, 'Comino', 'comino', 'unit', [
                ['Comino molido sachet', 'ABA-COMI-SAC', 'unit', 0.50, 0.40],
                ['Comino molido x 50g', 'ABA-COMI-50G', 'unit', 2.50, 2.00],
            ]],
            [$abarrotes, 'Ajo molido', 'ajo-molido', 'unit', [
                ['Ajo molido sachet', 'ABA-AJOM-SAC', 'unit', 0.50, 0.40],
                ['Ajo molido x frasco', 'ABA-AJOM-FRA', 'unit', 5.00, 4.20],
            ]],
            [$abarrotes, 'Ají preparado', 'aji-preparado', 'unit', [
                ['Ají panca pasta sachet', 'ABA-AJIP-SAC', 'unit', 1.00, 0.80],
                ['Ají amarillo pasta sachet', 'ABA-AJIA-SAC', 'unit', 1.00, 0.80],
            ]],
            [$abarrotes, 'Pimienta', 'pimienta', 'unit', [
                ['Pimienta molida sachet', 'ABA-PIMI-SAC', 'unit', 0.50, 0.40],
            ]],
            [$abarrotes, 'Orégano', 'oregano', 'unit', [
                ['Orégano sachet', 'ABA-OREG-SAC', 'unit', 0.50, 0.40],
                ['Orégano x bolsa 50g', 'ABA-OREG-50G', 'unit', 3.00, 2.50],
            ]],
            [$abarrotes, 'Sal', 'sal', 'unit', [
                ['Sal de mesa x 1kg', 'ABA-SAL-1KG', 'unit', 1.50, 1.20],
            ]],
            [$abarrotes, 'Aceite', 'aceite', 'unit', [
                ['Aceite vegetal x 1L', 'ABA-ACEI-1L', 'unit', 8.00, 7.00],
                ['Aceite de oliva x 200ml', 'ABA-OLIV-200', 'unit', 12.00, 10.00],
            ]],
            [$abarrotes, 'Vinagre', 'vinagre', 'unit', [
                ['Vinagre tinto x 500ml', 'ABA-VINA-500', 'unit', 3.50, 3.00],
            ]],
            [$abarrotes, 'Sillao', 'sillao', 'unit', [
                ['Sillao x botella', 'ABA-SILL-BOT', 'unit', 4.00, 3.50],
            ]],
        ];

        // ══════════════════════════════════════════
        // CREAR TODO
        // ══════════════════════════════════════════
        $allVariants = [];

        foreach ($catalogo as [$cat, $prodName, $prodSlug, $unitType, $variantes]) {
            if (empty($variantes)) continue;

            // Evitar duplicados
            $product = Product::firstOrCreate(
                ['slug' => $prodSlug],
                [
                    'category_id' => $cat->id,
                    'name' => $prodName,
                    'unit_type' => $unitType,
                ]
            );

            foreach ($variantes as [$vName, $sku, $saleUnit, $pMin, $pMay]) {
                $variant = ProductVariant::firstOrCreate(
                    ['sku_code' => $sku],
                    [
                        'product_id' => $product->id,
                        'name' => $vName,
                        'sale_unit' => $saleUnit,
                    ]
                );

                ProductPrice::updateOrCreate(
                    ['product_variant_id' => $variant->id, 'price_type' => 'minorista'],
                    ['price' => $pMin]
                );
                ProductPrice::updateOrCreate(
                    ['product_variant_id' => $variant->id, 'price_type' => 'mayorista'],
                    ['price' => $pMay]
                );

                $allVariants[] = [
                    'variant' => $variant,
                    'sale_unit' => $saleUnit,
                    'product_name' => $prodName,
                ];
            }
        }

        // ══════════════════════════════════════════
        // CREAR STOCK EN TIENDA
        // Para cada variante: lote → transfer → transfer_line → packages en tienda
        // ══════════════════════════════════════════
        $this->command->info('Creando stock en tienda...');

        // Un transfer general para meter todo
        $transfer = Transfer::create([
            'transfer_code' => 'CARG-SEED-' . now()->format('Ymd') . '-001',
            'dispatched_by' => $user->id,
            'received_by' => $user->id,
            'status' => 'received',
            'dispatched_at' => now()->subHours(2),
            'received_at' => now()->subHour(),
        ]);

        foreach ($allVariants as $item) {
            $variant = $item['variant'];
            $saleUnit = $item['sale_unit'];

            // Definir stock según tipo
            if ($saleUnit === 'kg') {
                $totalQty = rand(80, 300); // 80-300 kg
                $packageCount = rand(2, 5);
                $weightPerPkg = round($totalQty / $packageCount, 3);
            } else {
                $totalQty = rand(20, 100); // 20-100 unidades
                $packageCount = rand(1, 3);
                $weightPerPkg = null;
                $unitsPerPkg = (int) ceil($totalQty / $packageCount);
            }

            // Lote
            $lot = Lot::create([
                'product_variant_id' => $variant->id,
                'lot_code' => 'LOT-' . $variant->sku_code . '-' . now()->format('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT),
                'supplier' => $this->randomSupplier($item['product_name']),
                'purchase_price_per_kg' => $saleUnit === 'kg' ? round(rand(5, 20) + rand(0, 99) / 100, 2) : null,
                'purchase_price_per_unit' => $saleUnit === 'unit' ? round(rand(1, 10) + rand(0, 99) / 100, 2) : null,
                'total_quantity' => $totalQty,
                'unit' => $saleUnit,
                'remaining_quantity' => $totalQty,
                'entry_date' => now()->subDays(rand(1, 5)),
            ]);

            // Transfer line
            $tLine = TransferLine::create([
                'transfer_id' => $transfer->id,
                'product_variant_id' => $variant->id,
                'merma_kg' => $saleUnit === 'kg' ? round(rand(1, 5) / 10, 3) : 0,
                'total_packages' => $packageCount,
                'received_packages' => $packageCount,
                'transit_sold_packages' => 0,
            ]);

            // Inventory IN
            InventoryMovement::create([
                'product_variant_id' => $variant->id,
                'location' => 'almacen',
                'movement_type' => 'IN',
                'quantity' => $totalQty,
                'unit' => $saleUnit,
                'reference_type' => 'App\Models\Lot',
                'reference_id' => $lot->id,
                'user_id' => $user->id,
                'note' => 'Entrada seed',
                'occurred_at' => now()->subHours(3),
            ]);

            // Packages
            for ($i = 0; $i < $packageCount; $i++) {
                $isLast = ($i === $packageCount - 1);

                // Primer paquete de kg: abrirlo para tener stock suelto
                $openFirst = ($saleUnit === 'kg' && $i === 0);

                if ($saleUnit === 'kg') {
                    if ($isLast) {
                        // Último paquete: ajustar para que sume exacto
                        $pkgWeight = round($totalQty - ($weightPerPkg * ($packageCount - 1)), 3);
                    } else {
                        $pkgWeight = $weightPerPkg;
                    }

                    $pkg = Package::create([
                        'lot_id' => $lot->id,
                        'transfer_line_id' => $tLine->id,
                        'package_type' => 'saco',
                        'gross_weight' => $pkgWeight,
                        'net_weight' => $openFirst ? round($pkgWeight * 0.99, 3) : null,
                        'status' => $openFirst ? 'opened' : 'closed',
                        'location' => 'tienda',
                        'received_at' => now()->subHour(),
                        'opened_at' => $openFirst ? now()->subMinutes(30) : null,
                    ]);

                    // Movimientos
                    InventoryMovement::create([
                        'product_variant_id' => $variant->id,
                        'package_id' => $pkg->id,
                        'location' => 'almacen',
                        'movement_type' => 'TRANSFER_OUT',
                        'quantity' => -$pkgWeight,
                        'unit' => 'kg',
                        'reference_type' => 'App\Models\Transfer',
                        'reference_id' => $transfer->id,
                        'user_id' => $user->id,
                        'note' => 'Despacho seed',
                        'occurred_at' => now()->subHours(2),
                    ]);

                    InventoryMovement::create([
                        'product_variant_id' => $variant->id,
                        'package_id' => $pkg->id,
                        'location' => 'tienda',
                        'movement_type' => 'TRANSFER_IN',
                        'quantity' => $pkgWeight,
                        'unit' => 'kg',
                        'reference_type' => 'App\Models\Transfer',
                        'reference_id' => $transfer->id,
                        'user_id' => $user->id,
                        'note' => 'Recibido seed',
                        'occurred_at' => now()->subHour(),
                    ]);

                    if ($openFirst) {
                        $merma = round($pkgWeight * 0.01, 3);
                        InventoryMovement::create([
                            'product_variant_id' => $variant->id,
                            'package_id' => $pkg->id,
                            'location' => 'tienda',
                            'movement_type' => 'OPENING_MERMA',
                            'quantity' => -$merma,
                            'unit' => 'kg',
                            'user_id' => $user->id,
                            'note' => 'Merma al abrir seed',
                            'occurred_at' => now()->subMinutes(30),
                        ]);
                    }

                } else {
                    // Productos por unidad
                    $pkgUnits = $isLast ? ($totalQty - ($unitsPerPkg * ($packageCount - 1))) : $unitsPerPkg;

                    $pkg = Package::create([
                        'lot_id' => $lot->id,
                        'transfer_line_id' => $tLine->id,
                        'package_type' => 'caja',
                        'unit_count' => $pkgUnits,
                        'status' => 'closed',
                        'location' => 'tienda',
                        'received_at' => now()->subHour(),
                    ]);

                    InventoryMovement::create([
                        'product_variant_id' => $variant->id,
                        'package_id' => $pkg->id,
                        'location' => 'almacen',
                        'movement_type' => 'TRANSFER_OUT',
                        'quantity' => -$pkgUnits,
                        'unit' => 'unit',
                        'reference_type' => 'App\Models\Transfer',
                        'reference_id' => $transfer->id,
                        'user_id' => $user->id,
                        'note' => 'Despacho seed',
                        'occurred_at' => now()->subHours(2),
                    ]);

                    InventoryMovement::create([
                        'product_variant_id' => $variant->id,
                        'package_id' => $pkg->id,
                        'location' => 'tienda',
                        'movement_type' => 'TRANSFER_IN',
                        'quantity' => $pkgUnits,
                        'unit' => 'unit',
                        'reference_type' => 'App\Models\Transfer',
                        'reference_id' => $transfer->id,
                        'user_id' => $user->id,
                        'note' => 'Recibido seed',
                        'occurred_at' => now()->subHour(),
                    ]);
                }
            }
        }

        $this->command->info('✅ Catálogo creado: ' . count($allVariants) . ' variantes con stock en tienda');
    }

    private function randomSupplier(string $productName): string
    {
        $suppliers = [
            'Rico Pollo', 'San Fernando', 'Redondos', 'La Preferida',
            'Braedt', 'Otto Kunz', 'La Segoviana', 'Laive',
            'Camal Municipal', 'Proveedor Lima', 'Distribuidora Central',
            'Mercado Mayorista', 'Agro Import', 'Don Pollo SAC',
        ];
        return $suppliers[array_rand($suppliers)];
    }
}