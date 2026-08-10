<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AiDocumentCheck;
use Illuminate\Http\Request;

class AiDocumentChecksController extends Controller
{
    // Vráti metriky AI kontrol, ktoré ešte neboli prenesené do Damaro (synced_at = null).
    // Damaro ich stiahne a uloží do svojej DB (raz denne, v noci).
    public function get()
    {
        $checks = AiDocumentCheck::unsynced()
            ->orderBy('id')
            ->get()
            ->map(fn (AiDocumentCheck $c) => [
                'id'                        => $c->id,
                'transport_id'              => $c->transport_id,
                'slot'                      => $c->slot,
                'original_filename'         => $c->original_filename,
                'model_used'                => $c->model_used,
                'escalated'                 => $c->escalated,
                'confidence'                => $c->confidence,
                'duration_ms'               => $c->duration_ms,
                'detected_document_type'    => $c->detected_document_type,
                'type_matches_slot'         => $c->type_matches_slot,
                'company_matches_expected'  => $c->company_matches_expected,
                'amount_matches_expected'   => $c->amount_matches_expected,
                'vat_matches_expected'      => $c->vat_matches_expected,
                'invoice_has_vat'           => $c->invoice_has_vat,
                'readable'                  => $c->readable,
                'carrier_name_found'        => $c->carrier_name_found,
                'invoice_amount'            => $c->invoice_amount,
                'invoice_currency'          => $c->invoice_currency,
                'invoice_billed_to'         => $c->invoice_billed_to,
                'invoice_billed_to_ico'     => $c->invoice_billed_to_ico,
                'invoice_billed_to_city'    => $c->invoice_billed_to_city,
                'invoice_billed_to_country' => $c->invoice_billed_to_country,
                'company_evidence'          => $c->company_evidence,
                'amount_evidence'           => $c->amount_evidence,
                'vat_evidence'              => $c->vat_evidence,
                'warnings_count'            => $c->warnings_count,
                'warning_codes'             => $c->warning_codes,
                'created_at'                => $c->created_at,
            ]);

        return response()->json(['checks' => $checks]);
    }

    // Označí metriky ako prenesené do Damaro.
    public function mark_synced(Request $request)
    {
        $ids = $request->input('ids', []);

        AiDocumentCheck::whereIn('id', $ids)
            ->whereNull('synced_at')
            ->update(['synced_at' => now()]);

        return response()->json(['ok' => true]);
    }
}
