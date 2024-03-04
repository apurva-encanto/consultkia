<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCallChatHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('call_chat_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lawyer_id');           
            $table->double('calls',8,2)->default(0);           
            $table->double('chats',8,2)->default(0);          
            $table->timestamps();
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
        Schema::dropIfExists('call_chat_histories');
    }
}
