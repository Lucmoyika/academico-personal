<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add columns only if they don't already exist (make migration idempotent)
        if (! Schema::hasColumn('media', 'conversions_disk') || ! Schema::hasColumn('media', 'uuid')) {
            Schema::table('media', function (Blueprint $table) {
                if (! Schema::hasColumn('media', 'conversions_disk')) {
                    $table->string('conversions_disk')->nullable();
                }

                if (! Schema::hasColumn('media', 'uuid')) {
                    // Use a 36-char string for UUID to avoid DB engines that don't support the `uuid` column type
                    $table->string('uuid', 36)->nullable();
                }
            });

            // Ensure existing media rows have a conversions_disk value
            DB::statement("UPDATE media SET conversions_disk = 'disk' WHERE conversions_disk IS NULL;");

            // Update existing media rows with generated UUIDs where missing
            Media::whereNull('uuid')->cursor()->each(function (Media $media) {
                $media->update(['uuid' => Str::uuid()->toString()]);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
