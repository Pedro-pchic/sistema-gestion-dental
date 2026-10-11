<?php

namespace App\Models;

use App\Enums\CustomerInquiryStatus;
use Database\Factories\CustomerInquiryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['customer_id', 'name', 'email', 'subject', 'message', 'status'])]
class CustomerInquiry extends Model
{
    /** @use HasFactory<CustomerInquiryFactory> */
    use HasFactory;

    protected $attributes = [
        'status' => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'status' => CustomerInquiryStatus::class,
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
