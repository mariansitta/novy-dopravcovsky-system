<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\DocumentsRequestedRequest;
use App\Http\Requests\GetDriverLinkRequest;
use App\Http\Requests\TransportDeleteRequest;
use App\Http\Requests\TransportsExistRequest;
use App\Http\Requests\ExistingTransportsRequest;
use App\Http\Requests\ModifyDriverNoticeRequest;
use Illuminate\Support\Facades\Mail;
use App\Mail\NotifyMail;
use App\Models\Status;
use App\Models\Transport;
use App\Models\User;
use App\Traits\DeleteTrait;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;

class DamaroController extends Controller
{
    use DeleteTrait;

    // Create transport copy and user copy if doesnt exists
    public function transport_request(DocumentsRequestedRequest $request){
        /*
        // If transport with transport_id exists delete
        $transport = Transport::where('transport_id', $request->transport['transport_id'])->first();

        if( isset($transport) ){
            $this->delete_files($transport->files);
            $transport->delete();
        }
        */
        
        $transport = Transport::withTrashed()->where('transport_id', $request->transport['transport_id'])->first();

        // If driver with driver_id doesnt exist create
        $user = User::where('driver_id', $request->driver['driver_id'])->first();

        $link = null;
        if( ! isset($user) ){
            $user = User::create([
                'driver_id' => $request->driver['driver_id'],
                'name' => $request->driver['name'],
                'email' => $request->driver['email'],
                'country' => $request->driver['country'],
            ]);

            $link = $user->generateLink(Str::random(32));
        } else {
            $link = route('transports.enter', [ 'token' => $user->token ]);
        }

        if (!$transport) {
            $transport = $user->transports()->create($request->transport);
        } else {
            
            $this->delete_files($transport->files);

            /*
            foreach ($transport->files as $file) {
                unlink($file->path . $file->filename);
                $file->delete();
            }
            */

            if ($transport->trashed()) {
                $transport->restore();
            }
            
            if (isset($request->transport['bill_file'])) {
                $transport->bill_sent = 1;
                $transport->bill_file = $request->transport['bill_file'];
            } else {
                $transport->bill_sent = 0;
                $transport->bill_file = null;
            }
            
            if (isset($request->transport['docs_file'])) {
                $transport->docs_sent = 1;
                $transport->docs_file = $request->transport['docs_file'];
            } else {
                $transport->docs_sent = 0;
                $transport->docs_file = null;
            }

            /* update */
            $user = User::where('driver_id', $request->driver['driver_id'])->first();
            if ($user) {
                $transport->user_id = $user->id;
            }
            if (isset($request->transport['loading_date'])) {
                $transport->loading_date = $request->transport['loading_date'];
            }
            if (isset($request->transport['loading'])) {
                $transport->loading = $request->transport['loading'];
            }
            if (isset($request->transport['unloading'])) {
                $transport->unloading = $request->transport['unloading'];
            }
            if (isset($request->transport['ldm'])) {
                $transport->ldm = $request->transport['ldm'];
            }
            if (isset($request->transport['weight'])) {
                $transport->weight = $request->transport['weight'];
            }
            if (isset($request->transport['timocom_id'])) {
                $transport->timocom_id = $request->transport['timocom_id'];
            }
            if (isset($request->transport['raal_id'])) {
                $transport->raal_id = $request->transport['raal_id'];
            }
            if (isset($request->transport['driver_plate_number'])) {
                $transport->driver_plate_number = $request->transport['driver_plate_number'];
            }
            if (isset($request->transport['driver_price'])) {
                $transport->driver_price = $request->transport['driver_price'];
            }

            if (isset($request->transport['driver_notice'])) {
                $transport->driver_notice = $request->transport['driver_notice'];
            } else {
                $transport->driver_notice = null;
            }

            $transport->save();
        }

        $bill = isset($transport) && (isset($transport->bill) && !($transport->bill_sent) && is_readable($transport->bill->get_path()));
        $docs = isset($transport) && (isset($transport->docs) && !($transport->docs_sent) && is_readable($transport->docs->get_path()));
        $due_date = optional($transport)->due_date;
        $driver_notice = optional($transport)->driver_notice;

        return response()->json([
            'link' => isset($link) ? $link : null,
            'transport_id' => $transport->id,
            'bill' => $bill,
            'docs' => $docs,
            'due_date' => $due_date,
            'driver_notice' => $driver_notice,
        ], 200);
    }

