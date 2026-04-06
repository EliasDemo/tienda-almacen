@extends('layouts.app')

@section('title', 'Nueva Entrada de Producto')

@section('content')
<div class="max-w-2xl mx-auto"
     x-data="entryForm()"
>
    <form method="POST" action="{{ route('almacen.entries.store') }}">
        @csrf

        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-semibold mb-4">Registrar Entrada al Almacén</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                {{-- Producto --}}
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Producto *</label>
                    <select name="product_variant_id"
                            x-model="selectedVariant"
                            @change="onVariantChange()"
                            class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            required>
                        <option value="">-- Seleccionar producto --</option>
                        @foreach($variants as $v)
                            <option value="{{ $v['id'] }}"
                                    data-unit="{{ $v['unit_type'] }}"
                                    {{ old('product_variant_id') == $v['id'] ? 'selected' : '' }}>
                                {{ $v['label'] }} ({{ $v['unit_type'] }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Cantidad total --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Cantidad total (<span x-text="unitType"></span>) *
                    </label>
                    <input type="number" step="0.001" min="0.001" name="total_quantity"
                           value="{{ old('total_quantity') }}"
                           class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Ej: 500.000"
                           required>
                </div>

                {{-- Proveedor --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Proveedor</label>
                    <input type="text" name="supplier" value="{{ old('supplier') }}"
                           class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Nombre del proveedor">
                </div>

                {{-- Precio compra --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Precio compra x <span x-text="unitType === 'kg' ? 'kilo' : 'unidad'"></span>
                    </label>
                    <input type="number" step="0.01" min="0"
                           :name="unitType === 'kg' ? 'purchase_price_per_kg' : 'purchase_price_per_unit'"
                           value="{{ old('purchase_price_per_kg', old('purchase_price_per_unit')) }}"
                           class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500"
                           placeholder="0.00">
                </div>

                {{-- Fecha entrada --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de entrada *</label>
                    <input type="date" name="entry_date"
                           value="{{ old('entry_date', date('Y-m-d')) }}"
                           class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500"
                           required>
                </div>

                {{-- Fecha vencimiento --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha vencimiento</label>
                    <input type="date" name="expiry_date"
                           value="{{ old('expiry_date') }}"
                           class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>

                {{-- Notas --}}
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                    <textarea name="notes" rows="2"
                              class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500"
                              placeholder="Notas adicionales...">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>

        {{-- Botones --}}
        <div class="flex justify-end gap-3">
            <a href="{{ route('almacen.entries.index') }}"
               class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                Cancelar
            </a>
            <button type="submit"
                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                Registrar Entrada
            </button>
        </div>
    </form>
</div>

<script>
function entryForm() {
    return {
        selectedVariant: '{{ old("product_variant_id", "") }}',
        unitType: 'kg',

        onVariantChange() {
            const select = document.querySelector('[name=product_variant_id]');
            const option = select.options[select.selectedIndex];
            this.unitType = option.dataset.unit || 'kg';
        },

        init() {
            if (this.selectedVariant) {
                this.onVariantChange();
            }
        }
    }
}
</script>
@endsection