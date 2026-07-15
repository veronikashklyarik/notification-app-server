<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table): void {
            // Replace single day (tinyint) with multi-day array (json)
            $table->dropColumn('cyclical_month_day');
            $table->json('cyclical_month_days')->nullable()->after('cyclical_month_type');

            // Yearly: selected months array
            $table->json('cyclical_year_months')->nullable()->after('cyclical_month_weekday');

            // Yearly: toggle to use Nth-weekday-of-month instead of fixed day
            $table->boolean('cyclical_year_use_weekday')->nullable()->default(false)->after('cyclical_year_months');

            // Daily cyclical pause cycle (use for N days, pause for M days)
            $table->unsignedSmallInteger('cyclical_use_for')->nullable()->after('cyclical_year_use_weekday');
            $table->unsignedSmallInteger('cyclical_pause_for')->nullable()->after('cyclical_use_for');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table): void {
            $table->dropColumn(['cyclical_month_days', 'cyclical_year_months', 'cyclical_year_use_weekday', 'cyclical_use_for', 'cyclical_pause_for']);
            $table->unsignedTinyInteger('cyclical_month_day')->nullable()->after('cyclical_month_type');
        });
    }
};
