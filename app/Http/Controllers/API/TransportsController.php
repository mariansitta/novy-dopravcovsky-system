<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\DocumentsGetRequest;
use App\Http\Requests\TransportDataRequest;
use App\Http\Requests\DeleteFileRequest;
use App\Models\Status;
use App\Models\Transport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TransportsController extends Controller
{
    // Controller for retrieving uploaded documents from drivers system

    // Get transports with both files uploaded
    /*
    public function get(){
        $transport_ids = Transport::whereNotNull('transport_id')
            ->whereHas('files', function ($q){
                $q->where('type', 'bill');
            })->whereHas('files', function ($q){
                $q->where('type', 'docs');
            })->where('status_id', Status::where('slug', 'uploaded')->first()->id )->get()->pluck('transport_id');

        return response()->json([
            'transports' => $transport_ids
        ], 200);
    }
    */
    public function get(){
        $transport_ids = Transport::whereNotNull('transport_id')
            ->whereHas('files', function ($q){
                $q->where('type', 'bill');
            })->orWhereHas('files', function ($q){
                $q->where('type', 'docs');
            })->where('status_id', Status::where('slug', 'uploaded')->first()->id )->get()->pluck('transport_id');

        return response()->json([
            'transports' => $transport_ids
        ], 200);
    }

    // Check if transport has uploaded documents
    public function exists(Request $request)
    {
        /*
        Log::info('Checking existence for transport', ['transport_id' => $request->id]);
        */
    
        $transport = Transport::where('transport_id', $request->id)->first();
    
        if (!$transport) {
            Log::warning('Transport not found', ['transport_id' => $request->id]);
            return response(['exists' => false], 200);
        }
    
        $billExists = isset($transport->bill) && is_readable($transport->bill->get_path());
        $docsExists = isset($transport->docs) && is_readable($transport->docs->get_path());
    
        $notifyEmail = $transport->user?->notify_email;

        /*
        Log::info('Transport found', [
            'transport_id' => $request->id,
            'bill_exists' => $billExists,
            'docs_exists' => $docsExists,
            'bill_sent' => $transport->bill_sent ?? null,
            'docs_sent' => $transport->docs_sent ?? null,
            'due_date' => $transport->due_date,
            'notify_email' => $notifyEmail,
        ]);
        */
    
        return response([
            'exists' => $billExists || $docsExists,
            'bill' => $billExists && !$transport->bill_sent,
            'docs' => $docsExists && !$transport->docs_sent,
            'due_date' => $transport->due_date,
            'notify_email' => $notifyEmail,
        ], 200);
    }
    /*
    public function exists(Request $request){
        $transport = Transport::where('transport_id', $request->id)->first();
    
        return response([
            'exists' =>
                isset($transport)
                && (isset($transport->bill) && is_readable($transport->bill->get_path()) || isset($transport->docs) && is_readable($transport->docs->get_path())),
            'bill' => isset($transport)
                && (isset($transport->bill) && !($transport->bill_sent) && is_readable($transport->bill->get_path())),
            'docs' => isset($transport)
                && (isset($transport->docs) && !($transport->docs_sent) && is_readable($transport->docs->get_path())),
            'due_date' => optional($transport)->due_date,
        ], 200);
    }
    */

    // Get specific file of transport
    public function file(DocumentsGetRequest $request){
        $transport = Transport::where('transport_id', $request->id)->firstOrFail();

        return response()->download($transport->{$request->file}->get_path());
    }

    // Update transport file_sent
    public function success(DocumentsGetRequest $request){
        $transport = Transport::where('transport_id', $request->id)->firstOrFail();

        $transport->update([
            $request->file . '_sent' => 1,
            $request->file . '_file' => $request->filename,
        ]);

        return response([], 200);
    }

    // Get due date of transport
    public function data(TransportDataRequest $request){
        $transport = Transport::where('transport_id', $request->id)->firstOrFail();

        return response([
            'due_date' => $transport->due_date,
        ], 200);
    }

    // Update transport status to uploaded (pending) after files are downloaded by Damaro
    public function status(TransportDataRequest $request){
        $transport = Transport::where('transport_id', $request->id)->firstOrFail();

        if($transport->bill_sent && $transport->docs_sent){
            $uploadedStatus = Status::where('slug', 'uploaded')->first();
            if ($transport->status_id !== $uploadedStatus->id) {
                $transport->status_id = $uploadedStatus->id;
                $transport->save();
            }
        }

        return response([], 200);
    }

    // Get all transport_ids still in uploaded (pending) status
    public function pending(): \Illuminate\Http\JsonResponse
    {
        $uploadedStatus = Status::where('slug', 'uploaded')->first();

        $transport_ids = Transport::where('status_id', $uploadedStatus->id)
            ->whereNotNull('transport_id')
            ->pluck('transport_id');

        return response()->json(['transports' => $transport_ids]);
    }

    // Bulk update transport status to processed after received invoices created in Damaro
    public function invoiced_bulk(Request $request): \Illuminate\Http\Response
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return response([], 200);
        }

        $uploadedStatus = Status::where('slug', 'uploaded')->first();
        $processedStatus = Status::where('slug', 'processed')->first();

        Transport::whereIn('transport_id', $ids)
            ->where('status_id', $uploadedStatus->id)
            ->update(['status_id' => $processedStatus->id]);

        return response([], 200);
    }

    // Update transport status to processed after received invoice is created in Damaro
    public function invoiced(TransportDataRequest $request){
        $transport = Transport::where('transport_id', $request->id)->firstOrFail();

        $uploadedStatus = Status::where('slug', 'uploaded')->first();

        if ($transport->status_id === $uploadedStatus->id) {
            $transport->status_id = Status::where('slug', 'processed')->first()->id;
            $transport->save();
        }

        return response([], 200);
    }

    public function bill_delete(DeleteFileRequest $request)
    {
        Log::info('API bill_delete requested', [
            'transport_id' => $request->id,
        ]);

        // zahrnieme aj soft-deleted záznamy
        $transport = Transport::withTrashed()
            ->where('transport_id', $request->id)
            ->firstOrFail();

        Log::info('API bill_delete transport found', [
            'id'          => $transport->id,
            'transport_id'=> $transport->transport_id,
            'deleted_at'  => $transport->deleted_at,
        ]);

        // ak je záznam soft-deleted, obnovíme ho
        if ($transport->trashed()) {
            $transport->restore();
            Log::info('API bill_delete restored soft-deleted transport', [
                'id' => $transport->id,
            ]);
        }

        $before = [
            'bill_sent' => $transport->bill_sent,
            'bill_file' => $transport->bill_file,
        ];

        $transport->update([
            'bill_sent' => 0,
            'bill_file' => null,
        ]);

        Log::info('API bill_delete completed', [
            'transport_id' => $request->id,
            'before'       => $before,
            'after'        => [
                'bill_sent' => $transport->bill_sent,
                'bill_file' => $transport->bill_file,
            ],
        ]);

        return response([], 200);
    }

    public function docs_delete(DeleteFileRequest $request)
    {
        Log::info('API docs_delete requested', [
            'transport_id' => $request->id,
        ]);

        // zahrnieme aj soft-deleted záznamy
        $transport = Transport::withTrashed()
            ->where('transport_id', $request->id)
            ->firstOrFail();

        Log::info('API docs_delete transport found', [
            'id'          => $transport->id,
            'transport_id'=> $transport->transport_id,
            'deleted_at'  => $transport->deleted_at,
        ]);

        // ak je záznam soft-deleted, obnovíme ho
        if ($transport->trashed()) {
            $transport->restore();
            Log::info('API docs_delete restored soft-deleted transport', [
                'id' => $transport->id,
            ]);
        }

        $before = [
            'docs_sent' => $transport->docs_sent,
            'docs_file' => $transport->docs_file,
        ];

        $transport->update([
            'docs_sent' => 0,
            'docs_file' => null,
        ]);

        Log::info('API docs_delete completed', [
            'transport_id' => $request->id,
            'before'       => $before,
            'after'        => [
                'docs_sent' => $transport->docs_sent,
                'docs_file' => $transport->docs_file,
            ],
        ]);

        return response([], 200);
    }

    public function unpaid()
    {
        $transports = Transport::withTrashed()
            ->where(fn($q) => $q->whereNull('paid_at')->orWhere('status_id', '!=', 3))
            ->whereNotNull('transport_id')
            ->where('created_at', '>=', now()->subYear())
            ->select('id', 'transport_id')
            ->get();

        return response()->json(['transports' => $transports]);
    }

    public function sync(Request $request)
    {
        $payments = $request->all();

        $paidStatus = Status::where('slug', 'paid')->first();

        $updated = 0;
        collect($payments)->chunk(500)->each(function ($chunk) use (&$updated, $paidStatus) {
            foreach ($chunk as $payment) {
                if (empty($payment['paid_at'])) continue;

                $affected = Transport::withTrashed()
                    ->where('id', $payment['id'])
                    ->update([
                        'paid_at'   => $payment['paid_at'],
                        'status_id' => $paidStatus->id,
                    ]);

                $updated += $affected;
            }
        });

        return response()->json(['updated' => $updated]);
    }

    // Get visible transports based on visibility column and normal logic
    public function get_visible_transports()
    {
        $transports = Transport::withTrashed()
            ->whereNotNull('transport_id')
            ->visible()
            ->pluck('transport_id');

        return response()->json(['transports' => $transports]);
    }

    public function byStatus(Request $request): \Illuminate\Http\JsonResponse
    {
        $slugs = $request->input('statuses', []);

        if (empty($slugs)) {
            return response()->json(['transports' => []]);
        }

        $statusIds = Status::whereIn('slug', $slugs)->pluck('id', 'slug');

        $result = [];
        foreach ($slugs as $slug) {
            if ($slug === 'none') {
                $result['none'] = Transport::withTrashed()
                    ->whereNull('status_id')
                    ->whereNotNull('transport_id')
                    ->pluck('transport_id')
                    ->toArray();
                continue;
            }

            if (!isset($statusIds[$slug])) continue;

            $result[$slug] = Transport::withTrashed()
                ->where('status_id', $statusIds[$slug])
                ->whereNotNull('transport_id')
                ->pluck('transport_id')
                ->toArray();
        }

        return response()->json(['transports' => $result]);
    }

    public function withUnsentFiles(): \Illuminate\Http\JsonResponse
    {
        $transports = Transport::withTrashed()
            ->whereNotNull('transport_id')
            ->whereNull('status_id')
            ->where(fn($q) => $q->where('bill_sent', 0)->orWhere('docs_sent', 0))
            ->whereHas('files', fn($q) => $q->whereIn('type', ['bill', 'docs'])->where('created_at', '<', now()->subDay()))
            ->with(['files' => fn($q) => $q->whereIn('type', ['bill', 'docs'])->where('created_at', '<', now()->subDay())])
            ->get()
            ->map(fn($t) => [
                'transport_id' => (int) $t->transport_id,
                'has_bill'     => $t->files->where('type', 'bill')->isNotEmpty() && !$t->bill_sent,
                'has_docs'     => $t->files->where('type', 'docs')->isNotEmpty() && !$t->docs_sent,
                'uploaded_at'  => $t->files->min('created_at'),
            ])
            ->filter(fn($item) => $item['has_bill'] || $item['has_docs'])
            ->values();

        return response()->json(['transports' => $transports]);
    }

    public function setStatusBulk(Request $request): \Illuminate\Http\JsonResponse
    {
        $updates = $request->input('updates', []);

        if (empty($updates)) {
            return response()->json(['updated' => 0]);
        }

        $statusCache = [];
        $updated = 0;

        foreach ($updates as $item) {
            $slug = $item['status'] ?? null;
            $transportId = $item['id'] ?? null;

            if (!$slug || !$transportId) continue;

            if (!isset($statusCache[$slug])) {
                $statusCache[$slug] = Status::on('mysql')->where('slug', $slug)->value('id');
            }

            $statusId = $statusCache[$slug];
            if (!$statusId) continue;

            $data = ['status_id' => $statusId];
            if (!empty($item['paid_at'])) {
                $data['paid_at'] = $item['paid_at'];
            }

            $affected = Transport::on('mysql')->withTrashed()
                ->where('transport_id', $transportId)
                ->update($data);

            $updated += $affected;
        }

        return response()->json(['updated' => $updated]);
    }

    // Bulk priradenie fakturačnej firmy k transportom (z Damara).
    // Payload: ['updates' => [['id' => transport_id, 'company_id' => 123|null], ...]]
    // Používa sa pri AI kontrole nahrávaných faktúr (firma + DPH logika).
    public function setCompanyBulk(Request $request): \Illuminate\Http\JsonResponse
    {
        $updates = $request->input('updates', []);

        if (empty($updates)) {
            return response()->json(['updated' => 0]);
        }

        $updated = 0;

        foreach ($updates as $item) {
            $transportId = $item['id'] ?? null;
            if (!$transportId) continue;

            $companyId = $item['company_id'] ?? null;
            $companyId = $companyId !== null && $companyId !== '' ? (int) $companyId : null;

            $affected = Transport::on('mysql')->withTrashed()
                ->where('transport_id', $transportId)
                ->update(['company_id' => $companyId]);

            $updated += $affected;
        }

        return response()->json(['updated' => $updated]);
    }

    // Set visibility for transport: null = normálna logika, 'hidden' = vždy skryť, 'visible' = vždy zobraziť
    public function set_visibility(Request $request)
    {
        $request->validate([
            'id'         => 'required',
            'visibility' => 'nullable|in:hidden,visible',
        ]);

        $transport = Transport::on('mysql')->withTrashed()
            ->where('transport_id', $request->id)
            ->firstOrFail();

        $visibility = $request->visibility ?: null;
        $transport->visibility = $visibility;
        $transport->save();

        Log::info('Transport visibility set', [
            'transport_id' => $request->id,
            'visibility'   => $request->visibility,
        ]);

        return response()->json([
            'ok'         => true,
            'visibility' => $transport->visibility,
        ]);
    }
}