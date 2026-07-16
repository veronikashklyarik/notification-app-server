<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table): void {
            $table->unsignedSmallInteger('reminder_interval')->nullable()->after('is_active');
        });

        // Copy each user's global interval to all their existing notifications.
        DB::statement('
            UPDATE notifications
            SET reminder_interval = (
                SELECT reminder_interval FROM users WHERE users.id = notifications.user_id
            )
            WHERE deleted_at IS NULL
              AND EXISTS (
                SELECT 1 FROM users
                WHERE users.id = notifications.user_id
                  AND users.reminder_interval IS NOT NULL
              )
        ');
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table): void {
            $table->dropColumn('reminder_interval');
        });
    }
};
