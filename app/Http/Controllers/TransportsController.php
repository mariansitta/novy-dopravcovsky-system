<?php

namespace App\Http\Controllers;

use App\Http\Requests\BillRequest;
use App\Http\Requests\DocsRequest;
use App\Http\Requests\BillDocumentRequest;
use App\Http\Requests\DocsDocumentRequest;
use App\Http\Requests\DocumentsRequest;
use App\Http\Requests\DeleteDocumentRequest;
use App\Models\Status;
use App\Models\Transport;
use App\Models\TransportStatus;
use App\Traits\UploadTrait;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TransportsController extends Admin\AdminController
{
    use UploadTrait;

    public function index(){
        $transport = auth()->user()->transport;

        return view('web.transports.index', compact( 'transport'));
    }

    public function bill_document(BillDocumentRequest $request, $id) {
        $transport = Transport::where('id', $id)->first();

        if (isset($transport->docs) && isset($transport->bill)) {
            return redirect()->route('index');
        }

        $status = Status::where('slug', 'uploaded')->first();
        $transport->status_id = $status->id;
        $filename = $this->upload_file($request, 'bill', 'transports', $transport, 'bill');
        $transport->bill_file = $filename;
        $transport->bill_sent = 0;

        $transport->due_date = now()->addDays($transport->due_days);
        $transport->save();

        $this->_setFlashMessage($request, 'success', trans('texts.upload-success-alert'));

        return redirect()->route('index');
    }

    public function doc_document(DocsDocumentRequest $request, $id) {
        $transport = Transport::where('id', $id)->first();

        if (isset($transport->docs) && isset($transport->bill)) {
            return redirect()->route('index');
        }

        $status = Status::where('slug', 'uploaded')->first();
        $transport->status_id = $status->id;
        $filename = $this->upload_file($request, 'docs', 'transports', $transport, 'docs');
        $transport->docs_file = $filename;
        $transport->docs_sent = 0;

        $transport->due_date = now()->addDays($transport->due_days);
        $transport->save();

        $this->_setFlashMessage($request, 'success', trans('texts.upload-success-alert'));

        return redirect()->route('index');
    }

    public function documents(DocumentsRequest $request, $id) {
        $transport = Transport::where('id', $id)->first();

        if (isset($transport->docs) && isset($transport->bill)) {
            return redirect()->route('index');
        }

        $status = Status::where('slug', 'uploaded')->first();
        $transport->status_id = $status->id;
        $this->upload_file($request, 'bill', 'transports', $transport, 'bill');
        $this->upload_file($request, 'docs', 'transports', $transport, 'docs');

        $transport->due_date = now()->addDays($transport->due_days);
        $transport->save();

        $transport->user->notify_email = $request->email;
        $transport->user->save();

        $this->_setFlashMessage($request, 'success', trans('texts.upload-success-alert'));

        return redirect()->route('index');
    }

    public function edit($id)
    {
        $transport = Transport::findOrFail($id);
        return view('web.transports.edit', compact('transport'));
    }

    public function transport_status_form(Request $request, $id) {
        $transport = Transport::where('id', $id)->first();

        $datetime = null;

        if ($transport && $request->transport_status) {
            $TransportStatus = TransportStatus::create([
                'transport_id' => $transport->id,
                'status' => $request->transport_status,
                'datetime' => $datetime,
            ]);
            $this->_setFlashMessage($request, 'success', trans('texts.transport-status-success-alert'));
        }

        return redirect()->route('index');
    }

    public function transport_status($id)
    {
        $transport = Transport::findOrFail($id);
        return view('web.transports.transport_status', compact('transport'));
    }

    public function bill($id)
    {
        $transport = Transport::findOrFail($id);
        return view('web.transports.bill', compact('transport'));
    }

    public function docs($id)
    {
        $transport = Transport::findOrFail($id);
        return view('web.transports.docs', compact('transport'));
    }

    // Login user with token
    public function enter($token) {
        Auth::logout();

        $user = User::where('token', $token)->first();

        if ($user) {
            Auth::login($user);

            return redirect()->route('index');
        } else {
            return redirect()->route('login');
        }
    }
    
    public function document_delete(DeleteDocumentRequest $request, $id){
        $transport = Transport::findOrFail($id);

        $transport->files()->where('type', $request->column)->delete();

        $this->_setFlashMessage($request, 'success', "Dokument bol úspešne vymazaný.");

        return redirect()->route('index');
    }

    public function _table() {
        $locale = in_array(app()->getLocale(), ['SK', 'CZ']) ? 'sk' : 'en';

        $table = DB::table('transports')
            //->whereDate('transports.created_at', '>', now()->addDays(-90)->startOfDay()->format('Y-m-d'))
            ->where(function($query) {
                $query->whereDate('transports.created_at', '>', now()->addDays(-60)->startOfDay()->format('Y-m-d'))
                      ->orWhere('bill_sent', 0)
                      ->orWhere('docs_sent', 0);
            })
            ->select('transports.id AS trans_id',
                'number',
                'loading_date',
                'due_date',
                'loading',
                'unloading',
                'ldm',
                'weight',
//                'timocom_id',
//                'raal_id',
                'driver_notice',
                'bill_file',
                'docs_file',
                'driver_plate_number',
                'driver_price',
                "statuses.name_$locale AS status_name",
                "statuses.slug AS status_slug",
                'transports.status_id AS status_id',
                //DB::raw("(SELECT CONCAT(path, '', filename) AS full_path FROM files WHERE type = 'bill' AND fileable_id = transports.id AND deleted_at IS NULL) AS bill"),
                //DB::raw("(SELECT CONCAT(path, '', filename) AS full_path FROM files WHERE type = 'docs' AND fileable_id = transports.id AND deleted_at IS NULL) AS docs"),
                DB::raw("(SELECT CONCAT(path, '', filename) FROM files WHERE type = 'bill' AND fileable_id = transports.id AND deleted_at IS NULL ORDER BY id DESC LIMIT 1) AS bill"),
                DB::raw("(SELECT CONCAT(path, '', filename) FROM files WHERE type = 'docs' AND fileable_id = transports.id AND deleted_at IS NULL ORDER BY id DESC LIMIT 1) AS docs"),
                DB::raw("CASE WHEN deleted_at IS NULL THEN 0 ELSE 1 END AS is_deleted"),
                DB::raw("(SELECT status FROM transport_statuses WHERE transport_statuses.transport_id = transports.id ORDER BY created_at DESC LIMIT 1) AS transport_status")
                )
            ->leftJoin('statuses', 'transports.status_id', '=', 'statuses.id')
            ->where('user_id', '=', auth()->user()->id)
            //->whereNUll('deleted_at')
            ->where(function ($query) {
                // Include soft deleted records not older than 10 days
                $query->whereDate('deleted_at', '>', now()->subDays(10)->startOfDay()->format('Y-m-d'))
                      ->orWhereNull('deleted_at');
            })
            ->orderBy('transports.number', 'desc')->get();
        return response()->json([
            'data' => $table
        ]);
    }

}
