<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerCredit;
use App\Services\CashRegisterService;
use App\Services\CreditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CreditController extends Controller
{
    public function __construct(
        private CreditService $creditService,
        private CashRegisterService $cashService
    ) {}

    /**
     * Panel de fiados
     */
    public function index()
    {
        $customers = Customer::whereHas('credits', fn($q) => $q->where('status', '!=', 'paid'))
            ->with(['credits' => fn($q) => $q->where('status', '!=', 'paid')->with('sale')])
            ->get()
            ->map(function ($customer) {
                $customer->pending_balance = $customer->credits->sum('balance');
                return $customer;
            })
            ->sortByDesc('pending_balance');

        $totalPending = $customers->sum('pending_balance');

        return view('pos.credits.index', compact('customers', 'totalPending'));
    }

    /**
     * Detalle de fiados de un cliente
     */
    public function show(Customer $customer)
    {
        $customer->load([
            'credits' => fn($q) => $q->orderBy('created_at', 'desc'),
            'credits.sale.items.variant.product',
            'credits.payments.user',
            'credits.payments.cashRegister',
        ]);

        $pendingBalance = $customer->credits->where('status', '!=', 'paid')->sum('balance');
        $register = $this->cashService->getOpenRegister(Auth::id());

        return view('pos.credits.show', compact('customer', 'pendingBalance', 'register'));
    }

    /**
     * AJAX: Registrar pago
     */
    public function pay(Request $request, CustomerCredit $credit)
    {
        $request->validate([
            'amount'    => 'required|numeric|min:0.01',
            'method'    => 'required|in:cash,transfer,other',
            'reference' => 'nullable|string',
            'notes'     => 'nullable|string',
        ]);

        $register = $this->cashService->getOpenRegister($request->user()->id);

        try {
            $payment = $this->creditService->registerPayment(
                $credit,
                array_merge($request->all(), [
                    'cash_register_id' => $register?->id,
                ]),
                $request->user()->id
            );

            return response()->json([
                'success'  => true,
                'message'  => 'Pago de S/ ' . number_format($payment->amount, 2) . ' registrado.',
                'balance'  => number_format((float) $credit->fresh()->balance, 2),
                'status'   => $credit->fresh()->status,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}