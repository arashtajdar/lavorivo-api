<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusToUserOffDaysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_off_days', function (Blueprint $table) {
            $table->boolean('status')->after('reason')->default(false); // Add a boolean column with default value false (not approved)
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_off_days', function (Blueprint $table) {
            $table->dropColumn('status'); // Remove the status column
        });
    }
}
