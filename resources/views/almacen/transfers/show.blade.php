@extends('layouts.app')

@section('title', 'Cargamento ' . $transfer->transfer_code)

@section('content')
<div x-data="workStation()" class="max-w-6xl mx-auto">

    {{-- Cabecera --}}
    <div class="bg-white rounded-lg shadow p-4 mb-4 flex justify-between items-center">
        <div>
            <h3 class="text-lg font-semibold">{{ $transfer->transfer_code }}</h3>
            <p class="text-xs text-gray-500">{{ $transfer->dispatcher->name }} — {{ $transfer->created_at->format('d/m/Y H:i') }}</p>

            @if($transfer->stockRequestOrder)
            @php $linkedOrder = $transfer->stockRequestOrder; @endphp
            <div class="mt-1 flex items-center gap-2 flex-wrap">
                <span class="bg-orange-100 text-orange-800 px-2 py-0.5 rounded-full text-xs font-medium">
                    <i class="fas fa-clipboard-list mr-1"></i>Pedido vinculado: {{ $linkedOrder->request_code }}
                </span>
                <span class="text-xs text-gray-600">Cliente: <strong>{{ $linkedOrder->customer->name ?? '—' }}</strong></span>
                @php $lc = ['rojo'=>'bg-red-500','azul'=>'bg-blue-500','verde'=>'bg-green-500','amarillo'=>'bg-yellow-400']; @endphp
                <span class="w-3 h-3 rounded-full {{ $lc[$linkedOrder->label_color] ?? 'bg-gray-400' }}"></span>
                <span class="text-xs text-gray-500">Entrega: {{ $linkedOrder->delivery_date?->format('d/m/Y') }}</span>
            </div>
            @endif
        </div>
        <div class="flex gap-2 items-center">
            <span class="inline-flex px-3 py-1 text-sm font-medium rounded-full
                {{ $transfer->status === 'preparing' ? 'bg-yellow-100 text-yellow-800' : '' }}
                {{ $transfer->status === 'in_transit' ? 'bg-blue-100 text-blue-800' : '' }}
                {{ $transfer->status === 'received' ? 'bg-green-100 text-green-800' : '' }}
            ">
                {{ $transfer->status === 'preparing' ? 'Preparando' : '' }}
                {{ $transfer->status === 'in_transit' ? 'En camino' : '' }}
                {{ $transfer->status === 'received' ? 'Recibido' : '' }}
            </span>
            <div class="flex gap-3 items-center">
                @if($transfer->status === 'preparing' && $transfer->lines->count() === 0)
                <form method="POST" action="{{ route('almacen.transfers.destroy', $transfer) }}"
                    onsubmit="return confirm('¿Eliminar este cargamento vacío?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-red-500 hover:underline text-sm">Eliminar</button>
                </form>
                @endif
                <a href="{{ route('almacen.transfers.index') }}" class="text-sm text-blue-600 hover:underline">← Volver</a>
            </div>
        </div>
    </div>

    {{-- Panel de progreso del pedido (solo si tiene pedido) --}}
    @if($transfer->stockRequestOrder)
    @php $linkedOrder = $transfer->stockRequestOrder; @endphp
    <div class="bg-orange-50 border border-orange-200 rounded-lg shadow p-4 mb-4">
        <h4 class="text-sm font-semibold text-orange-800 mb-2">
            <i class="fas fa-clipboard-list mr-1"></i>Progreso del Pedido {{ $linkedOrder->request_code }}
        </h4>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
            @foreach($linkedOrder->items as $orderItem)
            @php $maxPkgs = (int) $orderItem->quantity_requested; @endphp
            <div class="bg-white rounded px-3 py-2 text-xs flex justify-between items-center"
                 :class="orderCountForVariant({{ $orderItem->product_variant_id }}) >= {{ $maxPkgs }} ? 'ring-2 ring-green-300' : ''">
                <span class="font-medium">{{ $orderItem->variant->product->name ?? '' }} — {{ $orderItem->variant->name ?? '' }}</span>
                <div class="flex items-center gap-2">
                    <span class="font-bold"
                          :class="orderCountForVariant({{ $orderItem->product_variant_id }}) >= {{ $maxPkgs }} ? 'text-green-700' : 'text-orange-700'">
                        <span x-text="orderCountForVariant({{ $orderItem->product_variant_id }})"></span> / {{ $maxPkgs }}
                    </span>
                    <template x-if="orderCountForVariant({{ $orderItem->product_variant_id }}) >= {{ $maxPkgs }}">
                        <i class="fas fa-check-circle text-green-500"></i>
                    </template>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    @if($transfer->status === 'preparing')
    {{-- PASO 1: Configurar producto --}}
    <div x-show="!productLocked" class="bg-white rounded-lg shadow p-4 mb-4">
        <h4 class="text-sm font-semibold mb-3 text-gray-700">Configurar Producto</h4>

        <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
            <div>
                <label class="block text-xs text-gray-500 mb-1">Producto *</label>
                <select x-model="config.product_variant_id" @change="onProductChange()"
                        class="w-full border-gray-300 rounded-lg shadow-sm text-sm">
                    <option value="">-- Producto --</option>
                    <template x-for="v in variants" :key="v.id">
                        <option :value="v.id" x-text="v.label"></option>
                    </template>
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Lote *</label>
                <select x-model="config.lot_id" class="w-full border-gray-300 rounded-lg shadow-sm text-sm">
                    <option value="">-- Lote --</option>
                    <template x-for="lot in filteredLots" :key="lot.id">
                        <option :value="lot.id" x-text="lot.label"></option>
                    </template>
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Tipo</label>
                <select x-model="config.package_type" class="w-full border-gray-300 rounded-lg shadow-sm text-sm">
                    <option value="saco">Saco</option>
                    <option value="caja">Caja</option>
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Merma (kg)</label>
                <input type="number" step="0.001" min="0" x-model="config.merma_kg"
                       class="w-full border-gray-300 rounded-lg shadow-sm text-sm" placeholder="0.000">
            </div>
            <div class="flex items-end">
                <button @click="lockProduct()"
                        :disabled="!config.product_variant_id || !config.lot_id"
                        class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm disabled:opacity-50">
                    Confirmar Producto
                </button>
            </div>
        </div>

        {{-- Aviso: este producto está en el pedido --}}
        <div x-show="canBeForOrder" class="mt-3 p-2 bg-orange-50 border border-orange-200 rounded text-xs text-orange-700">
            <i class="fas fa-clipboard-list mr-1"></i>
            Este producto está en el pedido. Al confirmar podrás elegir si cada saco es para el pedido o para tienda.
        </div>

        <div x-show="existingLineInfo" class="mt-3 p-2 bg-blue-50 rounded text-xs text-blue-700">
            <span x-text="existingLineInfo"></span>
        </div>
    </div>

    {{-- PASO 2: Modo rápido --}}
    <div x-show="productLocked" class="bg-green-50 border-2 border-green-300 rounded-lg shadow p-4 mb-4">

        <div class="flex justify-between items-center mb-3">
            <div class="flex items-center flex-wrap gap-x-2 gap-y-1">
                <span class="text-sm font-bold text-green-800" x-text="currentProductLabel"></span>
                <span class="text-xs text-gray-500">|</span>
                <span class="text-xs text-gray-600">Lote: <span x-text="currentLotLabel" class="font-medium"></span></span>
                <span class="text-xs text-gray-500">|</span>
                <span class="text-xs text-gray-600">Tipo: <span x-text="config.package_type" class="font-medium capitalize"></span></span>
                <span class="text-xs text-gray-500">|</span>
                <span class="text-xs text-red-600">Merma: <span x-text="config.merma_kg"></span> kg</span>
            </div>
            <div class="flex gap-3">
                <button @click="showLotSelector = !showLotSelector" class="text-xs text-blue-600 hover:underline">Cambiar lote</button>
                <button @click="addAnotherProduct()" class="text-xs text-purple-600 hover:underline font-medium">+ Otro producto</button>
            </div>
        </div>

        {{-- Cambiar lote --}}
        <div x-show="showLotSelector" class="mb-3 p-3 bg-white rounded-lg border">
            <div class="flex gap-2">
                <select x-model="config.lot_id" class="flex-1 border-gray-300 rounded-lg shadow-sm text-sm">
                    <template x-for="lot in filteredLots" :key="lot.id">
                        <option :value="lot.id" x-text="lot.label"></option>
                    </template>
                </select>
                <button @click="showLotSelector = false; $refs.weightInput.focus()"
                        class="bg-gray-600 text-white px-4 py-2 rounded-lg text-sm">OK</button>
            </div>
        </div>

        {{-- Checkbox: ¿Es para el pedido? (solo si el producto está en el pedido) --}}
        <div x-show="canBeForOrder" class="mb-3 p-3 rounded-lg"
             :class="forOrder ? 'bg-orange-100 border border-orange-300' : 'bg-gray-100'">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" x-model="forOrder" class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                <span class="text-sm font-medium" :class="forOrder ? 'text-orange-800' : 'text-gray-600'">
                    <i class="fas fa-clipboard-list mr-1"></i>
                    Este saco es para el <strong>pedido</strong> de <span x-text="orderCustomerName"></span>
                </span>
                <template x-if="forOrder && currentOrderLimit">
                    <span class="text-xs font-bold ml-2" :class="orderItemCount >= currentOrderLimit.max_packages ? 'text-red-600' : 'text-orange-600'">
                        (<span x-text="orderItemCount + ' / ' + currentOrderLimit.max_packages"></span> bultos)
                    </span>
                </template>
            </label>
        </div>

        {{-- Campo de peso + botón --}}
        <div class="flex gap-3 items-end">
            <div class="flex-1 max-w-sm">
                <label class="block text-xs text-green-700 font-medium mb-1">
                    Peso / Cantidad (<span x-text="currentUnit"></span>)
                </label>
                <input type="number" step="0.001" min="0.001" x-model="weight"
                       @keydown.enter.prevent="addPackage()"
                       class="w-full border-green-400 rounded-lg shadow-sm text-2xl font-bold text-center focus:ring-green-500 focus:border-green-500 h-14"
                       placeholder="0.000"
                       x-ref="weightInput">
            </div>

            <button @click="addPackage()"
                    :disabled="loading || !weight || (forOrder && isOrderAtLimit)"
                    class="bg-green-600 text-white px-8 h-14 rounded-lg hover:bg-green-700 text-sm font-bold disabled:opacity-50 whitespace-nowrap">
                <span x-show="!loading && !(forOrder && isOrderAtLimit)">AGREGAR E IMPRIMIR</span>
                <span x-show="loading">...</span>
                <span x-show="forOrder && isOrderAtLimit && !loading" class="text-yellow-200">PEDIDO COMPLETO ✓</span>
            </button>

            <div class="text-center px-6 bg-white rounded-lg py-2 min-w-[100px]">
                <p class="text-xs text-gray-500">Bultos</p>
                <p class="text-3xl font-bold text-green-800" x-text="sessionCount"></p>
            </div>

            <div class="text-center px-6 bg-white rounded-lg py-2 min-w-[120px]">
                <p class="text-xs text-gray-500">Total <span x-text="currentUnit"></span></p>
                <p class="text-xl font-bold text-blue-800" x-text="sessionTotal.toFixed(3)"></p>
            </div>
        </div>

        {{-- Aviso pedido completo --}}
        <div x-show="forOrder && isOrderAtLimit" x-transition class="mt-3 px-3 py-2 rounded text-sm bg-orange-100 text-orange-700">
            <i class="fas fa-check-circle mr-1"></i>
            Se completaron los bultos del pedido para este producto. Puedes seguir agregando para <strong>tienda</strong> desmarcando el checkbox.
        </div>

        {{-- Mensaje --}}
        <div x-show="message" x-transition
             :class="messageType === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
             class="mt-3 px-3 py-2 rounded text-sm">
            <span x-text="message"></span>
        </div>
    </div>
    @endif

    {{-- ═══ TABLA DE BULTOS ═══ --}}

    {{-- Sección PEDIDO --}}
    <template x-if="orderPackagesGrouped.length > 0">
        <div class="mb-4">
            <div class="flex items-center gap-2 mb-2">
                <span class="bg-orange-200 text-orange-800 px-3 py-1 rounded-lg text-xs font-bold">
                    <i class="fas fa-clipboard-list mr-1"></i>PEDIDO
                </span>
                <span class="text-xs text-gray-500" x-text="orderCustomerName"></span>
            </div>

            <template x-for="(group, gIdx) in orderPackagesGrouped" :key="'o-'+gIdx">
                <div class="bg-white rounded-lg shadow mb-3 border-l-4 border-l-orange-400">
                    <div class="p-4 border-b flex justify-between items-center">
                        <div>
                            <h4 class="font-semibold" x-text="group.productName"></h4>
                            <p class="text-xs text-gray-500">
                                <span x-text="group.packages.length"></span> bultos —
                                <span x-text="group.totalQty.toFixed(3)"></span> <span x-text="group.unit"></span> —
                                <span class="text-red-600">Merma: <span x-text="group.merma.toFixed(3)"></span> kg</span>
                            </p>
                        </div>
                        @if($transfer->status === 'preparing')
                        <div class="flex items-center gap-1">
                            <label class="text-xs text-gray-500">Merma:</label>
                            <input type="number" step="0.001" min="0" :value="group.merma"
                                   @change="saveMermaForLine(group.lineId, $event.target.value)"
                                   class="w-24 border-gray-300 rounded text-xs py-1">
                            <span class="text-xs text-gray-400">kg</span>
                        </div>
                        @endif
                    </div>
                    <table class="w-full text-sm">
                        <thead class="bg-orange-50 text-left">
                            <tr>
                                <th class="px-4 py-2 text-orange-700 font-medium text-xs">#</th>
                                <th class="px-4 py-2 text-orange-700 font-medium text-xs">UUID</th>
                                <th class="px-4 py-2 text-orange-700 font-medium text-xs">Lote</th>
                                <th class="px-4 py-2 text-orange-700 font-medium text-xs">Tipo</th>
                                <th class="px-4 py-2 text-orange-700 font-medium text-xs">Peso</th>
                                <th class="px-4 py-2 text-orange-700 font-medium text-xs">Etiqueta</th>
                                @if($transfer->status === 'preparing')
                                <th class="px-4 py-2 text-orange-700 font-medium text-xs">Quitar</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <template x-for="(pkg, pIdx) in group.packages" :key="pkg.id">
                                <tr class="hover:bg-orange-50/50">
                                    <td class="px-4 py-2 text-xs" x-text="pIdx + 1"></td>
                                    <td class="px-4 py-2 font-mono text-xs" x-text="pkg.uuid.substring(0, 12) + '...'"></td>
                                    <td class="px-4 py-2 text-xs" x-text="pkg.lot_code"></td>
                                    <td class="px-4 py-2 text-xs capitalize" x-text="pkg.package_type"></td>
                                    <td class="px-4 py-2 text-xs font-medium" x-text="pkg.quantity + ' ' + group.unit"></td>
                                    <td class="px-4 py-2">
                                        <a :href="pkg.label_url" target="_blank" class="text-green-600 hover:underline text-xs">Imprimir QR</a>
                                    </td>
                                    @if($transfer->status === 'preparing')
                                    <td class="px-4 py-2">
                                        <button @click="removePackage(pkg.id, group.lineIdx)" class="text-red-500 hover:text-red-700 text-xs">Quitar</button>
                                    </td>
                                    @endif
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </template>
        </div>
    </template>

    {{-- Sección TIENDA --}}
    <template x-if="storePackagesGrouped.length > 0">
        <div class="mb-4">
            <div class="flex items-center gap-2 mb-2">
                <span class="bg-blue-200 text-blue-800 px-3 py-1 rounded-lg text-xs font-bold">
                    <i class="fas fa-store mr-1"></i>TIENDA
                </span>
            </div>

            <template x-for="(group, gIdx) in storePackagesGrouped" :key="'s-'+gIdx">
                <div class="bg-white rounded-lg shadow mb-3 border-l-4 border-l-blue-400">
                    <div class="p-4 border-b flex justify-between items-center">
                        <div>
                            <h4 class="font-semibold" x-text="group.productName"></h4>
                            <p class="text-xs text-gray-500">
                                <span x-text="group.packages.length"></span> bultos —
                                <span x-text="group.totalQty.toFixed(3)"></span> <span x-text="group.unit"></span> —
                                <span class="text-red-600">Merma: <span x-text="group.merma.toFixed(3)"></span> kg</span>
                            </p>
                        </div>
                        @if($transfer->status === 'preparing')
                        <div class="flex items-center gap-1">
                            <label class="text-xs text-gray-500">Merma:</label>
                            <input type="number" step="0.001" min="0" :value="group.merma"
                                   @change="saveMermaForLine(group.lineId, $event.target.value)"
                                   class="w-24 border-gray-300 rounded text-xs py-1">
                            <span class="text-xs text-gray-400">kg</span>
                        </div>
                        @endif
                    </div>
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-left">
                            <tr>
                                <th class="px-4 py-2 text-gray-600 font-medium text-xs">#</th>
                                <th class="px-4 py-2 text-gray-600 font-medium text-xs">UUID</th>
                                <th class="px-4 py-2 text-gray-600 font-medium text-xs">Lote</th>
                                <th class="px-4 py-2 text-gray-600 font-medium text-xs">Tipo</th>
                                <th class="px-4 py-2 text-gray-600 font-medium text-xs">Peso</th>
                                <th class="px-4 py-2 text-gray-600 font-medium text-xs">Etiqueta</th>
                                @if($transfer->status === 'preparing')
                                <th class="px-4 py-2 text-gray-600 font-medium text-xs">Quitar</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <template x-for="(pkg, pIdx) in group.packages" :key="pkg.id">
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-2 text-xs" x-text="pIdx + 1"></td>
                                    <td class="px-4 py-2 font-mono text-xs" x-text="pkg.uuid.substring(0, 12) + '...'"></td>
                                    <td class="px-4 py-2 text-xs" x-text="pkg.lot_code"></td>
                                    <td class="px-4 py-2 text-xs capitalize" x-text="pkg.package_type"></td>
                                    <td class="px-4 py-2 text-xs font-medium" x-text="pkg.quantity + ' ' + group.unit"></td>
                                    <td class="px-4 py-2">
                                        <a :href="pkg.label_url" target="_blank" class="text-green-600 hover:underline text-xs">Imprimir QR</a>
                                    </td>
                                    @if($transfer->status === 'preparing')
                                    <td class="px-4 py-2">
                                        <button @click="removePackage(pkg.id, group.lineIdx)" class="text-red-500 hover:text-red-700 text-xs">Quitar</button>
                                    </td>
                                    @endif
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </template>
        </div>
    </template>

    {{-- Sin pedido: tabla única normal --}}
    <template x-if="!hasOrderLimits && allPackagesGrouped.length > 0">
        <div>
            <template x-for="(group, gIdx) in allPackagesGrouped" :key="'n-'+gIdx">
                <div class="bg-white rounded-lg shadow mb-4">
                    <div class="p-4 border-b flex justify-between items-center">
                        <div>
                            <h4 class="font-semibold" x-text="group.productName"></h4>
                            <p class="text-xs text-gray-500">
                                <span x-text="group.packages.length"></span> bultos —
                                <span x-text="group.totalQty.toFixed(3)"></span> <span x-text="group.unit"></span> —
                                <span class="text-red-600">Merma: <span x-text="group.merma.toFixed(3)"></span> kg</span>
                            </p>
                        </div>
                        @if($transfer->status === 'preparing')
                        <div class="flex items-center gap-1">
                            <label class="text-xs text-gray-500">Merma:</label>
                            <input type="number" step="0.001" min="0" :value="group.merma"
                                   @change="saveMermaForLine(group.lineId, $event.target.value)"
                                   class="w-24 border-gray-300 rounded text-xs py-1">
                            <span class="text-xs text-gray-400">kg</span>
                        </div>
                        @endif
                    </div>
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-left">
                            <tr>
                                <th class="px-4 py-2 text-gray-600 font-medium text-xs">#</th>
                                <th class="px-4 py-2 text-gray-600 font-medium text-xs">UUID</th>
                                <th class="px-4 py-2 text-gray-600 font-medium text-xs">Lote</th>
                                <th class="px-4 py-2 text-gray-600 font-medium text-xs">Tipo</th>
                                <th class="px-4 py-2 text-gray-600 font-medium text-xs">Peso</th>
                                <th class="px-4 py-2 text-gray-600 font-medium text-xs">Etiqueta</th>
                                @if($transfer->status === 'preparing')
                                <th class="px-4 py-2 text-gray-600 font-medium text-xs">Quitar</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <template x-for="(pkg, pIdx) in group.packages" :key="pkg.id">
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-2 text-xs" x-text="pIdx + 1"></td>
                                    <td class="px-4 py-2 font-mono text-xs" x-text="pkg.uuid.substring(0, 12) + '...'"></td>
                                    <td class="px-4 py-2 text-xs" x-text="pkg.lot_code"></td>
                                    <td class="px-4 py-2 text-xs capitalize" x-text="pkg.package_type"></td>
                                    <td class="px-4 py-2 text-xs font-medium" x-text="pkg.quantity + ' ' + group.unit"></td>
                                    <td class="px-4 py-2">
                                        <a :href="pkg.label_url" target="_blank" class="text-green-600 hover:underline text-xs">Imprimir QR</a>
                                    </td>
                                    @if($transfer->status === 'preparing')
                                    <td class="px-4 py-2">
                                        <button @click="removePackage(pkg.id, group.lineIdx)" class="text-red-500 hover:text-red-700 text-xs">Quitar</button>
                                    </td>
                                    @endif
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </template>
        </div>
    </template>

    <div x-show="allPackagesGrouped.length === 0 && !productLocked" class="bg-white rounded-lg shadow p-8 text-center text-gray-400 mb-4">
        Cargamento vacío. Configura un producto arriba para empezar.
    </div>

    {{-- Botón despachar --}}
    @if($transfer->status === 'preparing')
    <div x-show="allPackagesGrouped.length > 0"
         class="bg-white rounded-lg shadow p-4 flex justify-between items-center mt-4">
        <p class="text-sm text-gray-600">
            Total: <span class="font-bold" x-text="grandTotalPackages"></span> bultos
            <template x-if="hasOrderLimits">
                <span>
                    (<span class="text-orange-600 font-medium" x-text="orderPackagesGrouped.reduce((s,g) => s + g.packages.length, 0)"></span> pedido +
                     <span class="text-blue-600 font-medium" x-text="storePackagesGrouped.reduce((s,g) => s + g.packages.length, 0)"></span> tienda)
                </span>
            </template>
        </p>
        <form method="POST" action="{{ route('almacen.transfers.dispatch', $transfer) }}">
            @csrf
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700"
                    onclick="return confirm('¿Despachar cargamento?')">
                Despachar Cargamento
            </button>
        </form>
    </div>
    @endif

    @if($transfer->status === 'in_transit')
    <div class="bg-blue-50 rounded-lg shadow p-4 text-center">
        <p class="text-sm text-blue-800 font-medium">
            En camino — Despachado: {{ $transfer->dispatched_at->format('d/m/Y H:i') }}
        </p>
    </div>
    @endif
