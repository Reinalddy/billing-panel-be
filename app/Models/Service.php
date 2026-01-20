<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = [
        'user_id',
        'service_name',
        'price',
        'start_date',
        'end_date',
        'status',
    ];
}
