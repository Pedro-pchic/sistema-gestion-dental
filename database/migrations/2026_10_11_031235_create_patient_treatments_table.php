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
        Schema::create('patient_treatments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->foreignId('dentist_id')->constrained()->restrictOnDelete();
            $table->foreignId('treatment_id')->constrained()->restrictOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('treatment_plan_item_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('registered_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('performed_at');
            $table->text('procedure_description');
            $table->text('clinical_notes')->nullable();
            $table->decimal('price_applied', 10, 2);
            $table->timestamps();
            $table->softDeletesTz();

            $table->unique(['appointment_id', 'treatment_id']);
            $table->unique(
                ['patient_id', 'dentist_id', 'treatment_id', 'performed_at'],
                'patient_treatments_unique_procedure_time',
            );
            $table->index(['patient_id', 'performed_at']);
            $table->index(['dentist_id', 'performed_at']);
            $table->index(['treatment_id', 'performed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_treatments');
    }
};
