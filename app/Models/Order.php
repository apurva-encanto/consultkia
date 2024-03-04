<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;


    public function userDetails(){
    	return $this->hasOne(User::class,'id','user_id')->select('id','user_name','city','phone','gender','profile_img');
    }


    public function lawyerDetails(){
    	return $this->hasOne(User::class,'id','lawyer_id')->select('id','user_name','first_name','last_name','city','phone','profile_img');
    }
}
