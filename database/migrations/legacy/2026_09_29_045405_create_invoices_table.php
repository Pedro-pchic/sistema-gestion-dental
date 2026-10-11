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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->unique()->constrained()->restrictOnDelete();
            $table->string('number', 50)->unique();
            $table->date('issue_date');
            $table->enum('status', ['issued', 'partially_paid', 'paid', 'cancelled'])->default('issued');
            $table->decimal('subtotal', 14, 2);
            $table->decimal('total', 14, 2);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'issue_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
