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
        Schema::create('incomes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->enum('category', ['sales', 'other_income']);
            $table->date('date');
            $table->decimal('amount', 14, 2);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['date', 'id']);
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE incomes ADD CONSTRAINT incomes_amount_positive CHECK (amount > 0)');
        }

        DB::table('payments')
            ->select(['id', 'number', 'payment_date', 'amount', 'created_at', 'updated_at'])
            ->orderBy('id')
            ->chunkById(500, function ($payments): void {
                DB::table('incomes')->insert(
                    $payments->map(fn (object $payment): array => [
                        'payment_id' => $payment->id,
                        'category' => 'sales',
                        'date' => $payment->payment_date,
                        'amount' => $payment->amount,
                        'description' => "Pago {$payment->number}",
                        'created_at' => $payment->created_at,
                        'updated_at' => $payment->updated_at,
                    ])->all(),
                );
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incomes');
    }
};
