<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAvailabilityTimesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('availability_times', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('availability_id');
            $table->time('from');
            $table->time('to');
            $table->enum('status',['1','0'])->default('1')->comment('1-active,0-deleted');
            $table->timestamps();
            
            $table->foreign('availability_id')->references('id')->on('availabilities');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('availability_times');
    }
}
