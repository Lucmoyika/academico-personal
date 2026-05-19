<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table): void {
            $table->index('campus_id');
            $table->index('rhythm_id');
            $table->index('level_id');
            $table->index('period_id');
            $table->index('room_id');
            $table->index('teacher_id');
            $table->index('start_date');
        });

        Schema::table('enrollments', function (Blueprint $table): void {
            $table->index('status_id');
            $table->index('responsible_id');
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->index('invoice_id');
            $table->index('responsable_id');
            $table->index('payment_method');
        });

        Schema::table('grades', function (Blueprint $table): void {
            $table->index('grade_type_id');
            $table->index('enrollment_id');
        });
    }

    public function down(): void
    {
        Schema::table('grades', function (Blueprint $table): void {
            $table->dropIndex(['grade_type_id']);
            $table->dropIndex(['enrollment_id']);
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->dropIndex(['invoice_id']);
            $table->dropIndex(['responsable_id']);
            $table->dropIndex(['payment_method']);
        });

        Schema::table('enrollments', function (Blueprint $table): void {
            $table->dropIndex(['status_id']);
            $table->dropIndex(['responsible_id']);
        });

        Schema::table('courses', function (Blueprint $table): void {
            $table->dropIndex(['campus_id']);
            $table->dropIndex(['rhythm_id']);
            $table->dropIndex(['level_id']);
            $table->dropIndex(['period_id']);
            $table->dropIndex(['room_id']);
            $table->dropIndex(['teacher_id']);
            $table->dropIndex(['start_date']);
        });
    }
};
