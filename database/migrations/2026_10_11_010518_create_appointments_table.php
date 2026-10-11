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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->foreignId('dentist_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at');
            $table->string('planned_treatment', 255);
            $table->text('administrative_notes')->nullable();
            $table->string('status', 32)->default('scheduled');
            $table->timestampTz('confirmed_at')->nullable();
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->timestampTz('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('cancellation_reason')->nullable();
            $table->timestampTz('no_show_at')->nullable();
            $table->timestamps();

            $table->index(['dentist_id', 'starts_at']);
            $table->index(['patient_id', 'starts_at']);
            $table->index(['status', 'starts_at']);
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist');
            DB::statement('ALTER TABLE appointments ADD CONSTRAINT appointments_valid_interval CHECK (ends_at > starts_at)');
            DB::statement("ALTER TABLE appointments ADD CONSTRAINT appointments_valid_status CHECK (status IN ('scheduled', 'confirmed', 'in_treatment', 'completed', 'cancelled', 'no_show'))");
            DB::statement("ALTER TABLE appointments ADD CONSTRAINT appointments_dentist_no_overlapping_reservations EXCLUDE USING gist (dentist_id WITH =, tstzrange(starts_at, ends_at, '[)') WITH &&) WHERE (status IN ('scheduled', 'confirmed', 'in_treatment'))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
