<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    public function lawyerDetails(){
    	return $this->hasOne(User::class,"id",'to')->select('id','first_name','last_name','profile_img');
    }
    public function userDetails(){
    	return $this->hasOne(User::class,"id",'from')->select('id','user_name','name','profile_img');
    }


    
}
