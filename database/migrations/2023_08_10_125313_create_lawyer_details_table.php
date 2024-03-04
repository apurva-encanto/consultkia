<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLawyerDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lawyer_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('practice_area')->nullable();
            $table->string('practice_state')->nullable();
            $table->string('practice_court')->nullable();
            $table->string('education_details')->nullable();
            $table->string('experience');
            $table->enum('availability',['1','0'])->default('1')->comment('1-repeat weekly,0-for current week');
            $table->text('description')->nullable();
            $table->string('language');
            $table->integer('is_chat')->default('0')->comment('1-available,0-unavailable');
            $table->integer('is_call')->default('0')->comment('1-available,0-unavailable');
            $table->double('call_charge',8, 2)->default(0);
            $table->double('chat_charge', 8,2)->default(0);
            $table->string("docs_img")->nullable();
            $table->string('ac_no');
            $table->string("ifsc");
            $table->string("account_name")->nullable();
            $table->string("bank_name")->nullable();
            $table->enum("is_available",['1','0'])->default('1')->comment('1-available,0-unavailable');
            $table->enum("isPremium",['1','0'])->default('0')->comment('1-premium,0-nonpremium');
            $table->enum("is_adminVerified",['1','0'])->default('0')->comment('1-verified,0-pending,2-rejectetd');
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
        Schema::dropIfExists('lawyer_details');
        
    }
}
