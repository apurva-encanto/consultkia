<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AvailabilityTime extends Model
{
    use HasFactory;
    protected $fillable = ['from', 'to', 'availability_id']; // Add 'from' to the array

}