    // Request existing driver url or create driver and return url
    public function transport_url(GetDriverLinkRequest $request){
        $transport = Transport::where('transport_id', $request->transport_id)->first();
    
        $link = route('transports.enter', ['token' => optional($transport)->user->token]);
    
        return response()->json([
            //'link' => isset($transport) ? $transport->user->link : null,
            'link' => $link,
        ], 200);
    }
    

    // Damaro: Notify old drivers action - Check if transports exist by array of transport_ids
    public function transports_exist(TransportsExistRequest $request){
        $transport_ids = Transport::whereIn('transport_id', $request->transports ? $request->transports : [])->get()->pluck('transport_id');

        return response()->json([
            'transports' => $transport_ids,
        ], 200);
    }

    // Damaro: Delete driver action - Delete transport
    public function transport_delete(TransportDeleteRequest $request){
        $transport = Transport::where('transport_id', $request->id)->first();
        $exists = isset($transport);

        if($exists){
            foreach ($transport->files as $file) $file->delete();
            $transport->delete();
        }

        return response([
            'exists' => $exists,
        ], 200);
    }

    // Damaro: Set invoice payment date - Update status
    public function transport_payment(TransportDeleteRequest $request){
        $transport = Transport::where('transport_id', $request->id)->firstOrFail();

        $transport->status_id = Status::where('slug', 'paid')->first()->id;
        $transport->save();

        return response([], 200);
    }

    // Delete transports which doesnt exists in damaro (both files uploaded)
    public function existing_transports(ExistingTransportsRequest $request) {
        $transportIds = $request->input('tranports_ids', []);

        $transports_delete_ids = array();
        $transports_delete = Transport::whereNotIn('transport_id', $transportIds)->get();

        foreach ($transports_delete as $transport) {
            if ($transport->bill_file && $transport->docs_file) {
                $this->delete_files($transport->files);
                $transport->delete();
                $transports_delete_ids[] = $transport->transport_id;
            }
        }

        sort($transports_delete_ids);
        return response()->json([
            'delete' => $transports_delete_ids,
        ], 200);
    }

    // Modify driver_notice of transport
    public function modify_driver_notice(ModifyDriverNoticeRequest $request) {
        $transport = Transport::withTrashed()->where('transport_id', $request->id)->firstOrFail();
        if (isset($request->driver_notice)) {
            $sendemail = false;

            if ($transport->driver_notice != $request->driver_notice) {
                $sendemail = true;
            }
            $transport->driver_notice = $request->driver_notice;

            if ($sendemail && isset($transport->user->notify_email)) {

                $lang = in_array($transport->user->country, ['SK', 'CZ']) ? 'sk' : 'en';

                $link = route('transports.enter', [ 'token' => $transport->user->token]);
                Mail::to($transport->user->notify_email)->send(new NotifyMail($transport->number, $request->driver_notice, $link, $lang));
            }
        } else {
            $transport->driver_notice = null;
        }
        $transport->save();

        return response()->json([
        ], 200);
    }

    // create link for new user
    public function assign_driver_link(Request $request) {

        // If driver with driver_id doesnt exist create
        $user = User::where('driver_id', $request->driver['driver_id'])->first();

        $link = null;
        if( ! isset($user) ){
            $user = User::create([
                'driver_id' => $request->driver['driver_id'],
                'name' => $request->driver['name'],
                'email' => $request->driver['email'],
                'country' => $request->driver['country'],
            ]);

            $link = $user->generateLink(Str::random(32));
        } else {
            $link = route('transports.enter', [ 'token' => $user->token ]);
        }

        return response()->json([
            'link' => $link,
        ], 200);
    }
}
