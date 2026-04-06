<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = Customer::withCount('sales')
            ->withSum(['credits as pending_debt' => fn($q) => $q->where('status', '!=', 'paid')], 'balance')
            ->orderBy('name')
            ->paginate(20);

        return view('admin.customers.index', compact('customers'));
    }

    public function show(Customer $customer)
    {
        $customer->load([
            'sales' => fn($q) => $q->where('status', 'completed')->orderBy('created_at', 'desc'),
            'sales.items.variant.product',
            'sales.payments',
            'sales.user',
            'credits' => fn($q) => $q->orderBy('created_at', 'desc'),
            'credits.sale',
            'credits.payments',
        ]);

        $salesByDate = $customer->sales->groupBy(fn($sale) => $sale->created_at->format('Y-m-d'));

        $stats = [
            'total_purchases' => $customer->sales->count(),
            'total_spent'     => $customer->sales->sum('total'),
            'pending_debt'    => $customer->credits->where('status', '!=', 'paid')->sum('balance'),
            'total_credited'  => $customer->credits->sum('original_amount'),
            'total_paid_debt' => $customer->credits->sum('paid_amount'),
        ];

        return view('admin.customers.show', compact('customer', 'salesByDate', 'stats'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'             => 'required|string|max:255',
            'phone'            => 'nullable|string|max:20',
            'document'         => 'nullable|string|max:20',
            'price_type'       => 'required|in:minorista,mayorista',
            'discount_percent' => 'required|numeric|min:0|max:100',
            'credit_limit'     => 'nullable|numeric|min:0',
            'notes'            => 'nullable|string',
        ]);

        Customer::create($request->all());

        return back()->with('success', 'Cliente creado correctamente.');
    }

    public function update(Request $request, Customer $customer)
    {
        $request->validate([
            'name'             => 'required|string|max:255',
            'phone'            => 'nullable|string|max:20',
            'document'         => 'nullable|string|max:20',
            'price_type'       => 'required|in:minorista,mayorista',
            'discount_percent' => 'required|numeric|min:0|max:100',
            'credit_limit'     => 'nullable|numeric|min:0',
            'notes'            => 'nullable|string',
        ]);

        $customer->update($request->all());

        return back()->with('success', 'Cliente actualizado.');
    }

    public function toggleActive(Customer $customer)
    {
        $customer->update(['is_active' => !$customer->is_active]);
        return back()->with('success', $customer->is_active ? 'Cliente activado.' : 'Cliente desactivado.');
    }

    public function toggleCreditBlock(Request $request, Customer $customer)
    {
        if ($customer->credit_blocked) {
            $customer->update([
                'credit_blocked' => false,
                'block_reason'   => null,
            ]);
            return back()->with('success', "Crédito desbloqueado para {$customer->name}.");
        }

        $customer->update([
            'credit_blocked' => true,
            'block_reason'   => $request->input('reason', 'Bloqueado manualmente.'),
        ]);

        return back()->with('success', "Crédito bloqueado para {$customer->name}.");
    }
}