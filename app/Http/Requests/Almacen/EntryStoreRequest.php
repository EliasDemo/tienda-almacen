<?php

namespace App\Http\Requests\Almacen;

use Illuminate\Foundation\Http\FormRequest;

class EntryStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['admin', 'almacen']);
    }

    public function rules(): array
    {
        return [
            'product_variant_id'      => 'required|exists:product_variants,id',
            'supplier'                => 'nullable|string|max:255',
            'purchase_price_per_kg'   => 'nullable|numeric|min:0',
            'purchase_price_per_unit' => 'nullable|numeric|min:0',
            'total_quantity'          => 'required|numeric|min:0.001',
            'entry_date'              => 'required|date',
            'expiry_date'             => 'nullable|date|after_or_equal:entry_date',
            'notes'                   => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'product_variant_id.required' => 'Selecciona un producto.',
            'total_quantity.required'      => 'Ingresa la cantidad total.',
            'total_quantity.min'           => 'La cantidad debe ser mayor a 0.',
        ];
    }
}