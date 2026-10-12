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
        Schema::create('treatment_plans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->foreignId('dentist_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 160);
            $table->text('description')->nullable();
            $table->string('status', 32)->default('pending');
            $table->timestamps();

            $table->index(['patient_id', 'status']);
            $table->index(['dentist_id', 'status']);
        });

        Schema::create('treatment_plan_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('treatment_plan_id')->constrained()->restrictOnDelete();
            $table->foreignId('treatment_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->decimal('estimated_unit_price', 10, 2);
            $table->string('status', 32)->default('pending');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['treatment_plan_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('treatment_plan_items');
        Schema::dropIfExists('treatment_plans');
    }
};
