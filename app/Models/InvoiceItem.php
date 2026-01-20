<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id',
        'description',
        'unit_price',
        'quantity',
        'total_price'
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
