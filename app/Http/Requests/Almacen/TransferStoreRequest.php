<?php

namespace App\Http\Requests\Almacen;

use Illuminate\Foundation\Http\FormRequest;

class TransferStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['admin', 'almacen']);
    }

    public function rules(): array
    {
        return [
            'notes'                          => 'nullable|string',
            'lines'                          => 'required|array|min:1',
            'lines.*.product_variant_id'     => 'required|exists:product_variants,id',
            'lines.*.merma_kg'               => 'required|numeric|min:0',
            'lines.*.notes'                  => 'nullable|string',
            'lines.*.packages'               => 'required|array|min:1',
            'lines.*.packages.*.lot_id'      => 'required|exists:lots,id',
            'lines.*.packages.*.package_type' => 'required|in:saco,caja',
            'lines.*.packages.*.quantity'    => 'required|numeric|min:0.001',
        ];
    }

    public function messages(): array
    {
        return [
            'lines.required'                      => 'Agrega al menos un producto al cargamento.',
            'lines.*.packages.required'            => 'Cada producto debe tener al menos un bulto.',
            'lines.*.packages.*.quantity.required'  => 'Ingresa el peso o cantidad del bulto.',
            'lines.*.packages.*.quantity.min'       => 'El peso debe ser mayor a 0.',
            'lines.*.merma_kg.required'             => 'Ingresa la merma pronosticada.',
        ];
    }
}