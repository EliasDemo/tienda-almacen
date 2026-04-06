@extends('layouts.app')

@section('title', 'Productos')

@section('content')
<div class="max-w-6xl mx-auto">

    <div class="bg-white rounded-lg shadow p-4 mb-4 flex justify-between items-center">
        <div>
            <h3 class="text-lg font-semibold"><i class="fas fa-drumstick-bite mr-2 text-blue-500"></i>Productos y Variantes</h3>
            <p class="text-xs text-gray-500 mt-1">Gestiona productos, variantes, precios e imágenes.</p>
        </div>
        <a href="{{ route('admin.products.create') }}"
           class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm flex items-center gap-2">
            <i class="fas fa-plus"></i> Nuevo Producto
        </a>
    </div>

    @forelse($products as $product)
    @php
        $image = $product->images->first();
    @endphp
    <div class="bg-white rounded-lg shadow mb-4">
        <div class="p-4 flex justify-between items-center border-b">
            <div class="flex items-center gap-4">
                {{-- Imagen --}}
                <div class="w-14 h-14 bg-gray-100 rounded-lg flex items-center justify-center overflow-hidden flex-shrink-0">
                    @if($image)
                    <img src="{{ asset('storage/' . $image->path) }}" class="w-full h-full object-cover rounded-lg">
                    @else
                    <i class="fas fa-image text-2xl text-gray-300"></i>
                    @endif
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h4 class="font-semibold text-gray-800">{{ $product->name }}</h4>
                        <span class="bg-gray-100 text-gray-600 px-2 py-0.5 rounded text-xs">
                            <i class="fas fa-folder mr-0.5 text-[10px]"></i>{{ $product->category->name }}
                        </span>
                        <span class="bg-blue-100 text-blue-600 px-2 py-0.5 rounded text-xs">
                            <i class="fas fa-ruler mr-0.5 text-[10px]"></i>{{ $product->unit_type }}
                        </span>
                        <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full
                            {{ $product->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            <i class="fas {{ $product->is_active ? 'fa-check-circle' : 'fa-times-circle' }} mr-0.5"></i>
                            {{ $product->is_active ? 'Activo' : 'Inactivo' }}
                        </span>
                    </div>
                    @if($product->description)
                    <p class="text-xs text-gray-500 mt-0.5">{{ $product->description }}</p>
                    @endif
                    <p class="text-xs text-gray-400 mt-0.5">
                        <i class="fas fa-layer-group mr-0.5"></i>{{ $product->variants->count() }} variantes
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.products.edit', $product) }}"
                   class="text-blue-600 hover:text-blue-800 transition-colors" title="Editar">
                    <i class="fas fa-pen-to-square"></i>
                </a>
                <button onclick="toggleProduct({{ $product->id }}, {{ $product->is_active ? 'true' : 'false' }})"
                        class="{{ $product->is_active ? 'text-red-500 hover:text-red-700' : 'text-green-500 hover:text-green-700' }} transition-colors"
                        title="{{ $product->is_active ? 'Desactivar' : 'Activar' }}">
                    <i class="fas {{ $product->is_active ? 'fa-toggle-on' : 'fa-toggle-off' }} text-lg"></i>
                </button>
                <form id="toggle-prod-{{ $product->id }}" method="POST" action="{{ route('admin.products.toggle', $product) }}" class="hidden">
                    @csrf @method('PATCH')
                </form>
            </div>
        </div>

        {{-- Variantes --}}
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-left">
                    <tr>
                        <th class="px-4 py-2 text-gray-600 font-medium text-xs"><i class="fas fa-box mr-1"></i>Variante</th>
                        <th class="px-4 py-2 text-gray-600 font-medium text-xs"><i class="fas fa-barcode mr-1"></i>SKU</th>
                        <th class="px-4 py-2 text-gray-600 font-medium text-xs"><i class="fas fa-qrcode mr-1"></i>Código</th>
                        <th class="px-4 py-2 text-gray-600 font-medium text-xs text-center">Unidad</th>
                        <th class="px-4 py-2 text-gray-600 font-medium text-xs text-center">
                            <i class="fas fa-tag mr-1 text-green-500"></i>Minorista
                        </th>
                        <th class="px-4 py-2 text-gray-600 font-medium text-xs text-center">
                            <i class="fas fa-truck mr-1 text-blue-500"></i>Mayorista
                        </th>
                        <th class="px-4 py-2 text-gray-600 font-medium text-xs text-center">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach($product->variants as $variant)
                    @php
                        $pMin = $variant->prices->where('price_type', 'minorista')->first();
                        $pMay = $variant->prices->where('price_type', 'mayorista')->first();
                    @endphp
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-2.5 font-medium text-gray-800">{{ $variant->name }}</td>
                        <td class="px-4 py-2.5 font-mono text-xs text-gray-500">{{ $variant->sku_code }}</td>
                        <td class="px-4 py-2.5 font-mono text-xs text-gray-500">{{ $variant->barcode ?? '—' }}</td>
                        <td class="px-4 py-2.5 text-center">
                            <span class="bg-gray-100 px-2 py-0.5 rounded text-xs">{{ $variant->sale_unit }}</span>
                        </td>
                        <td class="px-4 py-2.5 text-center font-bold text-green-700">
                            S/ {{ $pMin ? number_format($pMin->price, 2) : '0.00' }}
                        </td>
                        <td class="px-4 py-2.5 text-center font-bold text-blue-700">
                            S/ {{ $pMay ? number_format($pMay->price, 2) : '0.00' }}
                        </td>
                        <td class="px-4 py-2.5 text-center">
                            <span class="inline-flex px-2 py-0.5 text-[10px] rounded-full {{ $variant->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $variant->is_active ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @empty
    <div class="bg-white rounded-lg shadow p-12 text-center">
        <i class="fas fa-box-open text-4xl text-gray-300 mb-3"></i>
        <p class="text-gray-400">No hay productos registrados.</p>
        <a href="{{ route('admin.products.create') }}" class="mt-3 inline-block text-blue-600 hover:underline text-sm">
            <i class="fas fa-plus mr-1"></i>Crear el primero
        </a>
    </div>
    @endforelse
</div>

<script>
function toggleProduct(id, isActive) {
    Swal.fire({
        title: `¿${isActive ? 'Desactivar' : 'Activar'} producto?`,
        text: isActive ? 'No aparecerá en el POS ni en almacén.' : 'Volverá a estar disponible.',
        icon: isActive ? 'warning' : 'question',
        showCancelButton: true,
        confirmButtonColor: isActive ? '#DC2626' : '#16A34A',
        cancelButtonColor: '#6B7280',
        confirmButtonText: `<i class="fas ${isActive ? 'fa-times-circle' : 'fa-check-circle'} mr-1"></i> Sí, ${isActive ? 'desactivar' : 'activar'}`,
        cancelButtonText: 'Cancelar', reverseButtons: true,
    }).then((r) => { if (r.isConfirmed) document.getElementById('toggle-prod-' + id).submit(); });
}
</script>
@endsection