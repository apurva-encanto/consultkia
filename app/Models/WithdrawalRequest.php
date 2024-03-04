<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WithdrawalRequest extends Model
{
    use HasFactory;
    public function userDetails(){
    	return $this->hasOne(User::class,"id",'user_id')->select('id','user_name','name','profile_img');
    }
}
