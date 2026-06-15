<?php

namespace App\Models;

class Company extends BaseModel
{
    protected $table = 'companies';

    public $incrementing = false; // id prichádza z Damara (upsert podľa id)

    protected $keyType = 'int';

    protected $fillable = [
        'id',
        'name',
        'address',
        'city',
        'postal_code',
        'country',
        'ico',
        'dic',
        'icdph',
        'bank_name',
        'swift',
        'iban',
    ];

    public function transports()
    {
        return $this->hasMany(Transport::class);
    }
}
