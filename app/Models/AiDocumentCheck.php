<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiDocumentCheck extends Model
{
    protected $fillable = [
        'transport_id',
        'slot',
        'original_filename',
        'model_used',
        'escalated',
        'confidence',
        'duration_ms',
        'detected_document_type',
        'type_matches_slot',
        'company_matches_expected',
        'amount_matches_expected',
        'vat_matches_expected',
        'invoice_has_vat',
        'readable',
        'carrier_name_found',
        'invoice_amount',
        'invoice_currency',
        'invoice_billed_to',
        'invoice_billed_to_ico',
        'invoice_billed_to_city',
        'invoice_billed_to_country',
        'company_evidence',
        'amount_evidence',
        'vat_evidence',
        'warnings_count',
        'warning_codes',
        'synced_at',
    ];

    protected $casts = [
        'escalated'          => 'boolean',
        'confidence'         => 'float',
        'type_matches_slot'  => 'boolean',
        'readable'           => 'boolean',
        'carrier_name_found' => 'boolean',
        'invoice_amount'     => 'float',
        'warning_codes'      => 'array',
        'synced_at'          => 'datetime',
    ];

    public function scopeUnsynced($query)
    {
        return $query->whereNull('synced_at');
    }
}
