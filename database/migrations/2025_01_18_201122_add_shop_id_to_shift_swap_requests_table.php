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
        Schema::table('shift_swap_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('shop_id')->after('id'); // Add shop_id column

            // Foreign key constraint
            $table->foreign('shop_id')
                ->references('id')
                ->on('shops')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shift_swap_requests', function (Blueprint $table) {
            //
        });
    }
};
