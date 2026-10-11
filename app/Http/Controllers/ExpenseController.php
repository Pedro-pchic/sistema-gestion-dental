<?php

namespace App\Http\Controllers;

use App\Actions\RecordExpense;
use App\Enums\FinancialCategory;
use App\Enums\PurchaseStatus;
use App\Http\Requests\StoreExpenseRequest;
use App\Models\Expense;
use App\Models\Purchase;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function index(): View
    {
        $expenses = Expense::query()
            ->with('purchase.supplier')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(15);

        return view('expenses.index', compact('expenses'));
    }

    public function create(): View
    {
        $purchases = Purchase::query()
            ->with('supplier')
            ->where('status', PurchaseStatus::Received->value)
            ->whereDoesntHave('expense')
            ->orderByDesc('order_date')
            ->orderByDesc('id')
            ->get();

        return view('expenses.create', [
            'categories' => FinancialCategory::manualExpenseCases(),
            'purchases' => $purchases,
        ]);
    }

    public function store(StoreExpenseRequest $request, RecordExpense $recordExpense): RedirectResponse
    {
        $recordExpense->handle($request->validated());

        return redirect()->route('finanzas.gastos.index')->with('status', 'Gasto registrado.');
    }
}
