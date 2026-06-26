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
        Schema::table('users', function (Blueprint $table) {
            $table->datetime('email_verified_at')->nullable()->change();
            $table->datetime('created_at')->nullable()->change();
            $table->datetime('updated_at')->nullable()->change();
            $table->datetime('deleted_at')->nullable()->change();
        });

        Schema::table('password_reset_tokens', function (Blueprint $table) {
            $table->datetime('created_at')->nullable()->change();
        });

        Schema::table('failed_jobs', function (Blueprint $table) {
            $table->datetime('failed_at')->useCurrent()->change();
        });

        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->datetime('last_used_at')->nullable()->change();
            $table->datetime('expires_at')->nullable()->change();
            $table->datetime('created_at')->nullable()->change();
            $table->datetime('updated_at')->nullable()->change();
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->datetime('created_at')->nullable()->change();
            $table->datetime('updated_at')->nullable()->change();
            $table->datetime('deleted_at')->nullable()->change();
        });

        Schema::table('notification_events', function (Blueprint $table) {
            $table->datetime('created_at')->nullable()->change();
            $table->datetime('updated_at')->nullable()->change();
        });

        Schema::table('app_versions', function (Blueprint $table) {
            $table->datetime('created_at')->nullable()->change();
            $table->datetime('updated_at')->nullable()->change();
        });

        Schema::table('device_tokens', function (Blueprint $table) {
            $table->datetime('created_at')->nullable()->change();
            $table->datetime('updated_at')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('email_verified_at')->nullable()->change();
            $table->timestamp('created_at')->nullable()->change();
            $table->timestamp('updated_at')->nullable()->change();
            $table->timestamp('deleted_at')->nullable()->change();
        });

        Schema::table('password_reset_tokens', function (Blueprint $table) {
            $table->timestamp('created_at')->nullable()->change();
        });

        Schema::table('failed_jobs', function (Blueprint $table) {
            $table->timestamp('failed_at')->useCurrent()->change();
        });

        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->timestamp('last_used_at')->nullable()->change();
            $table->timestamp('expires_at')->nullable()->change();
            $table->timestamp('created_at')->nullable()->change();
            $table->timestamp('updated_at')->nullable()->change();
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->timestamp('created_at')->nullable()->change();
            $table->timestamp('updated_at')->nullable()->change();
            $table->timestamp('deleted_at')->nullable()->change();
        });

        Schema::table('notification_events', function (Blueprint $table) {
            $table->timestamp('created_at')->nullable()->change();
            $table->timestamp('updated_at')->nullable()->change();
        });

        Schema::table('app_versions', function (Blueprint $table) {
            $table->timestamp('created_at')->nullable()->change();
            $table->timestamp('updated_at')->nullable()->change();
        });

        Schema::table('device_tokens', function (Blueprint $table) {
            $table->timestamp('created_at')->nullable()->change();
            $table->timestamp('updated_at')->nullable()->change();
        });
    }
};
