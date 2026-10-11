<?php

namespace App\Models;

use App\Enums\CreditNoteStatus;
use App\Enums\InvoiceStatus;
use App\Support\Money;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['sale_id', 'number', 'issue_date', 'status', 'subtotal', 'total', 'notes'])]
class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'status' => InvoiceStatus::class,
            'subtotal' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function creditNotes(): HasMany
    {
        return $this->hasMany(CreditNote::class);
    }

    public function balanceDueCents(): int
    {
        if ($this->status === InvoiceStatus::Cancelled) {
            return 0;
        }

        return max(0, Money::toCents($this->total) - $this->paymentTotalCents() - $this->creditedTotalCents());
    }

    public function availableAmountCents(): int
    {
        return $this->balanceDueCents();
    }

    public function synchronizeStatus(): void
    {
        if ($this->status === InvoiceStatus::Cancelled) {
            return;
        }

        $paymentTotalCents = $this->paymentTotalCents();
        $creditedTotalCents = $this->creditedTotalCents();
        $balanceDueCents = Money::toCents($this->total) - $paymentTotalCents - $creditedTotalCents;

        $status = match (true) {
            $balanceDueCents <= 0 => InvoiceStatus::Paid,
            $paymentTotalCents > 0 || $creditedTotalCents > 0 => InvoiceStatus::PartiallyPaid,
            default => InvoiceStatus::Issued,
        };

        if ($this->status !== $status) {
            $this->update(['status' => $status]);
        }
    }

    private function paymentTotalCents(): int
    {
        return $this->payments()->get(['amount'])->sum(
            fn (Payment $payment): int => Money::toCents($payment->amount),
        );
    }

    private function creditedTotalCents(): int
    {
        return $this->creditNotes()
            ->where('status', CreditNoteStatus::Issued->value)
            ->get(['amount'])
            ->sum(fn (CreditNote $creditNote): int => Money::toCents($creditNote->amount));
    }
}
