<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketlistsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ticketlists', function(Blueprint $table) {
            $table->id();
            $table->string('ticket_id')->nullable();
            $table->string('subject')->nullable();
            $table->string('discription')->nullable();
            $table->integer('category_id');
            $table->unsignedBigInteger('user_id');
            $table->enum('status',['1','0'])->default('0')->comment('0-pending,1-complete');
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users');
            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ticketlistimages');
    }
}
