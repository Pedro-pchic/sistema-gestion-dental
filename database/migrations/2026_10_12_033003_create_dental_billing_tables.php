<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('dental_invoices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->string('number', 50)->nullable()->unique();
            $table->string('status', 24)->default('draft');
            $table->date('issue_date')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('balance_due', 12, 2)->default(0);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();

            $table->index(['status', 'issue_date']);
            $table->index(['patient_id', 'status']);
        });

        Schema::create('dental_invoice_details', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('dental_invoice_id')->constrained('dental_invoices')->restrictOnDelete();
            $table->foreignId('patient_treatment_id')->constrained('patient_treatments')->restrictOnDelete();
            $table->string('description', 180);
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();

            $table->index(['patient_treatment_id', 'dental_invoice_id']);
        });

        Schema::create('dental_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('dental_invoice_id')->constrained('dental_invoices')->restrictOnDelete();
            $table->string('number', 50)->unique();
            $table->uuid('idempotency_key')->unique();
            $table->date('payment_date');
            $table->decimal('amount', 12, 2);
            $table->string('method', 24);
            $table->string('reference', 120)->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 24)->default('registered');
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();

            $table->index(['dental_invoice_id', 'payment_date']);
            $table->index(['status', 'payment_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dental_payments');
        Schema::dropIfExists('dental_invoice_details');
        Schema::dropIfExists('dental_invoices');
    }
};
