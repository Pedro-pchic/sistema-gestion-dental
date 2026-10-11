<?php

namespace App\Http\Controllers;

use App\Enums\FinancialCategory;
use App\Http\Requests\StoreIncomeRequest;
use App\Models\Income;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class IncomeController extends Controller
{
    public function index(): View
    {
        $incomes = Income::query()
            ->with('payment.invoice')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(15);

        return view('incomes.index', compact('incomes'));
    }

    public function create(): View
    {
        return view('incomes.create');
    }

    public function store(StoreIncomeRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $amountCents = Money::toCents((string) $data['amount']);

        Income::create([
            'payment_id' => null,
            'category' => FinancialCategory::OtherIncome,
            'date' => $data['date'],
            'amount' => Money::fromCents($amountCents),
            'description' => $data['description'],
        ]);

        return redirect()->route('finanzas.ingresos.index')->with('status', 'Ingreso manual registrado.');
    }
}
