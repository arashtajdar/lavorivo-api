<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('rules', function (Blueprint $table) {
            $table->foreignId('shop_id')
                ->nullable() // Allow null for global rules
                ->constrained('shops')
                ->onDelete('cascade')
                ->after('employee_id'); // Place after the employee_id column
        });
    }

    public function down()
    {
        Schema::table('rules', function (Blueprint $table) {
            $table->dropForeign(['shop_id']);
            $table->dropColumn('shop_id');
        });
    }
};
