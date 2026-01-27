<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailUnsubscribe extends BaseModel
{

    protected $fillable = [
        'hash',
    ];

}
