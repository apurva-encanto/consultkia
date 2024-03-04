<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->nullable();
            $table->string('txn_id')->nullable();
            $table->dateTime('dateTime')->default(now());
            $table->unsignedBigInteger('end_by')->default(1);
            $table->unsignedBigInteger('from');
            $table->unsignedBigInteger('to');
            $table->enum('sender_type',['1','2','3','4'])->default('1')->comment('1-refrenceEarning,2-paidForOrder,3-recievedForOrder,4-addToWallet,5-withdrawal');
            $table->enum('reciever_type',['1','2','3','4'])->default('1')->comment('1-refrenceEarning,2-paidForOrder,3-recievedForOrder,4-addToWallet,5-withdrawal');
            $table->enum('type',['1','0'])->default('1')->comment('1-cr,0-dr');
            $table->double('amount',8,2)->default(0.00);
            $table->string('transaction_method')->nullable();
            $table->enum('status',['1','0','2'])->default('1')->comment('0-pending,1-approved,2-failed');
            $table->timestamps();
            $table->foreign('to')->references('id')->on('users')->onDelete('cascade'); 
            $table->foreign('from')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transactions');
    }
}
