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
        Schema::table('email_messages', function (Blueprint $table) {
            $table->foreignId('email_template_id')->nullable()->after('api_key_id')
                ->constrained('email_templates')->nullOnDelete();
            $table->timestamp('scheduled_at')->nullable()->after('last_event_at')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('email_template_id');
            $table->dropColumn('scheduled_at');
        });
    }
};
