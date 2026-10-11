<?php

namespace App\Models;

use App\Enums\QuoteStatus;
use Database\Factories\QuoteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

#[Fillable(['customer_id', 'number', 'status', 'quote_date', 'valid_until', 'notes', 'total'])]
class Quote extends Model
{
    public const int MAX_TOTAL_CENTS = 99_999_999_999_999;

    /** @use HasFactory<QuoteFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => QuoteStatus::class,
            'quote_date' => 'date',
            'valid_until' => 'date',
            'total' => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(QuoteDetail::class);
    }

    public function order(): HasOne
    {
        return $this->hasOne(Order::class);
    }

    public function isEditable(): bool
    {
        return $this->status === QuoteStatus::Draft;
    }

    public function transitionTo(QuoteStatus $status): void
    {
        DB::transaction(function () use ($status): void {
            $quote = self::query()
                ->whereKey($this->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $allowedTransitions = match ($quote->status) {
                QuoteStatus::Draft => [QuoteStatus::Sent, QuoteStatus::Rejected],
                QuoteStatus::Sent => [QuoteStatus::Accepted, QuoteStatus::Rejected],
                default => [],
            };

            if (! in_array($status, $allowedTransitions, true)) {
                throw ValidationException::withMessages([
                    'status' => 'El estado actual de la cotización no permite esta acción.',
                ]);
            }

            if ($status === QuoteStatus::Sent) {
                $customer = Customer::query()
                    ->whereKey($quote->customer_id)
                    ->lockForUpdate()
                    ->first();

                if (! $customer?->is_active) {
                    throw ValidationException::withMessages([
                        'customer_id' => 'La cotización debe pertenecer a un cliente activo para enviarse.',
                    ]);
                }

                if (! $quote->details()->exists()) {
                    throw ValidationException::withMessages([
                        'details' => 'La cotización debe contener al menos un producto.',
                    ]);
                }
            }

            $quote->update(['status' => $status]);
        });
    }
}
