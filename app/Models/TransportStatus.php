<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransportStatus extends BaseModel
{

    protected $table = 'transport_statuses';

    protected $fillable = [
        'transport_id',
        'status',
        'datetime'
    ];

    public function transports()
    {
        return $this->hasMany(Transport::class);
    }

}
