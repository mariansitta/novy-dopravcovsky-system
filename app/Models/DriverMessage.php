<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriverMessage extends Model
{
    protected $fillable = [
        'driver_id',
        'direction',
        'body',
        'read_at',
        'synced_at',
    ];

    protected $casts = [
        'read_at'   => 'datetime',
        'synced_at' => 'datetime',
    ];

    // Správa od dopravcu
    public function isFromDriver(): bool
    {
        return $this->direction === 'driver';
    }

    // Správa od Damaro
    public function isFromDamaro(): bool
    {
        return $this->direction === 'damaro';
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    // Všetky správy pre daného dopravcu (cez driver_id = User.driver_id)
    public function scopeForUser($query, $driverid)
    {
        return $query->where('driver_id', $driverid);
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }
}