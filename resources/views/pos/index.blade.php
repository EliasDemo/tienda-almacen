@extends('layouts.app')

@section('title', 'Punto de Venta - Touch')

@push('styles')
<style>
    /* ═══ Product Grid — Fast Food / Supermarket Style ═══ */
    .product-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: 8px;
        padding: 8px;
        overflow-y: auto;
        flex: 1;
        -webkit-overflow-scrolling: touch;
    }

    .product-card {
        position: relative;
        background: #fff;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        padding: 0;
        cursor: pointer;
        user-select: none;
        -webkit-tap-highlight-color: transparent;
        display: flex;
        flex-direction: column;
        align-items: center;
        overflow: hidden;
        transition: all 0.12s ease;
        aspect-ratio: 1 / 1;
    }
    .product-card:hover {
        border-color: #93c5fd;
        box-shadow: 0 2px 8px rgba(59,130,246,0.15);
    }
    .product-card:active {
        transform: scale(0.95);
        border-color: #3b82f6;
        background: #eff6ff;
    }
    .product-card.no-stock {
        opacity: 0.45;
        pointer-events: none;
    }

    /* Image area */
    .pc-img-wrap {
        width: 100%;
        height: 55%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        border-bottom: 1px solid #f1f5f9;
        overflow: hidden;
        position: relative;
    }
    .pc-img-wrap img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .pc-img-wrap .pc-icon {
        font-size: 28px;
        color: #cbd5e1;
    }

    /* Stock badge (top-right corner) */
    .pc-stock-badge {
        position: absolute;
        top: 4px;
        right: 4px;
        display: flex;
        flex-direction: column;
        gap: 2px;
        align-items: flex-end;
    }
    .pc-stock-badge .badge {
        font-size: 9px;
        font-weight: 700;
        padding: 1px 5px;
        border-radius: 4px;
        line-height: 1.4;
        white-space: nowrap;
        backdrop-filter: blur(4px);
    }
    .badge-kg {
        background: rgba(16, 185, 129, 0.9);
        color: #fff;
    }
    .badge-kg.low {
        background: rgba(245, 158, 11, 0.9);
    }
    .badge-kg.out {
        background: rgba(239, 68, 68, 0.85);
    }
    .badge-saco {
        background: rgba(59, 130, 246, 0.85);
        color: #fff;
    }

    /* Info area */
    .pc-info {
        width: 100%;
        padding: 6px 8px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        flex: 1;
        min-height: 0;
    }
    .pc-name {
        font-size: 11px;
        font-weight: 700;
        color: #1e293b;
        text-align: center;
        line-height: 1.2;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        width: 100%;
    }
    .pc-variant {
        font-size: 9px;
        color: #94a3b8;
        margin-top: 1px;
    }
    .pc-price {
        font-size: 15px;
        font-weight: 900;
        color: #059669;
        margin-top: 2px;
        letter-spacing: -0.5px;
    }

    /* Category pills */
    .cat-pills {
        display: flex;
        gap: 6px;
        padding: 8px 10px;
        overflow-x: auto;
        flex-shrink: 0;
        border-bottom: 2px solid #f1f5f9;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
    }
    .cat-pills::-webkit-scrollbar { display: none; }
    .cat-pill {
        padding: 6px 14px;
        border-radius: 20px;
        border: 2px solid #e5e7eb;
        background: #fff;
        color: #64748b;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        white-space: nowrap;
        transition: all 0.15s;
    }
    .cat-pill:active { transform: scale(0.95); }
    .cat-pill.active {
        background: #1e40af;
        border-color: #1e40af;
        color: #fff;
    }
</style>
@endpush

