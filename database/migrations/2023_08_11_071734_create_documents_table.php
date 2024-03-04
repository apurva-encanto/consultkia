<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDocumentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_detail_id');
            $table->string('file')->nullable();
            $table->string('license_no')->nullable();
            $table->string('pan_no')->nullable();
            $table->enum('doc_type',['1','2','0'])->default('0')->comment('1-license,2-pan,0-none');
            $table->timestamps();
            $table->foreign('user_detail_id')->references('id')->on('lawyer_details');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('documents');
    }
}
