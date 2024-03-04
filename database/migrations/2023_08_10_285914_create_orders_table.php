<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('lawyer_id');
            $table->double('call_rate',8,2)->default('0');
            $table->time('call_end')->default('00:00:00');
            $table->time('call_start')->default('00:00:00');
            $table->time('call_initiate')->default('00:00:00');
            $table->string('duration')->nullable();
            $table->date('date')->nullable();
            $table->string('total_amount',8,2)->default('0');
            $table->enum('call_type',['1','0'])->default('1')->comment('1-call,0-chat');
            $table->enum('status',['1','0','2','3'])->default('1')->comment('1-accepted,0-pending,2-canceled,3-completed');
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('lawyer_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('orders');
    }
}
