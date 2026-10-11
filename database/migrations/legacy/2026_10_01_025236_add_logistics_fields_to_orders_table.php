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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('logistics_status', 32)->nullable();
            $table->timestampTz('estimated_delivery_at')->nullable();
            $table->timestampTz('dispatched_at')->nullable();
            $table->timestampTz('delivered_at')->nullable();

            $table->index(['logistics_status', 'order_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['logistics_status', 'order_date']);
            $table->dropColumn([
                'logistics_status',
                'estimated_delivery_at',
                'dispatched_at',
                'delivered_at',
            ]);
        });
    }
};
