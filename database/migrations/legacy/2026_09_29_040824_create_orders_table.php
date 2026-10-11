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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('quote_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->string('number')->unique();
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed'])->default('pending');
            $table->date('order_date');
            $table->text('notes')->nullable();
            $table->decimal('total', 14, 2)->default(0);
            $table->timestamps();

            $table->index(['customer_id', 'status', 'order_date']);
            $table->index(['order_date', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
