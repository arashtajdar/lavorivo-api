<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateStatusColumnInShiftSwapRequestsTable extends Migration
{
    public function up()
    {
        Schema::table('shift_swap_requests', function (Blueprint $table) {
            // Change the `status` column from ENUM to INT
            $table->unsignedTinyInteger('status')->default(0)->comment('0: Pending, 1: Approved, 2: Rejected')->change();
        });
    }

    public function down()
    {
        Schema::table('shift_swap_requests', function (Blueprint $table) {
            // Revert the `status` column back to ENUM (if needed)
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->change();
        });
    }
}
