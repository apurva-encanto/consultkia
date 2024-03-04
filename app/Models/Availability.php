<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Availability extends Model
{
    use HasFactory;

    public function availabilityTimes()
    {
        return $this->hasMany(AvailabilityTime::class);
    }
}
