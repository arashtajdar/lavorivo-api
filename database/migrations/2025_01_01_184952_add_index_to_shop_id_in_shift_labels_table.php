<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexToShopIdInShiftLabelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shift_labels', function (Blueprint $table) {
            // Adding an index to the 'shop_id' column
            $table->index('shop_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shift_labels', function (Blueprint $table) {
            // Dropping the index from the 'shop_id' column
            $table->dropIndex(['shop_id']);
        });
    }
}
