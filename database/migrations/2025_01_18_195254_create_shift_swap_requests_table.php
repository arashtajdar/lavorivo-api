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
        Schema::create('shift_swap_requests', function (Blueprint $table) {
            $table->id(); // Primary Key
            $table->unsignedBigInteger('shift_label_id'); // The shift label being swapped
            $table->date('shift_date'); // The date of the shift
            $table->unsignedBigInteger('requester_id'); // The employee requesting the swap
            $table->unsignedBigInteger('requested_id'); // The employee being requested
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending'); // Request status
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('shift_label_id')->references('id')->on('shift_labels')->onDelete('cascade');
            $table->foreign('requester_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('requested_id')->references('id')->on('users')->onDelete('cascade');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_swap_requests');
    }
};
