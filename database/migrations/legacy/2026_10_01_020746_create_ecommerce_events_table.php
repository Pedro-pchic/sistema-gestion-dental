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
        Schema::create('ecommerce_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_type', 32);
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->char('session_identifier', 64)->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index('product_id');
            $table->index(['event_type', 'occurred_at', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecommerce_events');
    }
};
