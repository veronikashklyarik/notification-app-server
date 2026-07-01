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
        Schema::table('notification_events', function (Blueprint $table): void {
            // When the most recent reminder push was sent (null = no reminder sent yet)
            $table->datetime('reminded_at')->nullable()->after('notified_at');
            // Composite index supports the reminder query: WHERE status = pending AND reminded_at <= cutoff
            $table->index(['status', 'reminded_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notification_events', function (Blueprint $table): void {
            $table->dropIndex(['status', 'reminded_at']);
            $table->dropColumn('reminded_at');
        });
    }
};
