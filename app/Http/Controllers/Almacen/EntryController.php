<?php

namespace App\Http\Controllers\Almacen;

use App\Http\Controllers\Controller;
use App\Http\Requests\Almacen\EntryStoreRequest;
use App\Models\Lot;
use App\Models\ProductVariant;
use App\Services\Inventory\EntryService;

class EntryController extends Controller
{
    public function __construct(
        private EntryService $entryService
    ) {}

    public function index()
    {
        $lots = Lot::with(['variant.product.category'])
            ->latest()
            ->paginate(15);

        return view('almacen.entries.index', compact('lots'));
    }

    public function create()
    {
        $variants = ProductVariant::with('product.category')
            ->where('is_active', true)
            ->get()
            ->map(function ($v) {
                return [
                    'id'        => $v->id,
                    'label'     => $v->product->category->name . ' > ' . $v->product->name . ' > ' . $v->name,
                    'unit_type' => $v->product->unit_type,
                ];
            });

        return view('almacen.entries.create', compact('variants'));
    }

    public function store(EntryStoreRequest $request)
    {
        $lot = $this->entryService->registerEntry(
            $request->validated(),
            $request->user()->id
        );

        return redirect()
            ->route('almacen.entries.index')
            ->with('success', "Entrada registrada. Lote: {$lot->lot_code} - {$lot->total_quantity} {$lot->unit}");
    }

    public function show(Lot $lot)
    {
        $lot->load(['variant.product.category', 'packages.transferLine.transfer']);

        return view('almacen.entries.show', compact('lot'));
    }
}