<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    public function userDetail(){
	    return $this->hasOne(User::class,'id','user_id')->select('id','name','user_name','profile_img');
	}
	public function lawyerDetails(){
	    return $this->hasOne(User::class,'id','lawyer_id')->select('id','first_name','last_name','user_name','profile_img');
	}
}
