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

    // Update transport status to processed
    public function status(TransportDataRequest $request){
        $transport = Transport::where('transport_id', $request->id)->firstOrFail();

        if($transport->bill_sent && $transport->docs_sent){
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

}