</div>

<script>
function workStation() {
    const existingLines = @json($existingLines);
    const orderLimits = @json($orderLimits ?? []);
    const orderVariantIds = Object.keys(orderLimits).map(Number);
    const orderCustomer = @json($transfer->stockRequestOrder?->customer?->name ?? '');

    return {
        variants: @json($variants),
        lots: @json($lots),
        config: { product_variant_id: '', lot_id: '', package_type: 'saco', merma_kg: '0' },
        weight: '',
        productLocked: false,
        forOrder: false,
        showLotSelector: false,
        loading: false,
        message: '',
        messageType: 'success',
        sessionCount: 0,
        sessionTotal: 0,
        existingLineInfo: '',
        lines: JSON.parse(JSON.stringify(existingLines)),

        get hasOrderLimits() { return orderVariantIds.length > 0; },
        get orderCustomerName() { return orderCustomer; },

        get canBeForOrder() {
            return orderVariantIds.includes(parseInt(this.config.product_variant_id));
        },

        get filteredLots() {
            if (!this.config.product_variant_id) return [];
            return this.lots.filter(l => l.product_variant_id == this.config.product_variant_id && l.remaining > 0);
        },

        get currentUnit() {
            const v = this.variants.find(x => x.id == this.config.product_variant_id);
            return v ? v.unit_type : 'kg';
        },

        get currentProductLabel() {
            const v = this.variants.find(x => x.id == this.config.product_variant_id);
            return v ? v.label : '';
        },

        get currentLotLabel() {
            const lot = this.lots.find(l => l.id == this.config.lot_id);
            return lot ? lot.label : '';
        },

        get currentOrderLimit() {
            return orderLimits[parseInt(this.config.product_variant_id)] || null;
        },

        // Contar SOLO paquetes marcados forOrder para este producto
        get orderItemCount() {
            const variantId = parseInt(this.config.product_variant_id);
            const line = this.lines.find(l => l.product_variant_id == variantId);
            if (!line) return 0;
            return line.packages.filter(p => p.forOrder === true).length;
        },

        get isOrderAtLimit() {
            if (!this.currentOrderLimit) return false;
            return this.orderItemCount >= this.currentOrderLimit.max_packages;
        },

        // Contar forOrder por variante (para el panel de progreso)
        orderCountForVariant(variantId) {
            const line = this.lines.find(l => l.product_variant_id == variantId);
            if (!line) return 0;
            return line.packages.filter(p => p.forOrder === true).length;
        },

        get allPackagesGrouped() {
            return this.lines.filter(l => l.packages.length > 0).map((l, idx) => ({
                ...l,
                lineIdx: idx,
                totalQty: l.packages.reduce((sum, p) => sum + parseFloat(p.quantity), 0),
            }));
        },

        get orderPackagesGrouped() {
            if (!this.hasOrderLimits) return [];
            return this.allPackagesGrouped.map(g => ({
                ...g,
                packages: g.packages.filter(p => p.forOrder === true),
                totalQty: g.packages.filter(p => p.forOrder === true).reduce((sum, p) => sum + parseFloat(p.quantity), 0),
            })).filter(g => g.packages.length > 0);
        },

        get storePackagesGrouped() {
            if (!this.hasOrderLimits) return [];
            return this.allPackagesGrouped.map(g => ({
                ...g,
                packages: g.packages.filter(p => !p.forOrder),
                totalQty: g.packages.filter(p => !p.forOrder).reduce((sum, p) => sum + parseFloat(p.quantity), 0),
            })).filter(g => g.packages.length > 0);
        },

        get grandTotalPackages() {
            return this.lines.reduce((sum, l) => sum + l.packages.length, 0);
        },

        onProductChange() {
            this.config.lot_id = '';
            this.existingLineInfo = '';
            this.forOrder = false;

            const existing = this.lines.find(l => l.product_variant_id == this.config.product_variant_id);
            if (existing) {
                this.config.merma_kg = String(existing.merma);
                this.config.package_type = existing.package_type;
                if (existing.lot_id) this.config.lot_id = String(existing.lot_id);
                this.existingLineInfo = existing.productName + ' ya tiene ' + existing.packages.length + ' bultos';
            }
        },

        lockProduct() {
            if (!this.config.product_variant_id || !this.config.lot_id) return;
            this.productLocked = true;
            this.sessionCount = 0;
            this.sessionTotal = 0;
            this.$nextTick(() => this.$refs.weightInput.focus());
        },

        addAnotherProduct() {
            this.productLocked = false;
            this.showLotSelector = false;
            this.forOrder = false;
            this.config = { product_variant_id: '', lot_id: '', package_type: 'saco', merma_kg: '0' };
            this.sessionCount = 0;
            this.sessionTotal = 0;
            this.existingLineInfo = '';
        },

        async addPackage() {
            if (!this.weight || this.loading) return;

            // Validar límite solo si es para pedido
            if (this.forOrder && this.isOrderAtLimit) {
                this.message = '⚠️ Límite del pedido alcanzado. Desmarca el checkbox para agregar para tienda.';
                this.messageType = 'error';
                return;
            }

            this.loading = true;
            this.message = '';

            try {
                const res = await fetch('{{ route("almacen.transfers.add-package", $transfer) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        lot_id: this.config.lot_id,
                        package_type: this.config.package_type,
                        quantity: this.weight,
                        merma_kg: this.config.merma_kg,
                        for_order: this.forOrder,
                    }),
                });

                const data = await res.json();

                if (data.success) {
                    const qty = parseFloat(this.weight);
                    this.sessionCount++;
                    this.sessionTotal += qty;

                    const newPkg = {
                        id: data.package.id,
                        uuid: data.package.uuid,
                        lot_code: data.package.lot_code,
                        lot_id: parseInt(this.config.lot_id),
                        package_type: this.config.package_type,
                        quantity: qty.toFixed(3),
                        label_url: data.package.label_url,
                        forOrder: this.forOrder,
                    };

                    let line = this.lines.find(l => l.product_variant_id == this.config.product_variant_id);
                    if (!line) {
                        line = {
                            lineId: null,
                            product_variant_id: parseInt(this.config.product_variant_id),
                            productName: this.currentProductLabel,
                            merma: parseFloat(this.config.merma_kg),
                            unit: this.currentUnit,
                            package_type: this.config.package_type,
                            lot_id: parseInt(this.config.lot_id),
                            packages: [],
                        };
                        this.lines.push(line);
                    }
                    line.packages.push(newPkg);
                    line.lot_id = parseInt(this.config.lot_id);

                    const tag = this.forOrder ? ' [PEDIDO]' : ' [TIENDA]';
                    this.message = '#' + this.sessionCount + ' — ' + qty.toFixed(3) + ' ' + this.currentUnit + tag;
                    this.messageType = 'success';

                    window.open(data.package.label_url, '_blank');

                    const lot = this.lots.find(l => l.id == this.config.lot_id);
                    if (lot) {
                        lot.remaining = Math.max(0, lot.remaining - qty);
                        lot.label = lot.lot_code + ' (' + lot.remaining.toFixed(3) + ' ' + lot.unit + ')';
                    }

                    this.weight = '';
                    this.$nextTick(() => this.$refs.weightInput.focus());
                    setTimeout(() => { this.message = ''; }, 3000);
                } else {
                    this.message = data.message;
                    this.messageType = 'error';
                }
            } catch (e) {
                this.message = 'Error de conexión.';
                this.messageType = 'error';
            }

            this.loading = false;
        },

        async removePackage(packageId, lineIdx) {
            if (!confirm('¿Quitar este bulto?')) return;
            try {
                const res = await fetch('/almacen/transfers/{{ $transfer->id }}/packages/' + packageId, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json();
                if (data.success) {
                    const pkgIdx = this.lines[lineIdx].packages.findIndex(p => p.id === packageId);
                    if (pkgIdx !== -1) {
                        const pkg = this.lines[lineIdx].packages[pkgIdx];
                        const qty = parseFloat(pkg.quantity);
                        const lot = this.lots.find(l => l.id == pkg.lot_id);
                        if (lot) {
                            lot.remaining += qty;
                            lot.label = lot.lot_code + ' (' + lot.remaining.toFixed(3) + ' ' + lot.unit + ')';
                        }
                        this.lines[lineIdx].packages.splice(pkgIdx, 1);
                        if (this.lines[lineIdx].packages.length === 0) this.lines.splice(lineIdx, 1);
                    }
                }
            } catch (e) { alert('Error al quitar.'); }
        },

        async saveMermaForLine(lineId, value) {
            if (!lineId) return;
            try {
                await fetch('/almacen/transfer-lines/' + lineId + '/merma', {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ merma_kg: value }),
                });
                const line = this.lines.find(l => l.lineId == lineId);
                if (line) line.merma = parseFloat(value);
            } catch (e) {}
        },
    }
}
</script>
@endsection