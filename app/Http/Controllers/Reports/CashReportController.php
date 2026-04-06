<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\CashRegister;
use App\Models\Sale;
use App\Exports\CashRegisterExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashReportController extends Controller
{
    /**
     * Lista de cajas agrupadas por día (carpetas)
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = CashRegister::select(
                DB::raw('DATE(opened_at) as date'),
                DB::raw('COUNT(*) as total_registers'),
                DB::raw('SUM(total_sales) as total_sales'),
                DB::raw('SUM(total_cash) as total_cash'),
                DB::raw('SUM(total_other) as total_other'),
                DB::raw('GROUP_CONCAT(DISTINCT user_id) as user_ids')
            )
            ->where('status', 'closed')
            ->groupBy(DB::raw('DATE(opened_at)'))
            ->orderBy('date', 'desc');

        // Cajero solo ve últimos 3 días
        if ($user->hasRole('caja') && !$user->hasAnyRole(['admin', 'gerente'])) {
            $query->where('opened_at', '>=', now()->subDays(3)->startOfDay());
        }

        $days = $query->paginate(15);

        return view('reports.cash.index', compact('days'));
    }

    /**
     * Cajas de un día específico
     */
    public function day(Request $request, string $date)
    {
        $user = $request->user();

        // Validar acceso cajero (3 días)
        if ($user->hasRole('caja') && !$user->hasAnyRole(['admin', 'gerente'])) {
            $limit = now()->subDays(3)->startOfDay();
            if ($date < $limit->toDateString()) {
                return back()->with('error', 'Solo puedes ver los últimos 3 días.');
            }
        }

        $registers = CashRegister::with(['user', 'sales.payments'])
            ->where('status', 'closed')
            ->whereDate('opened_at', $date)
            ->orderBy('opened_at')
            ->get();

        $summary = [
            'total_registers' => $registers->count(),
            'total_sales'     => $registers->sum('total_sales'),
            'total_cash'      => $registers->sum('total_cash'),
            'total_other'     => $registers->sum('total_other'),
            'sales_count'     => $registers->sum(fn($r) => $r->sales->where('status', 'completed')->count()),
        ];

        return view('reports.cash.day', compact('registers', 'date', 'summary'));
    }

    /**
     * Detalle de una caja específica
     */
    public function show(CashRegister $register)
    {
        $register->load([
            'user',
            'sales' => fn($q) => $q->where('status', 'completed')->orderBy('created_at'),
            'sales.items.variant.product.category',
            'sales.payments',
            'sales.customer',
        ]);

        return view('reports.cash.show', compact('register'));
    }

    /**
     * Detalle de una venta específica
     */
    public function showSale(Sale $sale)
    {
        $sale->load([
            'items.variant.product.category',
            'items.package.lot',
            'payments',
            'customer',
            'user',
            'cashRegister',
        ]);

        return view('reports.cash.sale', compact('sale'));
    }

    /**
     * Exportar caja a Excel
     */
    public function exportExcel(CashRegister $register)
    {
        $register->load([
            'user',
            'sales' => fn($q) => $q->where('status', 'completed'),
            'sales.items.variant.product',
            'sales.payments',
            'sales.customer',
        ]);

        return Excel::download(
            new CashRegisterExport($register),
            'caja-' . $register->opened_at->format('Y-m-d') . '-' . $register->user->name . '.xlsx'
        );
    }

    /**
     * Exportar caja a PDF
     */
    public function exportPdf(CashRegister $register)
    {
        $register->load([
            'user',
            'sales' => fn($q) => $q->where('status', 'completed'),
            'sales.items.variant.product',
            'sales.payments',
            'sales.customer',
        ]);

        $expectedCash = (float) $register->opening_amount + (float) $register->total_cash;

        $pdf = Pdf::loadView('reports.cash.pdf', compact('register', 'expectedCash'));
        $pdf->setPaper('A4');

        return $pdf->download('caja-' . $register->opened_at->format('Y-m-d') . '-' . $register->user->name . '.pdf');
    }

    /**
     * Exportar día completo a Excel
     */
    public function exportDayExcel(string $date)
    {
        $registers = CashRegister::with([
                'user',
                'sales' => fn($q) => $q->where('status', 'completed'),
                'sales.items.variant.product',
                'sales.payments',
            ])
            ->where('status', 'closed')
            ->whereDate('opened_at', $date)
            ->get();

        return Excel::download(
            new \App\Exports\DayRegisterExport($registers, $date),
            'reporte-dia-' . $date . '.xlsx'
        );
    }

    /**
     * Exportar día completo a PDF
     */
    public function exportDayPdf(string $date)
    {
        $registers = CashRegister::with([
                'user',
                'sales' => fn($q) => $q->where('status', 'completed'),
                'sales.items.variant.product',
                'sales.payments',
            ])
            ->where('status', 'closed')
            ->whereDate('opened_at', $date)
            ->get();

        $summary = [
            'total_registers' => $registers->count(),
            'total_sales'     => $registers->sum('total_sales'),
            'total_cash'      => $registers->sum('total_cash'),
            'total_other'     => $registers->sum('total_other'),
            'sales_count'     => $registers->sum(fn($r) => $r->sales->count()),
        ];

        $pdf = Pdf::loadView('reports.cash.day-pdf', compact('registers', 'date', 'summary'));
        $pdf->setPaper('A4');

        return $pdf->download('reporte-dia-' . $date . '.pdf');
    }
}
