<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserManagerTable extends Migration
{
    public function up()
    {
        Schema::create('user_manager', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // User being managed
            $table->unsignedBigInteger('manager_id'); // Manager of the user
            $table->timestamps();

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('manager_id')->references('id')->on('users')->onDelete('cascade');

            // Unique constraint to avoid duplicate entries
            $table->unique(['user_id', 'manager_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_manager');
    }
}
