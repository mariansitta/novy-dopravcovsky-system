<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmailUnsubscribesDeleteRequest;
use App\Models\EmailUnsubscribe;

class EmailUnsubscribesController extends Controller
{

    // Get email unsubscribes
    public function get(){
        $email_unsubscribes = EmailUnsubscribe::query()->get();

        return response([
            'hashes' => $email_unsubscribes->pluck('hash')->toArray(),
            'email_unsubscribes' => $email_unsubscribes->keyBy('hash')->map(function ($item){
                return $item['created_at'];
            })->all(),
        ], 200);
    }

    // Delete email unsubscribes
    public function delete(EmailUnsubscribesDeleteRequest $request){
        if ($hashes = $request->delete){
            EmailUnsubscribe::whereIn('hash', $hashes)->delete();
        }

        return response([], 200);
    }

    // Get email unsubscribe url
    public function url(){
        return response([
            'url' => route('unsubscribe'),
        ], 200);
    }

}
