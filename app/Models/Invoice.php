<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'user_id',
        'service_id',
        'invoice_number',
        'duration_month',
        'subtotal',
        'total',
        'status'
    ];
}
