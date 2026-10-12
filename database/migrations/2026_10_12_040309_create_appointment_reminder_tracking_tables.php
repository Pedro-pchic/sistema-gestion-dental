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
        Schema::table('patients', function (Blueprint $table): void {
            $table->boolean('email_reminders_enabled')->default(false);
            $table->timestampTz('email_reminders_consent_at')->nullable();
            $table->timestampTz('email_reminders_revoked_at')->nullable();
            $table->boolean('sms_reminders_enabled')->default(false);
            $table->timestampTz('sms_reminders_consent_at')->nullable();
            $table->timestampTz('sms_reminders_revoked_at')->nullable();
            $table->foreignId('notification_preferences_updated_by')->nullable()
                ->constrained('users')->nullOnDelete();
        });

        Schema::create('appointment_reminders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('channel', 16);
            $table->timestampTz('appointment_starts_at');
            $table->timestampTz('scheduled_at');
            $table->string('status', 20)->default('pending');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestampTz('next_attempt_at')->nullable();
            $table->timestampTz('queued_at')->nullable();
            $table->timestampTz('claimed_at')->nullable();
            $table->timestampTz('sent_at')->nullable();
            $table->string('last_error', 160)->nullable();
            $table->timestamps();

            $table->unique(
                ['appointment_id', 'patient_id', 'channel', 'appointment_starts_at'],
                'appointment_reminders_delivery_unique',
            );
            $table->index(['status', 'next_attempt_at']);
        });

        Schema::create('appointment_reminder_attempts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('appointment_reminder_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('attempt_number');
            $table->string('result', 16)->default('started');
            $table->timestampTz('attempted_at');
            $table->timestampTz('completed_at')->nullable();
            $table->string('error_type', 100)->nullable();
            $table->timestamps();

            $table->unique(['appointment_reminder_id', 'attempt_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_reminder_attempts');
        Schema::dropIfExists('appointment_reminders');

        Schema::table('patients', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('notification_preferences_updated_by');
            $table->dropColumn([
                'email_reminders_enabled',
                'email_reminders_consent_at',
                'email_reminders_revoked_at',
                'sms_reminders_enabled',
                'sms_reminders_consent_at',
                'sms_reminders_revoked_at',
            ]);
        });
    }
};