@section('content')
<div x-data="posSystem()" class="h-[calc(100vh-8rem)] bg-gray-100" @keydown.window="globalKey($event)">

    {{-- Header --}}
    <div class="bg-white rounded-t-lg shadow p-3 mb-3 flex justify-between items-center">
        <div class="flex items-center gap-4">
            <div>
                <span class="text-xs text-gray-500 block">Cliente</span>
                <select x-model="selectedCustomer" @change="onCustomerChange()"
                        class="border-0 bg-transparent font-bold text-lg p-0 focus:ring-0">
                    <option value="">Cliente Mostrador</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}" data-type="{{ $c->price_type }}">{{ $c->name }}{{ $c->phone ? ' · '.$c->phone : '' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="border-l pl-4">
                <span class="text-xs text-gray-500 block">Tipo Precio</span>
                <div class="flex bg-gray-100 rounded-lg p-0.5 gap-0.5">
                    <button @click="priceType = 'minorista'; loadProducts()"
                            class="px-3 py-1 rounded text-xs font-bold transition"
                            :class="priceType === 'minorista' ? 'bg-blue-600 text-white' : 'text-gray-500'">MENOR</button>
                    <button @click="priceType = 'mayorista'; loadProducts()"
                            class="px-3 py-1 rounded text-xs font-bold transition"
                            :class="priceType === 'mayorista' ? 'bg-blue-600 text-white' : 'text-gray-500'">MAYOR</button>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('pos.orders.index') }}" class="bg-purple-100 text-purple-700 px-3 py-1.5 rounded-lg hover:bg-purple-200 text-xs font-bold flex items-center gap-1">
                <i class="fas fa-clipboard-list"></i> Pedidos
            </a>
            <a href="{{ route('pos.credits.index') }}" class="bg-orange-100 text-orange-700 px-3 py-1.5 rounded-lg hover:bg-orange-200 text-xs font-bold flex items-center gap-1">
                <i class="fas fa-hand-holding-dollar"></i> Fiados
            </a>
            <div class="bg-green-50 border border-green-200 rounded-lg px-3 py-1.5 flex items-center gap-2">
                <i class="fas fa-cash-register text-green-600 text-xs"></i>
                <span class="text-xs font-bold text-green-700">{{ auth()->user()->name }}</span>
                <span class="text-xs text-green-500">{{ $register->opened_at->format('H:i') }}</span>
            </div>
            <a href="{{ route('pos.close-register') }}" class="bg-red-100 text-red-600 px-2 py-1.5 rounded-lg hover:bg-red-200 text-xs font-bold">
                <i class="fas fa-power-off"></i>
            </a>
        </div>
    </div>

    <div class="flex gap-3 h-[calc(100%-5rem)]">

        {{-- ═══ COLUMNA IZQUIERDA: Escáner + Productos ═══ --}}
        <div class="w-[420px] flex flex-col gap-3">

            {{-- Escáner y paquete escaneado --}}
            <div class="bg-white rounded-lg shadow p-3">
                <div class="flex gap-2">
                    <div class="flex-1 relative">
                        <i class="fas fa-qrcode absolute left-2.5 top-1/2 -translate-y-1/2 text-blue-400 text-sm"></i>
                        <input type="text" x-model="scanInput" x-ref="scanInput"
                               @keydown.enter.prevent="scanPackage()"
                               class="w-full pl-8 pr-3 py-2 border-gray-300 rounded-lg text-sm font-mono"
                               placeholder="Escanear QR / código... (F2)">
                    </div>
                    <button @click="toggleCamera()"
                            class="px-3 rounded-lg text-sm font-bold transition"
                            :class="cameraActive ? 'bg-red-100 text-red-600' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">
                        <i class="fas fa-camera"></i>
                    </button>
                </div>

                {{-- Paquete escaneado --}}
                <div x-show="scannedPackage" x-transition class="bg-amber-50 border border-amber-300 rounded-lg p-3 mt-2">
                    <template x-if="scannedPackage">
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-bold text-amber-800">
                                    <i class="fas fa-box mr-1"></i><span x-text="scannedPackage.product"></span>
                                </span>
                                <button @click="scannedPackage = null" class="text-gray-400 hover:text-gray-600 text-lg">&times;</button>
                            </div>
                            <div class="text-xs text-amber-600 mb-2">
                                Peso: <strong x-text="(scannedPackage.gross_weight || scannedPackage.unit_count) + ' ' + scannedPackage.unit"></strong>
                                · Estado: <strong x-text="scannedPackage.status === 'closed' ? 'Cerrado' : 'Abierto'"></strong>
                                <template x-if="scannedPackage.status === 'opened'">
                                    <span> · Disponible: <strong x-text="scannedPackage.available + ' ' + scannedPackage.unit"></strong></span>
                                </template>
                            </div>
                            <div x-show="scannedPackage.status === 'closed'" class="flex gap-2">
                                <button @click="addBulkToCart()"
                                        class="flex-1 bg-green-600 hover:bg-green-700 text-white py-2.5 rounded-lg text-sm font-bold transition">
                                    <i class="fas fa-box mr-1"></i> Saco completo
                                </button>
                                <button @click="openForFraction()" :disabled="openingPackage"
                                        class="flex-1 bg-orange-500 hover:bg-orange-600 text-white py-2.5 rounded-lg text-sm font-bold disabled:opacity-50 transition">
                                    <span x-show="!openingPackage"><i class="fas fa-box-open mr-1"></i> Abrir saco</span>
                                    <span x-show="openingPackage"><i class="fas fa-spinner fa-spin"></i></span>
                                </button>
                            </div>
                            <div x-show="scannedPackage.status === 'opened'" class="flex gap-2">
                                <input type="number" step="0.001" x-model="fractionQty" x-ref="fractionInput"
                                       @keydown.enter.prevent="addFractionToCart()"
                                       class="flex-1 border-gray-300 rounded-lg text-center text-lg font-bold h-10"
                                       placeholder="Cantidad en Kg">
                                <button @click="addFractionToCart()" :disabled="!fractionQty"
                                        class="bg-green-600 hover:bg-green-700 text-white px-4 rounded-lg font-bold disabled:opacity-50 transition">
                                    <i class="fas fa-plus mr-1"></i> Agregar
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- ═══ GRID DE PRODUCTOS — ESTILO FAST FOOD ═══ --}}
            <div class="flex-1 bg-white rounded-lg shadow overflow-hidden flex flex-col">
                {{-- Categorías como pills --}}
                <div class="cat-pills">
                    <button @click="selectedCategory = null; loadProducts()"
                            class="cat-pill" :class="!selectedCategory ? 'active' : ''">
                        <i class="fas fa-border-all mr-1"></i>Todos
                    </button>
                    @foreach($categories as $cat)
                    <button @click="selectedCategory = {{ $cat->id }}; loadProducts()"
                            class="cat-pill" :class="selectedCategory === {{ $cat->id }} ? 'active' : ''">
                        {{ $cat->name }}
                    </button>
                    @endforeach
                </div>

                {{-- Product Grid --}}
                <div class="product-grid">
                    {{-- Loading --}}
                    <template x-if="loadingProducts">
                        <div style="grid-column: 1/-1; padding: 40px 0; text-align: center; color: #94a3b8;">
                            <i class="fas fa-spinner fa-spin text-2xl"></i>
                            <p class="text-sm mt-2">Cargando...</p>
                        </div>
                    </template>

                    {{-- Product cards --}}
                    <template x-for="product in products" :key="product.id">
                        <div class="product-card"
                             :class="{ 'no-stock': product.loose_stock <= 0 && product.closed_packages <= 0 }"
                             @click="selectProductForQuantity(product)">

                            {{-- Image area --}}
                            <div class="pc-img-wrap">
                                <template x-if="product.image">
                                    <img :src="product.image" :alt="product.name">
                                </template>
                                <template x-if="!product.image">
                                    <i class="fas fa-drumstick-bite pc-icon"></i>
                                </template>

                                {{-- Stock badges --}}
                                <div class="pc-stock-badge">
                                    <span class="badge badge-kg"
                                          :class="{
                                              'low': product.loose_stock > 0 && product.loose_stock < 5,
                                              'out': product.loose_stock <= 0
                                          }"
                                          x-text="product.loose_stock > 0 ? parseFloat(product.loose_stock).toFixed(1) + ' kg' : 'Sin kg'">
                                    </span>
                                    <span x-show="product.closed_packages > 0" class="badge badge-saco"
                                          x-text="product.closed_packages + (product.closed_packages === 1 ? ' saco' : ' sacos')">
                                    </span>
                                </div>
                            </div>

                            {{-- Info --}}
                            <div class="pc-info">
                                <span class="pc-name" x-text="product.name"></span>
                                <span class="pc-variant" x-text="product.variant_name"></span>
                                <span class="pc-price"
                                      x-text="'S/ ' + (product.prices[priceType] || product.prices['minorista'] || 0).toFixed(2)">
                                </span>
                            </div>
                        </div>
                    </template>

                    {{-- Empty --}}
                    <template x-if="!loadingProducts && products.length === 0">
                        <div style="grid-column: 1/-1; padding: 40px 0; text-align: center; color: #cbd5e1;">
                            <i class="fas fa-inbox text-4xl mb-2" style="display:block;"></i>
                            <p class="text-sm">Sin productos en esta categoría</p>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- ═══ COLUMNA CENTRAL: Carrito + Teclado cantidad ═══ --}}
        <div class="flex-1 flex flex-col gap-3">

            {{-- Carrito --}}
            <div class="bg-white rounded-lg shadow overflow-hidden flex-1 flex flex-col">
                <div class="bg-gray-50 px-3 py-2 border-b flex justify-between items-center flex-shrink-0">
                    <span class="text-sm font-bold text-gray-700"><i class="fas fa-shopping-cart mr-1 text-blue-500"></i> Carrito</span>
                    <span class="text-xs text-gray-400" x-text="cart.length + ' items'"></span>
                </div>

                <div class="overflow-y-auto flex-1 min-h-0">
                    <template x-if="cart.length === 0">
                        <div class="text-center text-gray-300 py-8">
                            <i class="fas fa-shopping-cart text-4xl mb-2"></i>
                            <p class="text-sm">Escanea o selecciona un producto</p>
                        </div>
                    </template>
                    <template x-for="(item, idx) in cart" :key="idx">
                        <div class="border-b py-2 px-3 flex justify-between items-center hover:bg-gray-50 cursor-pointer"
                             :class="selectedCartIdx === idx ? 'bg-blue-50 border-l-4 border-l-blue-500' : ''"
                             @click="selectedCartIdx = idx">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-medium truncate" x-text="item.product"></span>
                                    <span class="text-[10px] px-1.5 py-0.5 rounded font-bold"
                                          :class="item.sell_mode === 'bulk' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700'"
                                          x-text="item.sell_mode === 'bulk' ? 'SACO' : 'KG'"></span>
                                </div>
                                <div class="text-xs text-gray-500">
                                    <span x-text="parseFloat(item.quantity).toFixed(3)"></span>
                                    <span x-text="item.unit"></span>
                                    × S/ <span x-text="parseFloat(item.unit_price).toFixed(2)"></span>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 flex-shrink-0">
                                <span class="font-bold text-sm" x-text="'S/ ' + (item.quantity * item.unit_price).toFixed(2)"></span>
                                <button @click.stop="removeFromCart(idx)"
                                        class="text-red-400 hover:text-red-600 w-6 h-6 rounded-full bg-red-50 hover:bg-red-100 flex items-center justify-center transition">
                                    <i class="fas fa-times text-xs"></i>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="border-t p-3 bg-gray-50 flex-shrink-0">
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-500">Subtotal</span>
                        <span class="font-medium" x-text="'S/ ' + subtotal.toFixed(2)"></span>
                    </div>
                    <div class="flex justify-between text-sm mb-1 items-center">
                        <span class="text-gray-500">Descuento</span>
                        <div class="flex items-center gap-1">
                            <span class="text-gray-400 text-xs">S/</span>
                            <input type="number" step="0.01" min="0" x-model="discount"
                                   class="w-16 border-gray-300 rounded text-xs text-right py-0.5 focus:ring-blue-500">
                        </div>
                    </div>
                    <div class="flex justify-between font-bold text-xl border-t pt-2 mt-1">
                        <span>TOTAL</span>
                        <span x-text="'S/ ' + total.toFixed(2)"></span>
                    </div>
                </div>
            </div>

            {{-- Panel de cantidad táctil --}}
            <div x-show="selectedProductForQty" x-transition class="bg-white rounded-lg shadow p-3 flex-shrink-0">
                <div class="flex justify-between items-center mb-2">
                    <div>
                        <span class="text-sm font-bold" x-text="selectedProductForQty?.name"></span>
                        <span class="text-xs text-gray-500 ml-1" x-text="selectedProductForQty?.variant_name"></span>
                        <span class="text-xs text-green-600 font-bold ml-2">
                            S/ <span x-text="(selectedProductForQty?.prices[priceType] || selectedProductForQty?.prices['minorista'] || 0).toFixed(2)"></span>/kg
                        </span>
                    </div>
                    <button @click="selectedProductForQty = null" class="text-gray-400 hover:text-gray-600 text-lg">&times;</button>
                </div>

                <div class="text-center mb-2">
                    <div class="text-4xl font-black text-blue-600" x-text="quantityInput || '0'"></div>
                    <span class="text-xs text-gray-400">kg · Disp: <span x-text="selectedProductForQty?.loose_stock || 0"></span> kg</span>
                </div>

                <div class="grid grid-cols-4 gap-1.5 mb-2">
                    <template x-for="n in [7,8,9]">
                        <button @click="qtyKey(String(n))"
                                class="bg-gray-100 hover:bg-gray-200 active:bg-gray-300 rounded-lg py-3 text-xl font-bold text-gray-700 transition"
                                x-text="n"></button>
                    </template>
                    <button @click="setQuickQuantity(0.5)" class="bg-blue-50 hover:bg-blue-100 rounded-lg py-3 text-sm font-bold text-blue-700">0.5kg</button>

                    <template x-for="n in [4,5,6]">
                        <button @click="qtyKey(String(n))"
                                class="bg-gray-100 hover:bg-gray-200 active:bg-gray-300 rounded-lg py-3 text-xl font-bold text-gray-700 transition"
                                x-text="n"></button>
                    </template>
                    <button @click="setQuickQuantity(1)" class="bg-blue-50 hover:bg-blue-100 rounded-lg py-3 text-sm font-bold text-blue-700">1 kg</button>

                    <template x-for="n in [1,2,3]">
                        <button @click="qtyKey(String(n))"
                                class="bg-gray-100 hover:bg-gray-200 active:bg-gray-300 rounded-lg py-3 text-xl font-bold text-gray-700 transition"
                                x-text="n"></button>
                    </template>
                    <button @click="setQuickQuantity(2)" class="bg-blue-50 hover:bg-blue-100 rounded-lg py-3 text-sm font-bold text-blue-700">2 kg</button>

                    <button @click="qtyKey('C')" class="bg-red-100 hover:bg-red-200 rounded-lg py-3 text-xl font-bold text-red-600 transition">C</button>
                    <button @click="qtyKey('0')" class="bg-gray-100 hover:bg-gray-200 active:bg-gray-300 rounded-lg py-3 text-xl font-bold text-gray-700 transition">0</button>
                    <button @click="qtyKey('.')" class="bg-gray-100 hover:bg-gray-200 rounded-lg py-3 text-xl font-bold text-gray-700 transition"
                            :disabled="quantityInput.includes('.')">.</button>
                    <button @click="qtyKey('⌫')" class="bg-gray-100 hover:bg-gray-200 rounded-lg py-3 text-lg font-bold text-gray-500 transition">⌫</button>
                </div>

                <div class="flex gap-2">
                    <button @click="selectedProductForQty = null"
                            class="flex-1 bg-gray-200 hover:bg-gray-300 rounded-lg py-3 font-medium transition">
                        Cancelar
                    </button>
                    <button @click="addSelectedProductToCart()"
                            class="flex-1 bg-green-600 hover:bg-green-700 text-white rounded-lg py-3 font-bold transition disabled:opacity-50"
                            :disabled="!quantityInput || parseFloat(quantityInput) <= 0">
                        <i class="fas fa-plus mr-1"></i> Agregar S/ <span x-text="calculatePrice().toFixed(2)"></span>
                    </button>
                </div>
            </div>
        </div>

        {{-- ═══ COLUMNA DERECHA: Pago ═══ --}}
        <div class="w-80 flex flex-col gap-3">

            {{-- Montos --}}
            <div class="bg-white rounded-lg shadow p-3">
                <div class="grid grid-cols-3 gap-2 mb-3">
                    <div class="text-center bg-red-50 rounded-lg p-2">
                        <span class="text-[10px] text-red-500 font-bold uppercase block">Por cobrar</span>
                        <span class="text-lg font-black text-red-600" x-text="'S/ ' + Math.max(0, total - totalPaid).toFixed(2)"></span>
                    </div>
                    <div class="text-center bg-blue-50 rounded-lg p-2">
                        <span class="text-[10px] text-blue-500 font-bold uppercase block">Recibido</span>
                        <span class="text-lg font-black text-blue-600" x-text="'S/ ' + numpadDisplay"></span>
                    </div>
                    <div class="text-center rounded-lg p-2" :class="change > 0 ? 'bg-green-50' : 'bg-gray-50'">
                        <span class="text-[10px] font-bold uppercase block" :class="change > 0 ? 'text-green-500' : 'text-gray-400'">Vuelto</span>
                        <span class="text-lg font-black" :class="change > 0 ? 'text-green-600' : 'text-gray-300'" x-text="'S/ ' + change.toFixed(2)"></span>
                    </div>
                </div>

                {{-- Numpad pago --}}
                <div class="grid grid-cols-4 gap-1.5 mb-3">
                    <button @click="np('7')" class="bg-gray-100 hover:bg-gray-200 active:bg-gray-300 rounded-lg py-2.5 text-lg font-bold text-gray-700 transition">7</button>
                    <button @click="np('8')" class="bg-gray-100 hover:bg-gray-200 active:bg-gray-300 rounded-lg py-2.5 text-lg font-bold text-gray-700 transition">8</button>
                    <button @click="np('9')" class="bg-gray-100 hover:bg-gray-200 active:bg-gray-300 rounded-lg py-2.5 text-lg font-bold text-gray-700 transition">9</button>
                    <button @click="npQuick(10)" class="bg-blue-600 hover:bg-blue-700 text-white rounded-lg py-2.5 text-sm font-bold transition">S/10</button>

                    <button @click="np('4')" class="bg-gray-100 hover:bg-gray-200 active:bg-gray-300 rounded-lg py-2.5 text-lg font-bold text-gray-700 transition">4</button>
                    <button @click="np('5')" class="bg-gray-100 hover:bg-gray-200 active:bg-gray-300 rounded-lg py-2.5 text-lg font-bold text-gray-700 transition">5</button>
                    <button @click="np('6')" class="bg-gray-100 hover:bg-gray-200 active:bg-gray-300 rounded-lg py-2.5 text-lg font-bold text-gray-700 transition">6</button>
                    <button @click="npQuick(20)" class="bg-blue-600 hover:bg-blue-700 text-white rounded-lg py-2.5 text-sm font-bold transition">S/20</button>

                    <button @click="np('1')" class="bg-gray-100 hover:bg-gray-200 active:bg-gray-300 rounded-lg py-2.5 text-lg font-bold text-gray-700 transition">1</button>
                    <button @click="np('2')" class="bg-gray-100 hover:bg-gray-200 active:bg-gray-300 rounded-lg py-2.5 text-lg font-bold text-gray-700 transition">2</button>
                    <button @click="np('3')" class="bg-gray-100 hover:bg-gray-200 active:bg-gray-300 rounded-lg py-2.5 text-lg font-bold text-gray-700 transition">3</button>
                    <button @click="npQuick(50)" class="bg-blue-600 hover:bg-blue-700 text-white rounded-lg py-2.5 text-sm font-bold transition">S/50</button>

                    <button @click="np('C')" class="bg-red-100 hover:bg-red-200 rounded-lg py-2.5 text-lg font-bold text-red-600 transition">C</button>
                    <button @click="np('0')" class="bg-gray-100 hover:bg-gray-200 active:bg-gray-300 rounded-lg py-2.5 text-lg font-bold text-gray-700 transition">0</button>
                    <button @click="np('.')" class="bg-gray-100 hover:bg-gray-200 rounded-lg py-2.5 text-lg font-bold text-gray-700 transition">.</button>
                    <button @click="np('⌫')" class="bg-gray-200 hover:bg-gray-300 rounded-lg py-2.5 text-lg text-gray-500 transition"><i class="fas fa-backspace"></i></button>
                </div>
            </div>

            {{-- Métodos de pago --}}
            <div class="bg-white rounded-lg shadow p-3">
                <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider block mb-2">Método de Pago</span>
                <div class="grid grid-cols-2 gap-2">
                    <button @click="payWith('cash')" :disabled="cart.length === 0"
                            class="py-3 rounded-lg font-bold text-sm bg-green-600 hover:bg-green-700 text-white transition disabled:opacity-30 flex flex-col items-center gap-1">
                        <i class="fas fa-money-bill-wave text-lg"></i> Efectivo
                    </button>
                    <button @click="payWith('transfer')" :disabled="cart.length === 0"
                            class="py-3 rounded-lg font-bold text-sm bg-blue-600 hover:bg-blue-700 text-white transition disabled:opacity-30 flex flex-col items-center gap-1">
                        <i class="fas fa-university text-lg"></i> Transfer
                    </button>
                    <button @click="payWith('credit')" :disabled="cart.length === 0 || !selectedCustomer"
                            class="py-3 rounded-lg font-bold text-sm bg-amber-500 hover:bg-amber-600 text-white transition disabled:opacity-30 flex flex-col items-center gap-1">
                        <i class="fas fa-hand-holding-dollar text-lg"></i> Fiar
                    </button>
                    <button @click="payWith('mixed')" :disabled="cart.length === 0"
                            class="py-3 rounded-lg font-bold text-sm bg-purple-600 hover:bg-purple-700 text-white transition disabled:opacity-30 flex flex-col items-center gap-1">
                        <i class="fas fa-coins text-lg"></i> Mixto
                    </button>
                </div>
                <div x-show="!selectedCustomer && cart.length > 0" class="text-[10px] text-amber-600 text-center mt-2">
                    <i class="fas fa-info-circle"></i> Selecciona cliente para fiar
                </div>
            </div>

            {{-- Acciones --}}
            <div class="bg-white rounded-lg shadow p-3">
                <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider block mb-2">Acciones</span>
                <div class="grid grid-cols-2 gap-2">
                    <a href="{{ route('pos.orders.create') }}"
                       class="py-2.5 rounded-lg font-medium text-xs bg-purple-50 hover:bg-purple-100 text-purple-700 flex items-center justify-center gap-1 transition text-center">
                        <i class="fas fa-clipboard-list"></i> Nuevo Pedido
                    </a>
                    <button @click="removeFromCart(selectedCartIdx)" :disabled="selectedCartIdx < 0"
                            class="py-2.5 rounded-lg font-medium text-xs bg-red-50 hover:bg-red-100 text-red-600 transition disabled:opacity-30 flex items-center justify-center gap-1">
                        <i class="fas fa-minus-circle"></i> Quitar Item
                    </button>
                    <button @click="clearAll()" :disabled="cart.length === 0"
                            class="py-2.5 rounded-lg font-medium text-xs bg-gray-100 hover:bg-gray-200 text-gray-600 transition disabled:opacity-30 flex items-center justify-center gap-1 col-span-2">
                        <i class="fas fa-trash"></i> Limpiar Todo
                    </button>
                </div>
            </div>

            {{-- COBRAR --}}
            <button @click="payWith('cash')" :disabled="cart.length === 0"
                    class="w-full bg-green-600 hover:bg-green-700 active:bg-green-800 text-white py-4 rounded-lg font-black text-xl shadow-lg disabled:opacity-30 transition flex items-center justify-center gap-2">
                <i class="fas fa-check-circle"></i> COBRAR
            </button>
        </div>
    </div>

    {{-- ═══ MODAL CÁMARA ═══ --}}
    <div x-show="cameraActive" x-transition class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-4 max-w-sm w-full">
            <div class="flex justify-between mb-3">
                <h3 class="font-bold text-sm">Escanear QR</h3>
                <button @click="stopCamera()" class="text-gray-400 hover:text-gray-600 text-xl">&times;</button>
            </div>
            <div id="qr-reader-pos" class="mx-auto rounded-lg overflow-hidden"></div>
            <p class="text-xs text-center text-gray-400 mt-2">Apunta al QR del bulto</p>
        </div>
    </div>

    {{-- ═══ MODAL VENTA EXITOSA ═══ --}}
    <div x-show="saleSuccess" x-transition class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-sm w-full text-center">
            <div class="text-green-500 text-5xl mb-3"><i class="fas fa-check-circle"></i></div>
            <p class="text-gray-500 text-sm">Venta N° <span x-text="lastSaleNumber" class="font-bold"></span></p>
            <p class="text-3xl font-black my-3">S/ <span x-text="lastSaleTotal"></span></p>
            <div x-show="lastCreditAmount > 0" class="bg-orange-50 border border-orange-200 rounded-xl p-3 mb-3">
                <p class="text-sm text-orange-700 font-bold"><i class="fas fa-hand-holding-dollar mr-1"></i>Fiado: S/ <span x-text="lastCreditAmount.toFixed(2)"></span></p>
            </div>
            <div x-show="lastChange > 0" class="bg-green-50 border border-green-200 rounded-xl p-3 mb-3">
                <p class="text-lg text-green-700 font-bold"><i class="fas fa-exchange-alt mr-1"></i>Vuelto: S/ <span x-text="lastChange.toFixed(2)"></span></p>
            </div>
            <button @click="newSale()" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-xl font-bold text-sm transition">
                <i class="fas fa-plus-circle mr-1"></i> Nueva Venta
            </button>
        </div>
    </div>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js"></script>

