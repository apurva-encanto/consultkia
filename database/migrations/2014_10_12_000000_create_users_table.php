<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->string('user_name')->nullable();
            $table->string('badge_no')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('city')->nullable();
            $table->string('latitude')->nullable();
            $table->string('referrel_code')->nullable();
            $table->string('longitude')->nullable();
            $table->enum('gender',['1','2','0'])->default('1')->comment('1-male,2-female,0-others');
            $table->enum('user_type',['1','2','3'])->default('1')->comment('1-user,2-lawyer,3-admin');
            $table->enum('status',['1','2'])->default('1')->comment('1-active,2-inactive');
            $table->enum('is_loggedin',['1','0'])->default('1')->comment('1-login,0-logout');
            $table->enum('login_type',['1','2'])->default('1')->comment('1-email,2-google');
            $table->string('refrence_code')->nullable();
            $table->enum('is_verified',['1','0'])->default('0')->comment('1-verified,0-pending');
            $table->string('profile_img')->nullable();
            $table->string('otp_code')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->string('original_password')->nullable();
            $table->string('fcm_token')->nullable();
            $table->double('wallet_ballance',[8,2])->default(0.00);
            $table->enum('is_delete',['1','0'])->default('0')->comment('1-delete,0-no');
            $table->rememberToken()->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
