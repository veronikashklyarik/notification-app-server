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
        Schema::create('app_versions', function (Blueprint $table) {
            $table->id();
            $table->string('platform', 10);
            $table->string('minimum_version', 20);
            $table->string('recommended_version', 20);
            $table->string('latest_version', 20);
            $table->boolean('force_update')->default(false);
            $table->text('message')->nullable();
            $table->string('download_url')->nullable();
            $table->datetime('created_at')->nullable();
            $table->datetime('updated_at')->nullable();

            $table->unique('platform');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_versions');
    }
};
