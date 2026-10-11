<?php

namespace App\Actions;

use App\Models\Address;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PlaceWebOrder
{
    public function __construct(private SaveOrder $saveOrder) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int|string, mixed>  $cart
     */
    public function handle(array $data, array $cart): Order
    {
        $quantities = collect($cart)
            ->mapWithKeys(fn (mixed $quantity, int|string $productId): array => [(int) $productId => (int) $quantity])
            ->filter(fn (int $quantity, int $productId): bool => $productId > 0 && $quantity > 0);

        if ($quantities->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => 'El carrito está vacío.',
            ]);
        }

        return DB::transaction(function () use ($data, $quantities): Order {
            $products = Product::query()
                ->whereKey($quantities->keys())
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy(fn (Product $product): int => $product->getKey());

            $categories = Category::query()
                ->whereKey($products->pluck('category_id')->unique())
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy(fn (Category $category): int => $category->getKey());

            $inventories = Inventory::query()
                ->whereIn('product_id', $quantities->keys())
                ->orderBy('product_id')
                ->lockForUpdate()
                ->get()
                ->keyBy('product_id');

            $details = [];

            foreach ($quantities as $productId => $quantity) {
                $product = $products->get($productId);
                $category = $product instanceof Product ? $categories->get($product->category_id) : null;
                $inventory = $inventories->get($productId);

                if (! $product instanceof Product || ! $product->is_active || ! $category?->is_active) {
                    throw ValidationException::withMessages([
                        'cart' => 'Uno de los productos ya no está disponible para compra.',
                    ]);
                }

                if ($quantity > Inventory::MAX_STOCK || ! $inventory instanceof Inventory || $inventory->stock < $quantity) {
                    throw ValidationException::withMessages([
                        'cart' => "Existencia insuficiente para el producto {$product->sku}.",
                    ]);
                }

                $details[] = [
                    'product_id' => $product->getKey(),
                    'quantity' => $quantity,
                    'unit_price' => $product->price,
                ];
            }

            $customer = $this->resolveCustomer($data);
            $address = $this->resolveAddress($customer, $data);
            $order = $this->saveOrder->handle([
                'customer_id' => $customer->getKey(),
                'order_date' => today()->toDateString(),
                'notes' => $data['notes'] ?? null,
                'details' => $details,
            ]);
            $order->update([
                'address_id' => $address->getKey(),
                'origin' => 'web',
            ]);

            return $order->load(['address', 'customer', 'details.product']);
        }, attempts: 3);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveCustomer(array $data): Customer
    {
        $email = Str::lower(trim((string) $data['customer_email']));
        $nit = trim((string) ($data['customer_nit'] ?? ''));

        $customer = Customer::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->lockForUpdate()
            ->first();

        if (! $customer instanceof Customer && $nit !== '') {
            $customer = Customer::query()
                ->where('nit', $nit)
                ->lockForUpdate()
                ->first();
        }

        if ($customer instanceof Customer && ! $customer->is_active) {
            throw ValidationException::withMessages([
                'customer_email' => 'El cliente asociado a estos datos está inactivo.',
            ]);
        }

        if ($customer instanceof Customer) {
            return $customer;
        }

        return Customer::create([
            'name' => $data['customer_name'],
            'nit' => $nit !== '' ? $nit : null,
            'phone' => $data['customer_phone'] ?? null,
            'email' => $email,
            'is_active' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveAddress(Customer $customer, array $data): Address
    {
        $address = $customer->addresses()
            ->where('address', $data['address'])
            ->where('city', $data['city'])
            ->where('department', $data['department'])
            ->lockForUpdate()
            ->first();

        if ($address instanceof Address) {
            return $address;
        }

        return $customer->addresses()->create([
            'label' => $data['address_label'] ?? null,
            'address' => $data['address'],
            'city' => $data['city'],
            'department' => $data['department'],
            'is_default' => ! $customer->addresses()->exists(),
        ]);
    }
}
