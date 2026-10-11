<?php

namespace App\Http\Controllers;

use App\Actions\RecordEcommerceEvent;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, RecordEcommerceEvent $recordEcommerceEvent): View
    {
        $recordEcommerceEvent->recordPortalVisit($request);

        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(6)
            ->get();

        $availableProducts = Product::query()
            ->publiclyVisible()
            ->with(['category', 'inventory'])
            ->whereHas('inventory', fn (Builder $query): Builder => $query->where('stock', '>', 0))
            ->orderByDesc('id')
            ->limit(12)
            ->get();
        $heroProduct = $availableProducts->first(
            static fn (Product $product): bool => $product->imageDeliveryUrl(960) !== null,
        ) ?? $availableProducts->first();
        $featuredProducts = $availableProducts
            ->reject(fn (Product $product): bool => $heroProduct !== null && $product->is($heroProduct))
            ->take(6)
            ->values();

        return view('portal.home', compact('categories', 'featuredProducts', 'heroProduct'));
    }
}
