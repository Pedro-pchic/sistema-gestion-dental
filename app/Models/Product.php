<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['category_id', 'sku', 'name', 'description', 'price', 'is_active', 'image_url', 'image_public_id'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    #[Scope]
    protected function publiclyVisible(Builder $query): void
    {
        $query
            ->where('is_active', true)
            ->whereHas('category', fn (Builder $categoryQuery): Builder => $categoryQuery->where('is_active', true));
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function inventory(): HasOne
    {
        return $this->hasOne(Inventory::class);
    }

    public function purchaseDetails(): HasMany
    {
        return $this->hasMany(PurchaseDetail::class);
    }

    public function quoteDetails(): HasMany
    {
        return $this->hasMany(QuoteDetail::class);
    }

    public function orderDetails(): HasMany
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function saleDetails(): HasMany
    {
        return $this->hasMany(SaleDetail::class);
    }

    public function isAvailable(): bool
    {
        return ($this->inventory?->stock ?? 0) > 0;
    }

    public function isPurchasable(): bool
    {
        return $this->is_active
            && ($this->category?->is_active ?? false)
            && $this->isAvailable();
    }

    public function availabilityLabel(): string
    {
        if (! $this->isAvailable()) {
            return 'Agotado';
        }

        if (
            $this->inventory->minimum_stock > 0
            && $this->inventory->stock <= $this->inventory->minimum_stock
        ) {
            return 'Stock bajo';
        }

        return 'Disponible';
    }

    public function imageDeliveryUrl(int $width = 640): ?string
    {
        if (! is_string($this->image_url) || $this->image_url === '') {
            return null;
        }

        $host = parse_url($this->image_url, PHP_URL_HOST);
        $path = parse_url($this->image_url, PHP_URL_PATH);

        if (parse_url($this->image_url, PHP_URL_SCHEME) !== 'https'
            || ! is_string($host)
            || ! str_ends_with($host, '.cloudinary.com')
            || ! is_string($path)
            || ! str_contains($path, '/image/upload/')) {
            return null;
        }

        $width = max(1, min($width, 2000));

        return preg_replace(
            '~(/image/upload/)~',
            '$1f_auto,q_auto,w_'.$width.',c_fill/',
            $this->image_url,
            1,
        );
    }
}
