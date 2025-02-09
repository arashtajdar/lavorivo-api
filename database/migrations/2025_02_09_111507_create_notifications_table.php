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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Recipient of the notification
            $table->string('type'); // Type of notification (e.g., shift_swap, system_alert)
            $table->text('message'); // The notification content
            $table->json('data')->nullable(); // Any additional data (e.g., shift details)
            $table->boolean('is_read')->default(false); // Mark as read/unread
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
