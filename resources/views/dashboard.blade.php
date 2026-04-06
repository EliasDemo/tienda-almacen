@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

    <div class="bg-white rounded-lg shadow p-6">
        <p class="text-sm text-gray-500">Categorías</p>
        <p class="text-3xl font-bold text-gray-800">{{ $stats['categories'] }}</p>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <p class="text-sm text-gray-500">Lotes Registrados</p>
        <p class="text-3xl font-bold text-gray-800">{{ $stats['lots'] }}</p>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <p class="text-sm text-gray-500">Bultos en Almacén</p>
        <p class="text-3xl font-bold text-blue-600">{{ $stats['packages_almacen'] }}</p>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <p class="text-sm text-gray-500">Bultos en Tienda</p>
        <p class="text-3xl font-bold text-green-600">{{ $stats['packages_tienda'] }}</p>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <p class="text-sm text-gray-500">Ventas Hoy</p>
        <p class="text-3xl font-bold text-gray-800">{{ $stats['sales_today'] }}</p>
    </div>

</div>
@endsection