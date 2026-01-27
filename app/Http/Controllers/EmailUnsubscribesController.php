<?php

namespace App\Http\Controllers;

use App\Models\EmailUnsubscribe;
use Illuminate\Http\Request;

class EmailUnsubscribesController extends Controller
{

    public function unsubscribe(Request $request){
        if($hash = $request->hash){
            $email_unsubscribe = EmailUnsubscribe::create([
                'hash' => $hash,
            ]);
        }

        return view('web.unsubscribe.confirm');
    }

}
