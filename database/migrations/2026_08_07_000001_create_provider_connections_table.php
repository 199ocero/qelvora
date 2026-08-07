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
        Schema::create('provider_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('provider');
            $table->text('credentials')->nullable();
            $table->json('settings')->nullable();
            $table->string('status')->default('pending');
            $table->boolean('is_active')->default(false);
            $table->string('webhook_token', 64)->unique();

            // Cached account health, refreshed on connect/sync.
            $table->boolean('production_access')->default(false);
            $table->decimal('send_quota_max', 12, 2)->nullable();
            $table->decimal('sent_last_24h', 12, 2)->nullable();
            $table->decimal('max_send_rate', 10, 2)->nullable();
            $table->string('enforcement_status')->nullable();
            $table->decimal('bounce_rate', 6, 4)->nullable();
            $table->decimal('complaint_rate', 6, 4)->nullable();

            $table->timestamp('connected_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['team_id', 'provider']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provider_connections');
    }
};
