<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstalledPlugin extends Model
{
    public $fillable = [
        "user_id",
        "plugin_id"
    ];
}
