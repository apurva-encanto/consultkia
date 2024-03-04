<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderReviewsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('description')->nullable();
            $table->double('rating',8,2)->default(0.00);
            $table->unsignedBigInteger('lawyer_id');
            $table->timestamps();
            $table->foreign('lawyer_id')->references('id')->on('users');             
            $table->foreign('order_id')->references('id')->on('orders'); 
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_reviews');
    }
}
