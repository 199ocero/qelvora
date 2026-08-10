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
        Schema::table('api_keys', function (Blueprint $table) {
            // When set, the key may only send from this verified identity.
            // Null means the key can send from any of the team's identities.
            $table->foreignId('mail_identity_id')
                ->nullable()
                ->after('team_id')
                ->constrained('mail_identities')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('api_keys', function (Blueprint $table) {
            $table->dropConstrainedForeignId('mail_identity_id');
        });
    }
};
