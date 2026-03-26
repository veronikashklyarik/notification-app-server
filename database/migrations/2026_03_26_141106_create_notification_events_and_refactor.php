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
        Schema::create('notification_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('notification_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('scheduled_at');
            $table->string('status')->default('pending');
            $table->timestamp('postponed_until')->nullable();
            $table->json('postpone_history')->nullable();
            $table->text('comment')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['notification_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index('scheduled_at');
        });

        Schema::dropIfExists('notification_history');

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn('next_due_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->timestamp('next_due_at')->nullable()->after('ends_at');
        });

        Schema::create('notification_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_id')->nullable()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('action');
            $table->text('comment')->nullable();
            $table->timestamp('postponed_until')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamps();
        });

        Schema::dropIfExists('notification_events');
    }
};
