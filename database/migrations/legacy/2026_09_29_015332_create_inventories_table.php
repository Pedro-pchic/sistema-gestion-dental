<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->unique()->constrained()->restrictOnDelete();
            $table->integer('stock')->default(0);
            $table->integer('minimum_stock')->default(0);
            $table->timestamps();
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE inventories ADD CONSTRAINT inventories_stock_non_negative CHECK (stock >= 0)');
            DB::statement('ALTER TABLE inventories ADD CONSTRAINT inventories_minimum_stock_non_negative CHECK (minimum_stock >= 0)');
        }

        DB::table('products')
            ->select('id')
            ->orderBy('id')
            ->chunk(500, function ($products): void {
                $timestamp = now()->toDateTimeString();

                DB::table('inventories')->insert(
                    $products->map(fn (object $product): array => [
                        'product_id' => $product->id,
                        'stock' => 0,
                        'minimum_stock' => 0,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ])->all(),
                );
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
