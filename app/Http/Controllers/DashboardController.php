<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Package;
use App\Models\Sale;
use App\Models\Lot;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'categories' => Category::count(),
            'lots' => Lot::count(),
            'packages_almacen' => Package::where('location', 'almacen')->count(),
            'packages_tienda' => Package::where('location', 'tienda')->count(),
            'sales_today' => Sale::whereDate('created_at', today())->count(),
        ];

        return view('dashboard', compact('stats'));
    }
}