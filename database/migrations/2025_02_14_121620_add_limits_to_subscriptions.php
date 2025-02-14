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
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->integer('maximum_shops')->default(1)->after('image'); // Maximum shops allowed
            $table->integer('maximum_employees')->default(5)->after('maximum_shops'); // Maximum employees allowed
        });
    }

    public function down()
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['maximum_shops', 'maximum_employees']);
        });
    }
};
