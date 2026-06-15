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
        'paid_at',
        'driver_price',
        'driver_plate_number',
        'timocom_id',
        'raal_id',
        'weight',
        'ldm',
        'unloading',
        'loading',
        'loading_date',
        'visibility',
        'cargo_vehicle_plate',
        'cargo_vehicle_description',
        'cargo_week',
        'pdf_pin',
        'loadings_json',
        'unloadings_json',
        'distance',
        'price_per_km',
        'invoice_type',
        'company_id',
    ];

    protected $casts = [
        'loadings_json'  => 'array',
        'unloadings_json' => 'array',
    ];

    protected $dates = ['deleted_at', 'paid_at'];

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function company(){
        return $this->belongsTo(Company::class);
    }

    public function files(){
        return $this->morphMany(File::class, 'fileable');
    }

    /*
    public function getBillAttribute(){
        return $this->files()->where('type', 'bill')->first();
    }

    public function getDocsAttribute(){
        return $this->files()->where('type', 'docs')->first();
    }
    */

    public function getBillAttribute(){
        return $this->files()->where('type', 'bill')
                    ->orderBy('id', 'desc')
                    ->first();
    }

    public function getDocsAttribute(){
        return $this->files()->where('type', 'docs')
                    ->orderBy('id', 'desc')
                    ->first();
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

    public function getStatusNameAttribute(){
        $locale = in_array(strtolower(app()->getLocale()), ['sk', 'cz']) ? 'sk' : 'en';
        return $this->status?->{"name_$locale"};
    }

    public function getStatusSlugAttribute(){
        return $this->status?->slug;
    }

    public function getIsDeletedAttribute(){
        return $this->deleted_at !== null ? 1 : 0;
    }

    public function getTransIdAttribute(){
        return $this->id;
    }

    public function getIsPaidAttribute(): bool
    {
        return $this->paid_at !== null;
    }

    public function scopeVisible($query)
    {
        return $query
            // Zahrň aj soft-deleted záznamy (mazané transporty môžu byť stále relevantné)
            ->withTrashed()
            ->where(function ($q) {
                $q
                    // 1) FORCE VISIBLE: manuálne nastavená viditeľnosť na 'visible' → vždy zobraz
                    ->where('visibility', 'visible')
                    ->orWhere(function ($q) {
                        $q
                            // 2) AUTOMATICKÁ LOGIKA: len pre záznamy bez manuálneho overridu (visibility = null)
                            ->whereNull('visibility')
                            ->where(function ($inner) {
                                $inner
                                    // 2a) Zobraz max 3 mesiace od dátumu splatnosti
                                    ->where(function ($q) {
                                        $q->whereNotNull('due_date')
                                        ->whereDate('due_date', '>', now()->subMonths(3)->startOfDay());
                                    })
                                    // 2b) Chýbajúce dokumenty – zobraz ak loading_date nie je starší ako 2 mesiace.
                                    //     Po 2 mesiacoch od naloženia sa predpokladá že dokumenty už neprídu
                                    //     a transport zmizne zo zoznamu automaticky.
                                    ->orWhere(function ($q) {
                                        $q->where(function ($inner) {
                                            $inner->where('bill_sent', 0)->orWhere('docs_sent', 0);
                                        })
                                        ->whereDate('loading_date', '>', now()->subMonths(3)->startOfDay());
                                    })
                                    // 2c) Má priradený status – zobraz max 3 mesiace od vytvorenia
                                    ->orWhere(function ($q) {
                                        $q->whereNotNull('status_id')
                                        ->whereDate('created_at', '>', now()->subMonths(3)->startOfDay());
                                    });
                            });
                    });
            })
            // FORCE HIDDEN: vylúč záznamy s manuálne nastavenou neviditeľnosťou ('hidden').
            // Záznamy bez overridu (null) aj force visible prejdú.
            ->where(function ($q) {
                $q->whereNull('visibility')->orWhere('visibility', 'visible');
            });
    }
}