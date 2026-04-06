@extends('layouts.app')

@section('title', 'Nuevo Producto')

@section('content')
<div class="max-w-4xl mx-auto" x-data="productForm()">
    <form method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data">
        @csrf

        <div class="bg-white rounded-lg shadow p-6 mb-4">
            <h3 class="text-lg font-semibold mb-4">
                <i class="fas fa-plus-circle text-blue-500 mr-2"></i>Nuevo Producto
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">
                        <i class="fas fa-folder mr-1 text-gray-400"></i>Categoría <span class="text-red-500">*</span>
                    </label>
                    <select name="category_id" required class="w-full border-gray-300 rounded-lg shadow-sm text-sm">
                        <option value="">-- Seleccionar --</option>
                        @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">
                        <i class="fas fa-drumstick-bite mr-1 text-gray-400"></i>Nombre <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           class="w-full border-gray-300 rounded-lg shadow-sm text-sm" placeholder="Ej: Pollo">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">
                        <i class="fas fa-ruler mr-1 text-gray-400"></i>Unidad base <span class="text-red-500">*</span>
                    </label>
                    <select name="unit_type" required class="w-full border-gray-300 rounded-lg shadow-sm text-sm">
                        <option value="kg">Kilogramos (kg)</option>
                        <option value="unit">Unidades</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-700 mb-1">
                        <i class="fas fa-align-left mr-1 text-gray-400"></i>Descripción
                    </label>
                    <input type="text" name="description" value="{{ old('description') }}"
                           class="w-full border-gray-300 rounded-lg shadow-sm text-sm" placeholder="Opcional">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">
                        <i class="fas fa-camera mr-1 text-gray-400"></i>Imagen
                    </label>
                    <div class="relative">
                        <input type="file" name="image" accept="image/*" @change="previewImage($event)"
                               class="w-full border-gray-300 rounded-lg shadow-sm text-xs file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    </div>
                    <div x-show="imagePreview" class="mt-2">
                        <img :src="imagePreview" class="w-20 h-20 object-cover rounded-lg border">
                    </div>
                </div>
            </div>
        </div>

        {{-- Variantes --}}
        <div class="bg-white rounded-lg shadow p-6 mb-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">
                    <i class="fas fa-layer-group text-blue-500 mr-2"></i>Variantes
                </h3>
                <button type="button" @click="addVariant()"
                        class="bg-green-600 text-white px-3 py-1.5 rounded-lg hover:bg-green-700 text-sm flex items-center gap-1">
                    <i class="fas fa-plus"></i> Agregar
                </button>
            </div>

            <template x-for="(v, idx) in variants" :key="idx">
                <div class="border rounded-lg p-4 mb-3 hover:border-blue-300 transition-colors">
                    <div class="flex justify-between items-center mb-3">
                        <span class="text-sm font-medium text-gray-600">
                            <i class="fas fa-box text-gray-400 mr-1"></i>Variante #<span x-text="idx + 1"></span>
                        </span>
                        <button type="button" @click="removeVariant(idx)" x-show="variants.length > 1"
                                class="text-red-500 hover:text-red-700 text-xs flex items-center gap-1">
                            <i class="fas fa-trash"></i> Quitar
                        </button>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1"><i class="fas fa-tag mr-1 text-gray-400"></i>Nombre *</label>
                            <input type="text" :name="'variants[' + idx + '][name]'" x-model="v.name" required
                                   class="w-full border-gray-300 rounded-lg shadow-sm text-sm" placeholder="Ej: Entero">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1"><i class="fas fa-barcode mr-1 text-gray-400"></i>SKU *</label>
                            <input type="text" :name="'variants[' + idx + '][sku_code]'" x-model="v.sku_code" required
                                   class="w-full border-gray-300 rounded-lg shadow-sm text-sm" placeholder="AVE-POLL-ENT">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1"><i class="fas fa-qrcode mr-1 text-gray-400"></i>Código Barras</label>
                            <input type="text" :name="'variants[' + idx + '][barcode]'" x-model="v.barcode"
                                   class="w-full border-gray-300 rounded-lg shadow-sm text-sm" placeholder="Opcional">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1"><i class="fas fa-ruler mr-1 text-gray-400"></i>Unidad venta *</label>
                            <select :name="'variants[' + idx + '][sale_unit]'" x-model="v.sale_unit"
                                    class="w-full border-gray-300 rounded-lg shadow-sm text-sm">
                                <option value="kg">kg</option>
                                <option value="unit">unidad</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1"><i class="fas fa-tag text-green-500 mr-1"></i>Precio Minorista *</label>
                            <input type="number" step="0.01" min="0"
                                   :name="'variants[' + idx + '][price_minorista]'" x-model="v.price_minorista" required
                                   class="w-full border-gray-300 rounded-lg shadow-sm text-sm" placeholder="0.00">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1"><i class="fas fa-truck text-blue-500 mr-1"></i>Precio Mayorista *</label>
                            <input type="number" step="0.01" min="0"
                                   :name="'variants[' + idx + '][price_mayorista]'" x-model="v.price_mayorista" required
                                   class="w-full border-gray-300 rounded-lg shadow-sm text-sm" placeholder="0.00">
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.products.index') }}"
               class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                <i class="fas fa-arrow-left"></i> Cancelar
            </a>
            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center gap-2">
                <i class="fas fa-save"></i> Crear Producto
            </button>
        </div>
    </form>
</div>

<script>
function productForm() {
    return {
        variants: [{ name: '', sku_code: '', barcode: '', sale_unit: 'kg', price_minorista: '', price_mayorista: '' }],
        imagePreview: null,
        addVariant() {
            this.variants.push({ name: '', sku_code: '', barcode: '', sale_unit: 'kg', price_minorista: '', price_mayorista: '' });
        },
        removeVariant(idx) {
            if (this.variants.length > 1) this.variants.splice(idx, 1);
        },
        previewImage(e) {
            const file = e.target.files[0];
            if (file) {
                this.imagePreview = URL.createObjectURL(file);
            }
        },
    }
}
</script>
@endsection
