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
        Schema::table('shift_labels', function (Blueprint $table) {
            $table->boolean('is_active')
                ->after('applicable_days')
                ->default(true);
        });
    }

    public function down()
    {
        Schema::table('shift_labels', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }

};
