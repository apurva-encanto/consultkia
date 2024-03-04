<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LawyerDetail extends Model
{
    use HasFactory;
    public function userDetails(){
        return $this->hasOne(User::class,'id','user_id');
    }
}
