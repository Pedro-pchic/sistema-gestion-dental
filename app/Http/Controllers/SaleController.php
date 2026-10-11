<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\View\View;

class SaleController extends Controller
{
    public function index(): View
    {
        $sales = Sale::query()
            ->with(['customer', 'order'])
            ->orderByDesc('sale_date')
            ->orderByDesc('id')
            ->paginate(15);

        return view('sales.index', compact('sales'));
    }

    public function show(Sale $sale): View
    {
        $sale->load(['customer', 'order', 'details.product', 'invoice']);

        return view('sales.show', compact('sale'));
    }
}
