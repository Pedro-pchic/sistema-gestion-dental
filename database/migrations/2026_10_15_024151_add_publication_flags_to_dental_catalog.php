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
        Schema::table('treatments', function (Blueprint $table): void {
            $table->boolean('is_public')->default(false);
            $table->boolean('show_public_price')->default(false);
        });

        Schema::table('dentists', function (Blueprint $table): void {
            $table->boolean('is_public')->default(false);
            $table->string('public_photo_path')->nullable();
            $table->text('public_bio')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dentists', function (Blueprint $table): void {
            $table->dropColumn(['is_public', 'public_photo_path', 'public_bio']);
        });

        Schema::table('treatments', function (Blueprint $table): void {
            $table->dropColumn(['is_public', 'show_public_price']);
        });
    }
};
