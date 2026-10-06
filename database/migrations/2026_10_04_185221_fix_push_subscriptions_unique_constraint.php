<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The original migration file was edited after it had already run in production/dev —
     * from a single-column unique on `endpoint` to a composite unique on `endpoint` +
     * `user_id` — but that edit never got applied to any database that already had the
     * old index, since editing a historical migration doesn't retroactively re-run it.
     * This migration brings the real schema in line with what the model/controller have
     * always assumed: multiple users can share the same browser/device endpoint.
     */
    public function up(): void
    {
        // Databases migrated before the original migration file was corrected still
        // carry the stale single-column index; fresh databases (e.g. the SQLite test
        // suite, migrated from the already-fixed file) never had it. Only touch it if
        // it's actually there.
        if (Schema::hasIndex('push_subscriptions', 'push_subscriptions_endpoint_unique')) {
            Schema::table('push_subscriptions', function (Blueprint $table): void {
                $table->dropUnique('push_subscriptions_endpoint_unique');
            });
        }

        if (! Schema::hasIndex('push_subscriptions', 'push_subscriptions_endpoint_user_id_unique')) {
            Schema::table('push_subscriptions', function (Blueprint $table): void {
                $table->unique(['endpoint', 'user_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasIndex('push_subscriptions', 'push_subscriptions_endpoint_user_id_unique')) {
            Schema::table('push_subscriptions', function (Blueprint $table): void {
                $table->dropUnique(['endpoint', 'user_id']);
            });
        }

        if (! Schema::hasIndex('push_subscriptions', 'push_subscriptions_endpoint_unique')) {
            Schema::table('push_subscriptions', function (Blueprint $table): void {
                $table->unique('endpoint');
            });
        }
    }
};
