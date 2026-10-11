<?php

namespace App\Actions;

use App\Models\Product;

class LoadCart
{
    public const string SESSION_KEY = 'ecommerce.cart';

    /**
     * @param  array<int|string, mixed>  $quantities
     * @return array{items: array<int, array{product: Product, quantity: int, subtotal: string}>, total: string, can_checkout: bool}
     */
    public function handle(array $quantities): array
    {
        $normalizedQuantities = collect($quantities)
            ->mapWithKeys(fn (mixed $quantity, int|string $productId): array => [(int) $productId => (int) $quantity])
            ->filter(fn (int $quantity, int $productId): bool => $productId > 0 && $quantity > 0);

        if ($normalizedQuantities->isEmpty()) {
            return ['items' => [], 'total' => '0.00', 'can_checkout' => false];
        }

        $products = Product::query()
            ->with(['category', 'inventory'])
            ->whereKey($normalizedQuantities->keys())
            ->get()
            ->keyBy(fn (Product $product): int => $product->getKey());

        $items = [];
        $totalCents = 0;
        $canCheckout = true;

        foreach ($normalizedQuantities as $productId => $quantity) {
            $product = $products->get($productId);

            if (! $product instanceof Product) {
                $canCheckout = false;

                continue;
            }

            $subtotalCents = $this->toCents($product->price) * $quantity;
            $totalCents += $subtotalCents;
            $canCheckout = $canCheckout
                && $product->isPurchasable()
                && $product->inventory->stock >= $quantity;
            $items[] = [
                'product' => $product,
                'quantity' => $quantity,
                'subtotal' => $this->formatCents($subtotalCents),
            ];
        }

        return [
            'items' => $items,
            'total' => $this->formatCents($totalCents),
            'can_checkout' => $canCheckout && count($items) === $normalizedQuantities->count(),
        ];
    }

    private function toCents(int|float|string $amount): int
    {
        $normalizedAmount = number_format((float) $amount, 2, '.', '');
        [$wholeUnits, $fractionalUnits] = explode('.', $normalizedAmount);

        return ((int) $wholeUnits * 100) + (int) $fractionalUnits;
    }

    private function formatCents(int $amount): string
    {
        return intdiv($amount, 100).'.'.str_pad((string) ($amount % 100), 2, '0', STR_PAD_LEFT);
    }
}
