<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Status extends BaseModel
{

    protected $fillable = [
        'name_sk',
        'name_en'
    ];

    public function transports()
    {
        return $this->hasMany(Transport::class);
    }

    public function getNameAttribute()
    {
        return $this->_translateProperty('name');
    }
}
