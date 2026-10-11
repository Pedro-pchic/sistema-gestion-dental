<?php

namespace App\Models;

use App\Enums\EcommerceEventType;
use Database\Factories\EcommerceEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['event_type', 'product_id', 'session_identifier', 'order_id', 'occurred_at'])]
class EcommerceEvent extends Model
{
    /** @use HasFactory<EcommerceEventFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'event_type' => EcommerceEventType::class,
            'occurred_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
