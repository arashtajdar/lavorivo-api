<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Subscription name (Basic, Premium, Gold)
            $table->integer('category'); // 1 = Monthly, 2 = Annual
            $table->decimal('price', 8, 2); // Real price
            $table->decimal('discounted_price', 8, 2)->nullable(); // Discounted price
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('subscriptions');
    }
};
