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
        Schema::table('notifications', function (Blueprint $table): void {
            $table->dropColumn(['frequency_type', 'frequency_value', 'frequency_unit']);
        });

        Schema::table('notifications', function (Blueprint $table): void {
            $table->string('schedule_type')->default('every_day')->after('description');
            $table->json('week_days')->nullable()->after('schedule_type');
            $table->unsignedTinyInteger('every_n_days')->nullable()->after('week_days');
            $table->unsignedSmallInteger('cyclical_value')->nullable()->after('every_n_days');
            $table->string('cyclical_unit')->nullable()->after('cyclical_value');
            $table->json('times')->nullable()->after('cyclical_unit');
            $table->timestamp('ends_at')->nullable()->after('starts_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table): void {
            $table->dropColumn(['schedule_type', 'week_days', 'every_n_days', 'cyclical_value', 'cyclical_unit', 'times', 'ends_at']);
        });

        Schema::table('notifications', function (Blueprint $table): void {
            $table->string('frequency_type')->after('description');
            $table->integer('frequency_value')->nullable()->after('frequency_type');
            $table->string('frequency_unit')->nullable()->after('frequency_value');
        });
    }
};
