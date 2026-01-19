<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductDuration extends Model
{
    protected $fillable = [
        'product_id',
        'duration_month',
        'price_per_month',
        'is_active',
    ];
}
