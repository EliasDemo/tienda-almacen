@extends('layouts.app')

@section('title', 'Stock Tienda')

@section('content')
<div x-data="stockApp()" class="space-y-4">

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-warehouse text-blue-600 mr-2"></i>Stock Tienda</h1>
            <p class="text-sm text-gray-500 mt-1">Inventario actual · {{ now()->format('d/m/Y H:i') }}</p>
        </div>
        <div class="flex gap-2">
            <button @click="showAddModal = true" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2">
                <i class="fas fa-plus-circle"></i> Agregar Stock
            </button>
            <a href="{{ route('stock.print', ['category_id' => $categoryId]) }}" target="_blank"
               class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2">
                <i class="fas fa-print"></i> Imprimir
            </a>
        </div>
    </div>

    {{-- Cards resumen --}}
    <div class="grid grid-cols-2 md:grid-cols-6 gap-3">
        <div class="bg-white rounded-xl shadow-sm border p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center"><i class="fas fa-weight-hanging text-blue-600"></i></div>
                <div><p class="text-xs text-gray-500">Peso Total</p><p class="text-xl font-bold text-gray-900">{{ number_format($totals['total_weight'], 1) }} kg</p></div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center"><i class="fas fa-cubes text-purple-600"></i></div>
                <div><p class="text-xs text-gray-500">Unidades</p><p class="text-xl font-bold text-gray-900">{{ $totals['total_units'] }}</p></div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-indigo-100 flex items-center justify-center"><i class="fas fa-box text-indigo-600"></i></div>
                <div><p class="text-xs text-gray-500">Sacos</p><p class="text-xl font-bold text-gray-900">{{ $totals['closed_sacos'] }}</p></div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-cyan-100 flex items-center justify-center"><i class="fas fa-boxes-stacked text-cyan-600"></i></div>
                <div><p class="text-xs text-gray-500">Cajas</p><p class="text-xl font-bold text-gray-900">{{ $totals['closed_cajas'] }}</p></div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-green-100 flex items-center justify-center"><i class="fas fa-check-circle text-green-600"></i></div>
                <div><p class="text-xs text-gray-500">Con Stock</p><p class="text-xl font-bold text-gray-900">{{ $totals['variants_with_stock'] }}</p></div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-red-100 flex items-center justify-center"><i class="fas fa-exclamation-triangle text-red-600"></i></div>
                <div><p class="text-xs text-gray-500">Sin Stock</p><p class="text-xl font-bold text-gray-900">{{ $totals['variants_empty'] }}</p></div>
            </div>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="bg-white rounded-xl shadow-sm border p-4">
        <form method="GET" action="{{ route('stock.index') }}" class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="text-xs font-medium text-gray-500 block mb-1">Buscar producto</label>
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Nombre del producto..."
                           class="w-full pl-9 pr-3 py-2 border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>
            <div>
                <label class="text-xs font-medium text-gray-500 block mb-1">Categoría</label>
                <select name="category_id" class="border-gray-300 rounded-lg text-sm py-2 focus:ring-blue-500">
                    <option value="">Todas</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ $categoryId == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                <i class="fas fa-filter mr-1"></i> Filtrar
            </button>
            @if($search || $categoryId)
                <a href="{{ route('stock.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium transition">
                    <i class="fas fa-times mr-1"></i> Limpiar
                </a>
            @endif
        </form>
    </div>

    {{-- Tabla --}}
    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b">
                        <th class="text-left px-4 py-3 font-medium text-gray-500 w-12"></th>
                        <th class="text-left px-3 py-3 font-medium text-gray-500">Producto</th>
                        <th class="text-center px-3 py-3 font-medium text-gray-500"><i class="fas fa-box text-indigo-400 mr-1"></i>Sacos</th>
                        <th class="text-center px-3 py-3 font-medium text-gray-500"><i class="fas fa-boxes-stacked text-cyan-400 mr-1"></i>Cajas</th>
                        <th class="text-center px-3 py-3 font-medium text-gray-500"><i class="fas fa-box-open text-amber-400 mr-1"></i>Abiertos</th>
                        <th class="text-right px-3 py-3 font-medium text-gray-500">Stock Suelto</th>
                        <th class="text-right px-3 py-3 font-medium text-gray-500">Total</th>
                        <th class="text-right px-3 py-3 font-medium text-gray-500">P. Menor</th>
                        <th class="text-right px-3 py-3 font-medium text-gray-500">P. Mayor</th>
                        <th class="text-center px-3 py-3 font-medium text-gray-500">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @php $currentCategory = null; @endphp
                    @forelse($stock as $item)
                        @if($item->category_name !== $currentCategory)
                            @php $currentCategory = $item->category_name; @endphp
                            <tr class="bg-gray-100">
                                <td colspan="10" class="px-4 py-2 font-bold text-gray-600 text-xs uppercase tracking-wider">
                                    <i class="fas fa-tag mr-1"></i>{{ $currentCategory }}
                                </td>
                            </tr>
                        @endif
                        @php
                            $hasStock = $item->total_weight > 0 || $item->total_units > 0;
                            $isUnit = ($item->sale_unit === 'unit');
                            $productId = $variantProductMap[$item->id] ?? null;
                            $img = $productId ? ($images[$productId] ?? null) : null;
                            $imgUrl = $img ? asset('storage/' . $img->path) : 'https://www.domoticaonline.net/images/imagen-no-disponible.jpg';
                            $varPrices = $prices[$item->id] ?? collect();
                            $pMin = $varPrices->firstWhere('price_type', 'minorista');
                            $pMay = $varPrices->firstWhere('price_type', 'mayorista');
                        @endphp
                        <tr class="border-b hover:bg-gray-50 transition {{ !$hasStock ? 'opacity-50' : '' }}">
                            <td class="px-4 py-2">
                                <img src="{{ $imgUrl }}" alt="{{ $item->product_name }}" class="w-10 h-10 rounded-lg object-cover border">
                            </td>
                            <td class="px-3 py-3">
                                <div class="font-medium text-gray-900">{{ $item->product_name }}</div>
                                <div class="text-xs text-gray-500">{{ $item->variant_name }} ·
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold {{ $isUnit ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                                        {{ $isUnit ? 'UNIDAD' : 'KG' }}
                                    </span>
                                </div>
                            </td>
                            <td class="text-center px-3 py-3">
                                @if($item->closed_sacos > 0)
                                    <span class="bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded-full text-xs font-bold">{{ $item->closed_sacos }}</span>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="text-center px-3 py-3">
                                @if($item->closed_cajas > 0)
                                    <span class="bg-cyan-100 text-cyan-700 px-2 py-0.5 rounded-full text-xs font-bold">{{ $item->closed_cajas }}</span>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="text-center px-3 py-3">
                                @if($item->opened_packages > 0)
                                    <span class="bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full text-xs font-bold">{{ $item->opened_packages }}</span>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="text-right px-3 py-3">
                                @if($isUnit)
                                    @if($item->loose_units > 0)
                                        <span class="font-medium text-green-700">{{ $item->loose_units }}</span>
                                        <span class="text-xs text-gray-400">unid</span>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                @else
                                    @if($item->loose_kg > 0)
                                        <span class="font-medium text-green-700">{{ number_format($item->loose_kg, 2) }}</span>
                                        <span class="text-xs text-gray-400">kg</span>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                @endif
                            </td>
                            <td class="text-right px-3 py-3">
                                @if($isUnit)
                                    <span class="font-bold {{ $item->total_units > 0 ? 'text-gray-900' : 'text-red-500' }}">{{ $item->total_units }}</span>
                                    <span class="text-xs text-gray-400">unid</span>
                                @else
                                    <span class="font-bold {{ $item->total_weight > 0 ? 'text-gray-900' : 'text-red-500' }}">{{ number_format($item->total_weight, 2) }}</span>
                                    <span class="text-xs text-gray-400">kg</span>
                                @endif
                            </td>
                            <td class="text-right px-3 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <span class="text-xs text-gray-400">S/</span>
                                    <input type="number" step="0.01" min="0"
                                           value="{{ $pMin ? number_format($pMin->price, 2, '.', '') : '' }}"
                                           data-variant="{{ $item->id }}" data-type="minorista"
                                           @change="updatePrice($event)"
                                           class="w-20 border-gray-200 rounded text-xs text-right py-1 font-medium focus:ring-blue-500 focus:border-blue-500 hover:border-blue-300 transition"
                                           placeholder="0.00">
                                </div>
                            </td>
                            <td class="text-right px-3 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <span class="text-xs text-gray-400">S/</span>
                                    <input type="number" step="0.01" min="0"
                                           value="{{ $pMay ? number_format($pMay->price, 2, '.', '') : '' }}"
                                           data-variant="{{ $item->id }}" data-type="mayorista"
                                           @change="updatePrice($event)"
                                           class="w-20 border-gray-200 rounded text-xs text-right py-1 font-medium focus:ring-blue-500 focus:border-blue-500 hover:border-blue-300 transition"
                                           placeholder="0.00">
                                </div>
                            </td>
                            <td class="text-center px-3 py-3">
                                <a href="{{ route('stock.packages', $item->id) }}"
                                   class="text-blue-600 hover:text-blue-800 text-xs font-medium transition">
                                    <i class="fas fa-boxes-stacked"></i> Detalle
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-12 text-gray-400">
                                <i class="fas fa-inbox text-4xl mb-3 block"></i>
                                No se encontraron productos
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal Agregar Stock --}}
    <div x-show="showAddModal" x-transition class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div @click.outside="showAddModal = false" class="bg-white rounded-2xl shadow-2xl p-6 max-w-md w-full">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-900"><i class="fas fa-plus-circle text-blue-600 mr-2"></i>Agregar Stock Manual</h3>
                <button @click="showAddModal = false" class="text-gray-400 hover:text-gray-600 text-xl">&times;</button>
            </div>

            <div class="space-y-3">
                <div>
                    <label class="text-xs font-medium text-gray-500 block mb-1">Producto</label>
                    <select x-model="addForm.product_variant_id" @change="onVariantSelect()"
                            class="w-full border-gray-300 rounded-lg text-sm focus:ring-blue-500">
                        <option value="">Seleccionar producto...</option>
                        <template x-for="v in addVariants" :key="v.id">
                            <option :value="v.id" x-text="v.category + ' → ' + v.name"></option>
                        </template>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-medium text-gray-500 block mb-1">Tipo empaque</label>
                        <select x-model="addForm.package_type" class="w-full border-gray-300 rounded-lg text-sm focus:ring-blue-500">
                            <option value="saco">Saco</option>
                            <option value="caja">Caja</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-500 block mb-1">Cant. empaques</label>
                        <input type="number" min="1" x-model="addForm.packages_count"
                               class="w-full border-gray-300 rounded-lg text-sm focus:ring-blue-500" placeholder="1">
                    </div>
                </div>
                <div>
                    <label class="text-xs font-medium text-gray-500 block mb-1">
                        Cantidad total (<span x-text="selectedVariantUnit || 'kg/unid'"></span>)
                    </label>
                    <input type="number" step="0.001" min="0.001" x-model="addForm.quantity"
                           class="w-full border-gray-300 rounded-lg text-sm focus:ring-blue-500" placeholder="Ej: 100">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-medium text-gray-500 block mb-1">Proveedor (opcional)</label>
                        <input type="text" x-model="addForm.supplier"
                               class="w-full border-gray-300 rounded-lg text-sm focus:ring-blue-500" placeholder="Nombre">
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-500 block mb-1">Precio compra (opcional)</label>
                        <input type="number" step="0.01" min="0" x-model="addForm.purchase_price"
                               class="w-full border-gray-300 rounded-lg text-sm focus:ring-blue-500" placeholder="0.00">
                    </div>
                </div>
            </div>

            <div class="flex gap-2 mt-5">
                <button @click="showAddModal = false" class="flex-1 bg-gray-200 hover:bg-gray-300 rounded-lg py-2.5 font-medium text-sm transition">Cancelar</button>
                <button @click="submitAddStock()" :disabled="addingStock || !addForm.product_variant_id || !addForm.quantity"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white rounded-lg py-2.5 font-bold text-sm transition disabled:opacity-50">
                    <span x-show="!addingStock"><i class="fas fa-plus mr-1"></i> Agregar</span>
                    <span x-show="addingStock"><i class="fas fa-spinner fa-spin"></i></span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function stockApp() {
    return {
        showAddModal: false,
        addingStock: false,
        addVariants: [],
        selectedVariantUnit: '',
        addForm: { product_variant_id: '', package_type: 'saco', quantity: '', packages_count: 1, supplier: '', purchase_price: '' },

        init() { this.loadVariants(); },

        async loadVariants() {
            try {
                const res = await fetch('/stock/variants', { headers: { 'Accept': 'application/json' } });
                this.addVariants = await res.json();
            } catch(e) {}
        },

        onVariantSelect() {
            const v = this.addVariants.find(v => v.id == this.addForm.product_variant_id);
            this.selectedVariantUnit = v ? v.sale_unit : '';
            this.addForm.package_type = v && v.sale_unit === 'unit' ? 'caja' : 'saco';
        },

        async submitAddStock() {
            if (this.addingStock) return;
            this.addingStock = true;
            try {
                const res = await fetch('/stock/add-stock', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                    body: JSON.stringify(this.addForm),
                });
                const data = await res.json();
                if (data.success) {
                    Swal.fire({ toast: true, position: 'top-end', timer: 2500, showConfirmButton: false, icon: 'success', title: data.message });
                    this.showAddModal = false;
                    this.addForm = { product_variant_id: '', package_type: 'saco', quantity: '', packages_count: 1, supplier: '', purchase_price: '' };
                    setTimeout(() => location.reload(), 800);
                } else {
                    Swal.fire({ toast: true, position: 'top-end', timer: 2500, showConfirmButton: false, icon: 'error', title: data.message || 'Error' });
                }
            } catch(e) {
                Swal.fire({ toast: true, position: 'top-end', timer: 2500, showConfirmButton: false, icon: 'error', title: 'Error de conexión' });
            }
            this.addingStock = false;
        },

        async updatePrice(event) {
            const input = event.target;
            const variantId = input.dataset.variant;
            const priceType = input.dataset.type;
            const value = parseFloat(input.value) || 0;
            if (value < 0) { input.value = 0; return; }
            try {
                input.classList.add('border-blue-400', 'bg-blue-50');
                const res = await fetch(`/stock/price/${variantId}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                    body: JSON.stringify({ price_type: priceType, price: value }),
                });
                const data = await res.json();
                if (data.success) {
                    input.classList.remove('border-blue-400', 'bg-blue-50');
                    input.classList.add('border-green-400', 'bg-green-50');
                    setTimeout(() => input.classList.remove('border-green-400', 'bg-green-50'), 1500);
                    Swal.fire({ toast: true, position: 'top-end', timer: 1500, showConfirmButton: false, icon: 'success', title: 'Precio actualizado' });
                } else {
                    input.classList.add('border-red-400', 'bg-red-50');
                }
            } catch (e) {
                input.classList.add('border-red-400', 'bg-red-50');
            }
        }
    }
}
</script>
@endsection