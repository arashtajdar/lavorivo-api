<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('shop_user', function (Blueprint $table) {
            $table->unsignedTinyInteger('role')->default(3)->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('shop_user', function (Blueprint $table) {
            $table->dropColumn('role'); // Remove the role column if rolled back
        });
    }
};
