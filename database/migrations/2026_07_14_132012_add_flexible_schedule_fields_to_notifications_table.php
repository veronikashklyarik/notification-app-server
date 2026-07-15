<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table): void {
            $table->json('specific_dates')->nullable()->after('week_days');
            $table->json('cyclical_week_days')->nullable()->after('cyclical_unit');
            $table->string('cyclical_month_type')->nullable()->after('cyclical_week_days');
            $table->unsignedTinyInteger('cyclical_month_day')->nullable()->after('cyclical_month_type');
            $table->string('cyclical_month_position')->nullable()->after('cyclical_month_day');
            $table->unsignedTinyInteger('cyclical_month_weekday')->nullable()->after('cyclical_month_position');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table): void {
            $table->dropColumn([
                'specific_dates',
                'cyclical_week_days',
                'cyclical_month_type',
                'cyclical_month_day',
                'cyclical_month_position',
                'cyclical_month_weekday',
            ]);
        });
    }
};
