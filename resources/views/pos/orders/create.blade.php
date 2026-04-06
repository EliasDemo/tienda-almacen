@extends('layouts.app')

@section('title', 'Nuevo Pedido')

@section('content')
<div class="max-w-5xl mx-auto" x-data="orderForm()">
    <form method="POST" action="{{ route('pos.orders.store') }}" @submit.prevent="submitOrder()">
        @csrf

        <div class="bg-white rounded-lg shadow p-6 mb-4">
            <h3 class="text-lg font-semibold mb-4">
                <i class="fas fa-clipboard-list text-blue-500 mr-2"></i>Nuevo Pedido de Cliente
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">
                        <i class="fas fa-user mr-1 text-gray-400"></i>Cliente <span class="text-red-500">*</span>
                    </label>
                    <select name="customer_id" x-model="customer_id" required
                            class="w-full border-gray-300 rounded-lg shadow-sm text-sm">
                        <option value="">-- Seleccionar --</option>
                        @foreach($customers as $c)
                        <option value="{{ $c->id }}">{{ $c->name }} {{ $c->phone ? '('.$c->phone.')' : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">
                        <i class="fas fa-calendar mr-1 text-gray-400"></i>Fecha entrega <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="delivery_date" x-model="delivery_date" required
                           min="{{ date('Y-m-d') }}" class="w-full border-gray-300 rounded-lg shadow-sm text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">
                        <i class="fas fa-palette mr-1 text-gray-400"></i>Color etiqueta
                    </label>
                    <select name="label_color" x-model="label_color" class="w-full border-gray-300 rounded-lg shadow-sm text-sm">
                        <option value="rojo">🔴 Rojo</option>
                        <option value="azul">🔵 Azul</option>
                        <option value="verde">🟢 Verde</option>
                        <option value="amarillo">🟡 Amarillo</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">
                        <i class="fas fa-comment mr-1 text-gray-400"></i>Nota del cliente
                    </label>
                    <input type="text" name="customer_notes" x-model="customer_notes"
                           class="w-full border-gray-300 rounded-lg shadow-sm text-sm" placeholder="Indicaciones">
                </div>
            </div>
        </div>

        {{-- Productos --}}
        <div class="bg-white rounded-lg shadow p-6 mb-4">
            <h4 class="font-semibold mb-4"><i class="fas fa-box text-blue-500 mr-2"></i>¿Qué pide el cliente?</h4>

            <div class="grid grid-cols-2 md:grid-cols-6 gap-3 mb-4 p-4 bg-gray-50 rounded-lg">
                <div class="col-span-2">
                    <label class="block text-xs text-gray-500 mb-1">Producto</label>
                    <select x-model="newItem.product_variant_id" @change="onVariantChange()"
                            class="w-full border-gray-300 rounded-lg shadow-sm text-xs">
                        <option value="">-- Producto --</option>
                        @foreach($variants as $v)
                        @php
                            $stock = $stockInfo[$v->id] ?? ['total_remaining' => 0, 'unit' => 'kg', 'lots_count' => 0];
                        @endphp
                        <option value="{{ $v->id }}"
                                data-name="{{ $v->product->name }} — {{ $v->name }}"
                                data-unit="{{ $v->product->unit_type }}"
                                data-price="{{ $v->prices->where('price_type', 'minorista')->first()?->price ?? 0 }}"
                                data-stock="{{ $stock['total_remaining'] }}"
                                data-lots="{{ $stock['lots_count'] }}">
                            {{ $v->product->category->name }} > {{ $v->product->name }} > {{ $v->name }}
                            [{{ number_format($stock['total_remaining'], $stock['unit'] === 'kg' ? 1 : 0) }} {{ $stock['unit'] }} disp.]
                        </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Cantidad</label>
                    <input type="number" step="1" min="1" x-model="newItem.quantity"
                           class="w-full border-gray-300 rounded-lg shadow-sm text-xs" placeholder="Ej: 4">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Tipo empaque</label>
                    <select x-model="newItem.package_type" class="w-full border-gray-300 rounded-lg shadow-sm text-xs">
                        <option value="saco">Saco</option>
                        <option value="caja">Caja</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Precio x <span x-text="newItem.unit"></span></label>
                    <input type="number" step="0.01" min="0" x-model="newItem.unit_price"
                           class="w-full border-gray-300 rounded-lg shadow-sm text-xs" placeholder="S/ 0.00">
                </div>
                <div class="flex items-end">
                    <button type="button" @click="addItem()"
                            :disabled="!newItem.product_variant_id || !newItem.quantity || !newItem.unit_price"
                            class="w-full bg-green-600 text-white px-3 py-2 rounded-lg hover:bg-green-700 text-xs disabled:opacity-50">
                        <i class="fas fa-plus mr-1"></i>Agregar
                    </button>
                </div>
            </div>

            {{-- Alerta de stock --}}
            <div x-show="stockWarning" x-transition class="mb-3 px-4 py-2 rounded-lg text-xs"
                 :class="stockWarning.includes('SIN STOCK') ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-blue-50 text-blue-700'">
                <span x-text="stockWarning"></span>
            </div>

            <template x-if="items.length === 0">
                <div class="text-center py-6 text-gray-400 text-sm">
                    <i class="fas fa-box-open text-2xl mb-2"></i>
                    <p>Agrega lo que el cliente pide.</p>
                </div>
            </template>

            <table x-show="items.length > 0" class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs text-gray-600">Producto</th>
                        <th class="px-4 py-2 text-center text-xs text-gray-600">Cantidad</th>
                        <th class="px-4 py-2 text-center text-xs text-gray-600">Empaque</th>
                        <th class="px-4 py-2 text-center text-xs text-gray-600">Unidad precio</th>
                        <th class="px-4 py-2 text-right text-xs text-gray-600">Precio x und/kg</th>
                        <th class="px-4 py-2 text-center text-xs text-gray-600">Obs.</th>
                        <th class="px-4 py-2 text-center text-xs text-gray-600"></th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <template x-for="(item, idx) in items" :key="idx">
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 text-xs font-medium" x-text="item.name"></td>
                            <td class="px-4 py-2 text-center text-xs font-bold" x-text="item.quantity"></td>
                            <td class="px-4 py-2 text-center text-xs capitalize" x-text="item.package_type"></td>
                            <td class="px-4 py-2 text-center text-xs">
                                <span x-text="item.unit === 'kg' ? 'Por kilo' : 'Por unidad'"
                                      class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded text-[10px]"></span>
                            </td>
                            <td class="px-4 py-2 text-right text-xs font-bold">
                                S/ <span x-text="parseFloat(item.unit_price).toFixed(2)"></span>/<span x-text="item.unit"></span>
                            </td>
                            <td class="px-4 py-2 text-center">
                                <span x-show="item.unit === 'kg'" class="text-[10px] text-orange-500" title="Peso se determinará en almacén">
                                    <i class="fas fa-weight-hanging"></i> Peso por definir
                                </span>
                                <span x-show="item.unit === 'unit'" class="text-[10px] text-green-600">
                                    ≈ S/ <span x-text="(item.quantity * item.unit_price).toFixed(2)"></span>
                                </span>
                            </td>
                            <td class="px-4 py-2 text-center">
                                <button type="button" @click="items.splice(idx, 1)" class="text-red-500 hover:text-red-700">
                                    <i class="fas fa-trash text-xs"></i>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
                <tfoot x-show="estimatedUnitTotal > 0" class="bg-blue-50">
                    <tr>
                        <td colspan="5" class="px-4 py-3 text-right text-xs font-bold text-blue-800">
                            ESTIMADO (solo productos por unidad):
                        </td>
                        <td class="px-4 py-3 text-center font-bold text-blue-800">
                            S/ <span x-text="estimatedUnitTotal.toFixed(2)"></span>
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>

            <div x-show="items.length > 0" class="mt-3 p-3 bg-orange-50 rounded-lg text-xs text-orange-700">
                <i class="fas fa-info-circle mr-1"></i>
                Los productos por <strong>kg</strong> no tienen total hasta que almacén pese los sacos.
                Solo los productos por <strong>unidad</strong> tienen estimado.
            </div>
        </div>

        {{-- Adelanto --}}
        <div class="bg-white rounded-lg shadow p-6 mb-4">
            <h4 class="font-semibold mb-4"><i class="fas fa-money-bill-wave text-green-500 mr-2"></i>Adelanto del Cliente</h4>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Monto (S/)</label>
                    <input type="number" step="0.01" min="0" name="advance_amount" x-model="advance_amount"
                           class="w-full border-gray-300 rounded-lg shadow-sm text-sm" placeholder="0.00">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Método</label>
                    <select name="advance_method" x-model="advance_method"
                            class="w-full border-gray-300 rounded-lg shadow-sm text-sm">
                        <option value="cash">Efectivo</option>
                        <option value="transfer">Transferencia</option>
                        <option value="other">Otro</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Referencia</label>
                    <input type="text" name="advance_reference" x-model="advance_reference"
                           class="w-full border-gray-300 rounded-lg shadow-sm text-sm" placeholder="N° operación">
                </div>
            </div>

            <div x-show="advance_amount > 0" class="mt-3 p-3 bg-green-50 rounded-lg text-sm">
                <p class="text-green-800">
                    <i class="fas fa-check-circle mr-1"></i>
                    Adelanto: <strong>S/ <span x-text="parseFloat(advance_amount || 0).toFixed(2)"></span></strong>
                </p>
            </div>
        </div>

        {{-- Notas internas --}}
        <div class="bg-white rounded-lg shadow p-6 mb-4">
            <label class="block text-xs font-medium text-gray-700 mb-1">
                <i class="fas fa-sticky-note mr-1 text-gray-400"></i>Notas internas
            </label>
            <textarea name="notes" x-model="notes" rows="2"
                      class="w-full border-gray-300 rounded-lg shadow-sm text-sm"
                      placeholder="Notas internas (no visibles para el cliente)"></textarea>
        </div>

        {{-- Botones --}}
        <div class="flex justify-end gap-3">
            <a href="{{ route('pos.orders.index') }}"
               class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                <i class="fas fa-arrow-left"></i> Cancelar
            </a>
            <button type="submit" :disabled="items.length === 0 || !customer_id || !delivery_date || submitting"
                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center gap-2 disabled:opacity-50">
                <i class="fas fa-save"></i>
                <span x-show="!submitting">Crear Pedido e Imprimir Boleta</span>
                <span x-show="submitting">Guardando...</span>
            </button>
        </div>

        {{-- Hidden inputs --}}
        <input type="hidden" name="label_color" :value="label_color">
        <template x-for="(item, idx) in items" :key="'h'+idx">
            <div>
                <input type="hidden" :name="'items['+idx+'][product_variant_id]'" :value="item.product_variant_id">
                <input type="hidden" :name="'items['+idx+'][quantity]'" :value="item.quantity">
                <input type="hidden" :name="'items['+idx+'][unit]'" :value="item.unit">
                <input type="hidden" :name="'items['+idx+'][unit_price]'" :value="item.unit_price">
                <input type="hidden" :name="'items['+idx+'][package_type]'" :value="item.package_type">
            </div>
        </template>
    </form>
</div>

<script>
function orderForm() {
    return {
        customer_id: '', delivery_date: '', customer_notes: '', notes: '', label_color: 'rojo',
        advance_amount: '', advance_method: 'cash', advance_reference: '',
        items: [], submitting: false, stockWarning: '',
        newItem: { product_variant_id: '', quantity: '', unit: 'kg', unit_price: '', name: '', package_type: 'saco' },

        get estimatedUnitTotal() {
            return this.items
                .filter(i => i.unit === 'unit')
                .reduce((sum, i) => sum + (parseFloat(i.quantity) * parseFloat(i.unit_price)), 0);
        },

        onVariantChange() {
            const select = document.querySelector('[x-model="newItem.product_variant_id"]');
            const opt = select.options[select.selectedIndex];
            this.stockWarning = '';
            if (opt.value) {
                this.newItem.unit = opt.dataset.unit || 'kg';
                this.newItem.unit_price = opt.dataset.price || '';
                this.newItem.name = opt.dataset.name || opt.text;
                this.newItem.package_type = this.newItem.unit === 'kg' ? 'saco' : 'caja';

                const stock = parseFloat(opt.dataset.stock || 0);
                const lots = parseInt(opt.dataset.lots || 0);
                if (stock <= 0) {
                    this.stockWarning = '⚠️ SIN STOCK en almacén. El pedido se creará pero almacén deberá abastecerse.';
                } else {
                    this.stockWarning = '📦 Stock almacén: ' + stock.toFixed(this.newItem.unit === 'kg' ? 1 : 0) + ' ' + this.newItem.unit + ' en ' + lots + ' lote(s)';
                }
            }
        },

        addItem() {
            if (!this.newItem.product_variant_id || !this.newItem.quantity || !this.newItem.unit_price) return;
            this.items.push({...this.newItem});
            this.newItem = { product_variant_id: '', quantity: '', unit: 'kg', unit_price: '', name: '', package_type: 'saco' };
            this.stockWarning = '';
        },

        submitOrder() {
            if (this.items.length === 0 || !this.customer_id || !this.delivery_date) return;
            this.submitting = true;
            this.$el.closest('form').submit();
        }
    }
}
</script>
@endsection
