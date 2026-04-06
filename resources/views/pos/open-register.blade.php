@extends('layouts.app')

@section('title', 'Abrir Caja')

@section('content')
<div class="max-w-md mx-auto mt-10">
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4 text-center">Abrir Caja</h3>

        <form method="POST" action="{{ route('pos.open-register.store') }}">
            @csrf

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Fondo Inicial (S/)</label>
                <input type="number" step="0.01" min="0" name="opening_amount"
                       value="{{ old('opening_amount', '0') }}"
                       class="w-full border-gray-300 rounded-lg shadow-sm text-2xl text-center focus:ring-blue-500 focus:border-blue-500 h-14"
                       required autofocus>
            </div>

            <button type="submit"
                    class="w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 font-medium">
                Abrir Caja
            </button>
        </form>
    </div>
</div>
@endsection