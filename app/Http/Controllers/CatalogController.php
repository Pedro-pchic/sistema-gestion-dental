<?php

namespace App\Http\Controllers;

use App\Actions\RecordEcommerceEvent;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where('is_active', true),
            ],
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $categoryId = isset($filters['category_id']) ? (int) $filters['category_id'] : null;
        $search = trim($filters['search'] ?? '');

        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $products = Product::query()
            ->publiclyVisible()
            ->with(['category', 'inventory'])
            ->when($categoryId !== null, fn (Builder $query): Builder => $query->where('category_id', $categoryId))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $term = '%'.$search.'%';

                $query->where(function (Builder $query) use ($term): void {
                    $query->where('name', 'like', $term)->orWhere('sku', 'like', $term);
                });
            })
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(12)
            ->withQueryString();

        return view('catalog.index', compact('categories', 'categoryId', 'products', 'search'));
    }

    public function show(Request $request, Product $product, RecordEcommerceEvent $recordEcommerceEvent): View
    {
        $product->load(['category', 'inventory']);

        abort_unless($product->is_active && $product->category->is_active, 404);

        $recordEcommerceEvent->recordProductViewed($request, $product);

        return view('catalog.show', compact('product'));
    }
}
