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
        Schema::table('notification_events', function (Blueprint $table) {
            $table->datetime('scheduled_at')->change();
            $table->datetime('postponed_until')->nullable()->change();
            $table->datetime('completed_at')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notification_events', function (Blueprint $table) {
            $table->timestamp('scheduled_at')->change();
            $table->timestamp('postponed_until')->nullable()->change();
            $table->timestamp('completed_at')->nullable()->change();
        });
    }
};
