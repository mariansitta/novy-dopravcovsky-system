<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Transport extends BaseModel
{
    use SoftDeletes;

    protected $fillable = [
        'number',
        'transport_id',
        'driver_notice',
        'bill_file',
        'docs_file',
        'docs_sent',
        'bill_sent',
        'due_days',
        'due_date',
        'status_id',
        'driver_price',
        'driver_plate_number',
        'timocom_id',
        'raal_id',
        'weight',
        'ldm',
        'unloading',
        'loading',
        'loading_date',
    ];

    protected $dates = ['deleted_at'];

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function files(){
        return $this->morphMany(File::class, 'fileable');
    }

    public function getBillAttribute(){
        return $this->files()->where('type', 'bill')->first();
    }

    public function getDocsAttribute(){
        return $this->files()->where('type', 'docs')->first();
    }

    public function status() {
        return $this->belongsTo(Status::class);
    }

    public function transport_statuses() {
        return $this->hasMany(TransportStatus::class);
    }

    public function transport_status() {
        return $this->hasOne(TransportStatus::class)->latest();
    }
}
