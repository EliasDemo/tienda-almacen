@extends('layouts.app')

@section('title', 'Editar ' . $product->name)

@section('content')
<div class="max-w-4xl mx-auto">

    {{-- Datos del producto --}}
    <form method="POST" action="{{ route('admin.products.update', $product) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-lg shadow p-6 mb-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">
                    <i class="fas fa-pen-to-square text-blue-500 mr-2"></i>Editar: {{ $product->name }}
                </h3>
                <a href="{{ route('admin.products.index') }}" class="text-sm text-blue-600 hover:underline">
                    <i class="fas fa-arrow-left mr-1"></i>Volver
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                {{-- Imagen actual --}}
                <div class="md:row-span-2 flex flex-col items-center" x-data="{ newPreview: null }">
                    @php $image = $product->images->first(); @endphp
                    <div class="w-32 h-32 bg-gray-100 rounded-lg flex items-center justify-center overflow-hidden mb-2">
                        <template x-if="newPreview">
                            <img :src="newPreview" class="w-full h-full object-cover rounded-lg">
                        </template>
                        <template x-if="!newPreview">
                            @if($image)
                            <img src="{{ asset('storage/' . $image->path) }}" class="w-full h-full object-cover rounded-lg">
                            @else
                            <i class="fas fa-image text-3xl text-gray-300"></i>
                            @endif
                        </template>
                    </div>

                    <input type="file" name="image" accept="image/*" @change="newPreview = URL.createObjectURL($event.target.files[0])"
                           class="w-full text-xs file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:bg-blue-50 file:text-blue-700">

                    @if($image)
                    <form method="POST" action="{{ route('admin.products.delete-image', $product) }}" class="mt-1">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-red-500 hover:text-red-700 text-xs" onclick="return confirm('¿Eliminar imagen?')">
                            <i class="fas fa-trash mr-1"></i>Eliminar imagen
                        </button>
                    </form>
                    @endif
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">
                        <i class="fas fa-folder mr-1 text-gray-400"></i>Categoría *
                    </label>
                    <select name="category_id" required class="w-full border-gray-300 rounded-lg shadow-sm text-sm">
                        @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ $product->category_id == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">
                        <i class="fas fa-drumstick-bite mr-1 text-gray-400"></i>Nombre *
                    </label>
                    <input type="text" name="name" value="{{ $product->name }}" required
                           class="w-full border-gray-300 rounded-lg shadow-sm text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">
                        <i class="fas fa-ruler mr-1 text-gray-400"></i>Unidad base *
                    </label>
                    <select name="unit_type" required class="w-full border-gray-300 rounded-lg shadow-sm text-sm">
                        <option value="kg" {{ $product->unit_type === 'kg' ? 'selected' : '' }}>Kilogramos (kg)</option>
                        <option value="unit" {{ $product->unit_type === 'unit' ? 'selected' : '' }}>Unidades</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-700 mb-1">
                        <i class="fas fa-align-left mr-1 text-gray-400"></i>Descripción
                    </label>
                    <input type="text" name="description" value="{{ $product->description }}"
                           class="w-full border-gray-300 rounded-lg shadow-sm text-sm">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 text-sm flex items-center justify-center gap-2">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                </div>
            </div>
        </div>
    </form>

    {{-- Variantes existentes --}}
    <div class="bg-white rounded-lg shadow p-6 mb-4">
        <h3 class="text-lg font-semibold mb-4">
            <i class="fas fa-layer-group text-blue-500 mr-2"></i>Variantes ({{ $product->variants->count() }})
        </h3>

        @foreach($product->variants as $variant)
        @php
            $pMin = $variant->prices->where('price_type', 'minorista')->first();
            $pMay = $variant->prices->where('price_type', 'mayorista')->first();
        @endphp
        <form method="POST" action="{{ route('admin.products.update-variant', $variant) }}"
              class="border rounded-lg p-4 mb-3 hover:border-blue-300 transition-colors">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs text-gray-500 mb-1"><i class="fas fa-tag mr-1 text-gray-400"></i>Nombre</label>
                    <input type="text" name="name" value="{{ $variant->name }}" required
                           class="w-full border-gray-300 rounded-lg shadow-sm text-sm">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1"><i class="fas fa-barcode mr-1 text-gray-400"></i>SKU</label>
                    <input type="text" name="sku_code" value="{{ $variant->sku_code }}" required
                           class="w-full border-gray-300 rounded-lg shadow-sm text-sm">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1"><i class="fas fa-qrcode mr-1 text-gray-400"></i>Código Barras</label>
                    <input type="text" name="barcode" value="{{ $variant->barcode }}"
                           class="w-full border-gray-300 rounded-lg shadow-sm text-sm">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1"><i class="fas fa-ruler mr-1 text-gray-400"></i>Unidad venta</label>
                    <select name="sale_unit" class="w-full border-gray-300 rounded-lg shadow-sm text-sm">
                        <option value="kg" {{ $variant->sale_unit === 'kg' ? 'selected' : '' }}>kg</option>
                        <option value="unit" {{ $variant->sale_unit === 'unit' ? 'selected' : '' }}>unidad</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1"><i class="fas fa-tag text-green-500 mr-1"></i>Minorista (S/)</label>
                    <input type="number" step="0.01" min="0" name="price_minorista"
                           value="{{ $pMin?->price ?? 0 }}" required
                           class="w-full border-gray-300 rounded-lg shadow-sm text-sm">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1"><i class="fas fa-truck text-blue-500 mr-1"></i>Mayorista (S/)</label>
                    <input type="number" step="0.01" min="0" name="price_mayorista"
                           value="{{ $pMay?->price ?? 0 }}" required
                           class="w-full border-gray-300 rounded-lg shadow-sm text-sm">
                </div>
            </div>

            <div class="mt-3 flex justify-end">
                <button type="submit" class="bg-green-600 text-white px-4 py-1.5 rounded-lg hover:bg-green-700 text-sm flex items-center gap-1">
                    <i class="fas fa-save"></i> Guardar
                </button>
            </div>
        </form>
        @endforeach
    </div>

    {{-- Agregar nueva variante --}}
    <div class="bg-white rounded-lg shadow p-6" x-data="{ show: false }">
        <div class="flex justify-between items-center">
            <h3 class="text-lg font-semibold">
                <i class="fas fa-plus-circle text-green-500 mr-2"></i>Agregar Variante
            </h3>
            <button @click="show = !show" class="text-sm flex items-center gap-1"
                    :class="show ? 'text-red-600 hover:text-red-800' : 'text-blue-600 hover:text-blue-800'">
                <i :class="show ? 'fas fa-times' : 'fas fa-plus'"></i>
                <span x-text="show ? 'Cancelar' : 'Agregar'"></span>
            </button>
        </div>

        <form x-show="show" x-transition method="POST" action="{{ route('admin.products.add-variant', $product) }}" class="mt-4">
            @csrf
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs text-gray-500 mb-1"><i class="fas fa-tag mr-1 text-gray-400"></i>Nombre *</label>
                    <input type="text" name="name" required class="w-full border-gray-300 rounded-lg shadow-sm text-sm">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1"><i class="fas fa-barcode mr-1 text-gray-400"></i>SKU *</label>
                    <input type="text" name="sku_code" required class="w-full border-gray-300 rounded-lg shadow-sm text-sm">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1"><i class="fas fa-qrcode mr-1 text-gray-400"></i>Código Barras</label>
                    <input type="text" name="barcode" class="w-full border-gray-300 rounded-lg shadow-sm text-sm">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1"><i class="fas fa-ruler mr-1 text-gray-400"></i>Unidad venta *</label>
                    <select name="sale_unit" class="w-full border-gray-300 rounded-lg shadow-sm text-sm">
                        <option value="kg">kg</option>
                        <option value="unit">unidad</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1"><i class="fas fa-tag text-green-500 mr-1"></i>Minorista *</label>
                    <input type="number" step="0.01" min="0" name="price_minorista" required
                           class="w-full border-gray-300 rounded-lg shadow-sm text-sm">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1"><i class="fas fa-truck text-blue-500 mr-1"></i>Mayorista *</label>
                    <input type="number" step="0.01" min="0" name="price_mayorista" required
                           class="w-full border-gray-300 rounded-lg shadow-sm text-sm">
                </div>
            </div>
            <div class="mt-3 flex justify-end">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm flex items-center gap-1">
                    <i class="fas fa-plus"></i> Agregar Variante
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