<script>
function posSystem() {
    let html5QrCode = null;

    return {
        scanInput: '', scanning: false, scannedPackage: null,
        fractionQty: '', openingPackage: false, cameraActive: false,
        products: [], loadingProducts: false, selectedCategory: null,
        cart: [], selectedCustomer: '', priceType: 'minorista',
        discount: 0, processingSale: false, selectedCartIdx: -1,
        saleSuccess: false, lastSaleNumber: '', lastSaleTotal: '',
        lastCreditAmount: 0, lastChange: 0,
        selectedProductForQty: null, quantityInput: '',
        numpadRaw: '',

        get numpadDisplay() { return this.numpadRaw || '0.00'; },
        get subtotal() { return this.cart.reduce((s, i) => s + (parseFloat(i.quantity) * parseFloat(i.unit_price)), 0); },
        get total() { return Math.max(0, this.subtotal - parseFloat(this.discount || 0)); },
        get totalPaid() { return parseFloat(this.numpadRaw) || 0; },
        get change() { return Math.max(0, this.totalPaid - this.total); },

        init() { this.loadProducts(); this.$refs.scanInput?.focus(); },

        globalKey(e) {
            if (['INPUT','SELECT','TEXTAREA'].includes(e.target.tagName)) return;
            if (e.key === 'F2') { e.preventDefault(); this.$refs.scanInput?.focus(); }
            if (e.key === 'Escape') { this.selectedProductForQty = null; }
        },

        // ─── Numpad pago ───
        np(k) {
            if (k === 'C') this.numpadRaw = '';
            else if (k === '⌫') this.numpadRaw = this.numpadRaw.slice(0, -1);
            else if (k === '.') { if (!this.numpadRaw.includes('.')) this.numpadRaw += '.'; }
            else this.numpadRaw += k;
        },
        npQuick(amt) { this.numpadRaw = String(amt); },

        // ─── Teclado cantidad ───
        qtyKey(k) {
            if (k === 'C') this.quantityInput = '';
            else if (k === '⌫') this.quantityInput = this.quantityInput.slice(0, -1);
            else if (k === '.') { if (!this.quantityInput.includes('.')) this.quantityInput += (this.quantityInput ? '.' : '0.'); }
            else this.quantityInput += k;
        },
        setQuickQuantity(kg) {
            if (kg <= (this.selectedProductForQty?.loose_stock || 0)) {
                this.quantityInput = kg.toString();
            } else {
                this.toast('Cantidad excede stock', 'error');
            }
        },
        calculatePrice() {
            if (!this.selectedProductForQty || !this.quantityInput) return 0;
            const qty = parseFloat(this.quantityInput) || 0;
            const price = this.selectedProductForQty.prices[this.priceType] || this.selectedProductForQty.prices['minorista'] || 0;
            return qty * price;
        },

        // ─── Productos ───
        onCustomerChange() {
            const sel = this.$el.querySelector('select[x-model="selectedCustomer"]');
            if (!sel) return;
            const opt = sel.options[sel.selectedIndex];
            if (opt && opt.dataset.type) { this.priceType = opt.dataset.type; this.loadProducts(); }
        },

        async loadProducts() {
            this.loadingProducts = true;
            try {
                let url = '{{ route("pos.products") }}';
                if (this.selectedCategory) url += '?category_id=' + this.selectedCategory;
                const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                this.products = await res.json();
            } catch (e) { this.toast('Error al cargar productos', 'error'); }
            this.loadingProducts = false;
        },

        selectProductForQuantity(product) {
            if (product.loose_stock <= 0 && product.closed_packages > 0) {
                this.toast('Escanea un saco de ' + product.name, 'info');
                this.$refs.scanInput?.focus();
                return;
            }
            if (product.loose_stock <= 0 && product.closed_packages <= 0) return;
            this.selectedProductForQty = product;
            this.quantityInput = '';
            this.scannedPackage = null;
        },

        addSelectedProductToCart() {
            if (!this.selectedProductForQty || !this.quantityInput) return;
            const qty = parseFloat(this.quantityInput);
            if (qty <= 0) return;
            if (qty > this.selectedProductForQty.loose_stock) { this.toast('Stock insuficiente', 'error'); return; }
            const price = this.selectedProductForQty.prices[this.priceType] || this.selectedProductForQty.prices['minorista'] || 0;
            this.cart.push({
                product_variant_id: this.selectedProductForQty.id, package_id: null,
                product: this.selectedProductForQty.name + ' — ' + this.selectedProductForQty.variant_name,
                sell_mode: 'fraction', quantity: qty, unit: this.selectedProductForQty.unit || 'kg',
                unit_price: price,
            });
            this.selectedProductForQty.loose_stock = Math.max(0, this.selectedProductForQty.loose_stock - qty);
            this.selectedProductForQty = null;
            this.quantityInput = '';
            this.toast('Agregado', 'success');
        },

        // ─── Escáner ───
        toggleCamera() {
            this.cameraActive = true;
            this.$nextTick(() => {
                html5QrCode = new Html5Qrcode("qr-reader-pos");
                html5QrCode.start({ facingMode: "environment" }, { fps: 10, qrbox: { width: 250, height: 250 } },
                    (text) => { this.scanInput = text; this.stopCamera(); this.scanPackage(); }, () => {}
                ).catch(() => { this.toast('Cámara no disponible', 'error'); this.cameraActive = false; });
            });
        },
        stopCamera() {
            this.cameraActive = false;
            if (html5QrCode?.isScanning) html5QrCode.stop().catch(() => {});
        },

        async scanPackage() {
            if (!this.scanInput.trim() || this.scanning) return;
            this.scanning = true; this.scannedPackage = null; this.selectedProductForQty = null;
            try {
                const res = await fetch('{{ route("pos.scan") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                    body: JSON.stringify({ uuid: this.scanInput.trim() }),
                });
                const data = await res.json();
                if (data.success) { this.scannedPackage = data.package; this.scanInput = ''; }
                else { this.toast(data.message, 'error'); this.scanInput = ''; }
            } catch (e) { this.toast('Error de conexión', 'error'); }
            this.scanning = false;
        },

        addBulkToCart() {
            if (!this.scannedPackage) return;
            const pkg = this.scannedPackage;
            this.cart.push({
                product_variant_id: pkg.product_variant_id, package_id: pkg.id, product: pkg.product,
                sell_mode: 'bulk', quantity: parseFloat(pkg.gross_weight || pkg.unit_count),
                unit: pkg.unit, unit_price: pkg.prices[this.priceType] || pkg.prices['minorista'] || 0,
            });
            this.scannedPackage = null; this.toast('Saco agregado', 'success');
            this.loadProducts(); this.$refs.scanInput?.focus();
        },

        async openForFraction() {
            if (!this.scannedPackage || this.openingPackage) return;
            this.openingPackage = true;
            try {
                const res = await fetch('/pos/open-package/' + this.scannedPackage.id, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                });
                const data = await res.json();
                if (data.success) {
                    Object.assign(this.scannedPackage, { status: 'opened', net_weight: data.net_weight, available: data.available });
                    this.toast(data.message, 'success'); this.loadProducts();
                    this.$nextTick(() => this.$refs.fractionInput?.focus());
                } else { this.toast(data.message, 'error'); }
            } catch (e) { this.toast('Error', 'error'); }
            this.openingPackage = false;
        },

        addFractionToCart() {
            if (!this.scannedPackage || !this.fractionQty) return;
            const pkg = this.scannedPackage;
            const qty = parseFloat(this.fractionQty);
            if (qty > parseFloat(pkg.available)) { this.toast('Stock insuficiente', 'error'); return; }
            this.cart.push({
                product_variant_id: pkg.product_variant_id, package_id: pkg.id, product: pkg.product,
                sell_mode: 'fraction', quantity: qty, unit: pkg.unit,
                unit_price: pkg.prices[this.priceType] || pkg.prices['minorista'] || 0,
            });
            pkg.available = (parseFloat(pkg.available) - qty).toFixed(3);
            this.fractionQty = '';
            this.toast(qty.toFixed(3) + ' ' + pkg.unit + ' agregado', 'success');
            if (parseFloat(pkg.available) <= 0) { this.scannedPackage = null; this.loadProducts(); this.$refs.scanInput?.focus(); }
            else { this.$refs.fractionInput?.focus(); }
        },

        removeFromCart(idx) {
            if (idx >= 0 && idx < this.cart.length) { this.cart.splice(idx, 1); this.selectedCartIdx = -1; }
        },
        clearAll() {
            this.cart = []; this.discount = 0; this.selectedCustomer = ''; this.numpadRaw = '';
            this.scannedPackage = null; this.selectedProductForQty = null;
            this.quantityInput = ''; this.selectedCartIdx = -1;
        },

        // ─── Pagos ───
        async payWith(method) {
            if (this.cart.length === 0) return;
            const t = this.total;
            const received = this.totalPaid || t;

            if (method === 'cash') {
                if (received < t) { this.toast('Monto insuficiente. Ingresa monto en el teclado.', 'error'); return; }
                this.lastChange = Math.max(0, received - t);
                await this.processSale([{ method: 'cash', amount: t, reference: null }]);
            } else if (method === 'transfer') {
                const { value } = await Swal.fire({
                    title: 'Referencia transferencia', input: 'text',
                    inputPlaceholder: 'N° operación (opcional)',
                    showCancelButton: true, confirmButtonColor: '#3b82f6', confirmButtonText: 'Confirmar'
                });
                if (value !== undefined) { this.lastChange = 0; await this.processSale([{ method: 'transfer', amount: t, reference: value || null }]); }
            } else if (method === 'credit') {
                if (!this.selectedCustomer) { this.toast('Selecciona cliente para fiar', 'error'); return; }
                try {
                    const res = await fetch('/pos/check-credit', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                        body: JSON.stringify({ customer_id: this.selectedCustomer })
                    });
                    const data = await res.json();
                    if (!data.can_credit) { this.toast(data.block_reason || 'Crédito bloqueado', 'error'); return; }
                } catch(e) {}
                const ok = await Swal.fire({ title: '¿Fiar S/ ' + t.toFixed(2) + '?', icon: 'question', showCancelButton: true, confirmButtonColor: '#f59e0b', confirmButtonText: 'Sí, fiar' });
                if (ok.isConfirmed) { this.lastChange = 0; this.lastCreditAmount = t; await this.processSale([{ method: 'cash', amount: 0, reference: 'FIADO' }]); }
            } else if (method === 'mixed') {
                const { value } = await Swal.fire({
                    title: 'Pago Mixto — S/ ' + t.toFixed(2),
                    html: '<div style="text-align:left"><div style="margin-bottom:8px"><label style="font-size:13px;font-weight:600">Efectivo</label><input id="mx-c" type="number" step="0.01" min="0" value="0" class="swal2-input" style="margin:4px 0 0;width:100%"></div><div><label style="font-size:13px;font-weight:600">Transferencia</label><input id="mx-t" type="number" step="0.01" min="0" value="0" class="swal2-input" style="margin:4px 0 0;width:100%"></div></div>',
                    showCancelButton: true, confirmButtonColor: '#7c3aed', confirmButtonText: 'Cobrar',
                    preConfirm: () => {
                        const c = parseFloat(document.getElementById('mx-c').value) || 0;
                        const tr = parseFloat(document.getElementById('mx-t').value) || 0;
                        if (c + tr < t - 0.01) { Swal.showValidationMessage('Monto insuficiente'); return false; }
                        return { cash: c, transfer: tr };
                    }
                });
                if (value) {
                    const pays = [];
                    if (value.cash > 0) pays.push({ method: 'cash', amount: value.cash, reference: null });
                    if (value.transfer > 0) pays.push({ method: 'transfer', amount: value.transfer, reference: null });
                    this.lastChange = Math.max(0, value.cash + value.transfer - t);
                    await this.processSale(pays);
                }
            }
        },

        async processSale(payments) {
            if (this.processingSale) return;
            this.processingSale = true;
            try {
                const res = await fetch('{{ route("pos.store-sale") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                    body: JSON.stringify({
                        cash_register_id: {{ $register->id }},
                        customer_id: this.selectedCustomer || null,
                        price_type: this.priceType, discount: this.discount,
                        items: this.cart.map(i => ({
                            product_variant_id: i.product_variant_id, package_id: i.package_id,
                            sell_mode: i.sell_mode, quantity: i.quantity, unit: i.unit, unit_price: i.unit_price
                        })),
                        payments: payments.filter(p => parseFloat(p.amount) >= 0),
                    }),
                });
                const data = await res.json();
                if (data.success) {
                    this.lastSaleNumber = data.sale_number;
                    this.lastSaleTotal = data.total;
                    this.saleSuccess = true;
                    this.loadProducts();
                } else { this.toast(data.message, 'error'); }
            } catch (e) { this.toast('Error al procesar', 'error'); }
            this.processingSale = false;
        },

        newSale() {
            this.cart = []; this.scannedPackage = null; this.selectedProductForQty = null;
            this.scanInput = ''; this.discount = 0; this.selectedCustomer = '';
            this.priceType = 'minorista'; this.numpadRaw = ''; this.quantityInput = '';
            this.saleSuccess = false; this.lastCreditAmount = 0; this.lastChange = 0;
            this.selectedCartIdx = -1;
            this.loadProducts();
            this.$nextTick(() => this.$refs.scanInput?.focus());
        },

        toast(msg, type) {
            Swal.fire({ toast: true, position: 'top-end', timer: 2500, showConfirmButton: false, icon: type, title: msg });
        },
    }
}
</script>
@endsection