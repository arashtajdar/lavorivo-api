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
        Schema::table('user_creation_requests', function (Blueprint $table) {
            $table->unique(['email', 'requested_by'], 'unique_email_requested_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_creation_requests', function (Blueprint $table) {
            $table->dropUnique('unique_email_requested_by');
        });
    }
};
