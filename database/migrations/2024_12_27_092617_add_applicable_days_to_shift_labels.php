<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddApplicableDaysToShiftLabels extends Migration
{
    public function up()
    {
        Schema::table('shift_labels', function (Blueprint $table) {
            $table->json('applicable_days')->nullable()->after('default_duration_minutes');
        });
    }

    public function down()
    {
        Schema::table('shift_labels', function (Blueprint $table) {
            $table->dropColumn('applicable_days');
        });
    }
}
