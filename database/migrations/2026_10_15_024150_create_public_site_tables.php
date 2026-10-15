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
        Schema::create('public_site_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('clinic_name')->nullable();
            $table->text('description')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->text('opening_hours')->nullable();
            $table->time('booking_starts_at')->nullable();
            $table->time('booking_ends_at')->nullable();
            $table->json('booking_weekdays')->nullable();
            $table->string('facebook_url')->nullable();
            $table->string('instagram_url')->nullable();
            $table->string('hero_image_path')->nullable();
            $table->string('logo_path')->nullable();
            $table->timestamps();
        });

        Schema::create('announcements', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->string('image_path')->nullable();
            $table->date('published_on')->nullable();
            $table->date('expires_on')->nullable();
            $table->boolean('is_published')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['is_published', 'published_on', 'expires_on']);
        });

        Schema::create('appointment_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('treatment_id')->constrained()->restrictOnDelete();
            $table->foreignId('dentist_id')->constrained()->restrictOnDelete();
            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at');
            $table->string('name', 160);
            $table->string('phone', 40);
            $table->string('email');
            $table->string('status', 20)->default('pending');
            $table->string('duplicate_key', 64)->nullable()->unique();
            $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('review_note')->nullable();
            $table->timestamps();
            $table->index(['dentist_id', 'status', 'starts_at']);
        });

        Schema::create('appointment_request_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('appointment_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_type', 20);
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_request_events');
        Schema::dropIfExists('appointment_requests');
        Schema::dropIfExists('announcements');
        Schema::dropIfExists('public_site_settings');
    }
};
