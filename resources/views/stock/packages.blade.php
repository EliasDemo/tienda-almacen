@extends('layouts.app')

@section('title', 'Detalle Bultos — ' . $variant->product->name)

@section('content')
<div class="space-y-4">

    <div class="flex justify-between items-start">
        <div class="flex items-center gap-4">
            <img src="{{ $image ? asset('storage/' . $image->path) : 'https://www.domoticaonline.net/images/imagen-no-disponible.jpg' }}"
                 alt="{{ $variant->product->name }}" class="w-16 h-16 rounded-xl object-cover border-2 border-gray-200">
            <div>
                <a href="{{ route('stock.index') }}" class="text-sm text-blue-600 hover:text-blue-800 mb-1 inline-block">
                    <i class="fas fa-arrow-left mr-1"></i> Volver al Stock
                </a>
                <h1 class="text-2xl font-bold text-gray-900">{{ $variant->product->name }} — {{ $variant->name }}</h1>
                <p class="text-sm text-gray-500 mt-1">
                    {{ $variant->product->category->name }} ·
                    Venta por: <span class="px-1.5 py-0.5 rounded text-[10px] font-bold {{ $variant->sale_unit === 'unit' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">{{ $variant->sale_unit === 'unit' ? 'UNIDAD' : 'KG' }}</span>
                </p>
            </div>
        </div>
        <div class="text-right">
            <div class="text-sm text-gray-500">Precios actuales</div>
            <div class="flex gap-3 mt-1">
                <div class="bg-green-50 border border-green-200 rounded-lg px-3 py-1.5 text-center">
                    <div class="text-[10px] text-green-600 font-bold uppercase">Minorista</div>
                    <div class="text-lg font-bold text-green-700">S/ {{ isset($prices['minorista']) ? number_format($prices['minorista']->price, 2) : '—' }}</div>
                </div>
                <div class="bg-blue-50 border border-blue-200 rounded-lg px-3 py-1.5 text-center">
                    <div class="text-[10px] text-blue-600 font-bold uppercase">Mayorista</div>
                    <div class="text-lg font-bold text-blue-700">S/ {{ isset($prices['mayorista']) ? number_format($prices['mayorista']->price, 2) : '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    @php
        $closed = $packages->where('status', 'closed');
        $opened = $packages->where('status', 'opened');
        $isUnit = ($variant->sale_unit === 'unit');
        if ($isUnit) {
            $totalStock = $closed->sum('unit_count') + $opened->sum('net_units');
        } else {
            $totalStock = $closed->sum('gross_weight') + $opened->sum('net_weight');
        }
    @endphp
    <div class="grid grid-cols-4 gap-3">
        <div class="bg-white rounded-xl shadow-sm border p-4 text-center">
            <p class="text-3xl font-black text-gray-900">{{ $packages->count() }}</p>
            <p class="text-xs text-gray-500">Bultos en tienda</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border p-4 text-center">
            <p class="text-3xl font-black text-indigo-600">{{ $closed->count() }}</p>
            <p class="text-xs text-gray-500">Cerrados</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border p-4 text-center">
            <p class="text-3xl font-black text-amber-600">{{ $opened->count() }}</p>
            <p class="text-xs text-gray-500">Abiertos</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border p-4 text-center">
            <p class="text-3xl font-black text-green-600">{{ $isUnit ? $totalStock : number_format($totalStock, 2) }}</p>
            <p class="text-xs text-gray-500">{{ $isUnit ? 'unidades' : 'kg' }} disponible</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <div class="px-4 py-3 border-b bg-gray-50 flex justify-between items-center">
            <h3 class="font-bold text-sm text-gray-700"><i class="fas fa-list mr-1 text-gray-400"></i> Detalle de Bultos</h3>
            <span class="text-xs text-gray-400">{{ $packages->count() }} bultos</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b">
                        <th class="text-left px-4 py-2 font-medium text-gray-500">UUID</th>
                        <th class="text-center px-3 py-2 font-medium text-gray-500">Estado</th>
                        <th class="text-center px-3 py-2 font-medium text-gray-500">Tipo</th>
                        <th class="text-right px-3 py-2 font-medium text-gray-500">{{ $isUnit ? 'Cantidad' : 'Peso Bruto' }}</th>
                        <th class="text-right px-3 py-2 font-medium text-gray-500">{{ $isUnit ? 'Disponible' : 'Peso Neto' }}</th>
                        <th class="text-center px-3 py-2 font-medium text-gray-500">Pedido</th>
                        <th class="text-left px-3 py-2 font-medium text-gray-500">Cargamento</th>
                        <th class="text-left px-3 py-2 font-medium text-gray-500">Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($packages as $pkg)
                        <tr class="border-b hover:bg-gray-50 transition">
                            <td class="px-4 py-2">
                                <code class="text-xs bg-gray-100 px-1.5 py-0.5 rounded font-mono text-gray-600">{{ Str::limit($pkg->uuid, 12) }}</code>
                            </td>
                            <td class="text-center px-3 py-2">
                                @if($pkg->status === 'closed')
                                    <span class="bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded-full text-xs font-bold">Cerrado</span>
                                @elseif($pkg->status === 'opened')
                                    <span class="bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full text-xs font-bold">Abierto</span>
                                @endif
                            </td>
                            <td class="text-center px-3 py-2">
                                <span class="text-xs font-bold px-2 py-0.5 rounded {{ $pkg->package_type === 'caja' ? 'bg-cyan-100 text-cyan-700' : 'bg-gray-100 text-gray-600' }}">
                                    {{ $pkg->package_type === 'caja' ? 'Caja' : 'Saco' }}
                                </span>
                            </td>
                            <td class="text-right px-3 py-2 font-medium">
                                @if($isUnit)
                                    {{ $pkg->unit_count ?? '—' }} unid
                                @else
                                    {{ $pkg->gross_weight ? number_format($pkg->gross_weight, 3) . ' kg' : '—' }}
                                @endif
                            </td>
                            <td class="text-right px-3 py-2">
                                @if($pkg->status === 'opened')
                                    <span class="font-bold text-amber-700">
                                        @if($isUnit)
                                            {{ $pkg->net_units ?? '—' }} unid
                                        @else
                                            {{ $pkg->net_weight ? number_format($pkg->net_weight, 3) . ' kg' : '—' }}
                                        @endif
                                    </span>
                                @else
                                    <span class="text-gray-400">= original</span>
                                @endif
                            </td>
                            <td class="text-center px-3 py-2">
                                @if($pkg->for_order)
                                    <span class="bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full text-xs font-bold">
                                        <i class="fas fa-star mr-0.5"></i> Pedido
                                    </span>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-xs text-gray-500">
                                @if($pkg->transferLine && $pkg->transferLine->transfer)
                                    Carg. #{{ $pkg->transferLine->transfer->id }}
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-xs text-gray-500">
                                {{ $pkg->created_at->format('d/m/Y H:i') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-8 text-gray-400">
                                <i class="fas fa-inbox text-3xl mb-2 block"></i>
                                No hay bultos disponibles
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection