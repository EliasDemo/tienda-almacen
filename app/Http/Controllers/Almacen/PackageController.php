<?php

namespace App\Http\Controllers\Almacen;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Transfer;

class PackageController extends Controller
{
    /**
     * Lista de cargamentos con resumen de bultos
     */
    public function index()
    {
        $transfers = Transfer::with(['dispatcher', 'lines.variant.product', 'lines.packages'])
            ->latest()
            ->paginate(15);

        return view('almacen.packages.index', compact('transfers'));
    }

    /**
     * Resumen de un cargamento + todos sus bultos
     */
    public function show(Transfer $transfer)
    {
        $transfer->load([
            'dispatcher',
            'receiver',
            'lines.variant.product.category',
            'lines.packages.lot',
        ]);

        return view('almacen.packages.show', compact('transfer'));
    }

    /**
     * Etiqueta individual con QR + código de barras
     */
    public function label(Package $package)
    {
        $package->load(['lot.variant.product.category', 'transferLine']);

        return view('almacen.packages.label', compact('package'));
    }
}