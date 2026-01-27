<?php

namespace App\Http\Controllers;

use App\Models\Status;
use App\Models\Transport;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TemporaryController extends Controller
{

    // Get transport ids
    public function transport_ids(Request $request){
        $transports = Transport::whereNotNull('users.driver_id')->leftJoin('users', 'transports.user_id', '=', 'users.id')->get();

        return response()->json([
            'transport_ids' => $transports->pluck('transport_id')->toArray(),
        ], 200);
    }

    // Get driver ids
    public function driver_ids(Request $request){
        $data = User::whereNotNull('driver_id')->get();

        return response()->json([
            'driver_ids' => $data->pluck('driver_id')->toArray(),
        ], 200);
    }

    // Get transport id => driver id pairs
    public function transport_driver_pairs(Request $request){
        $transports = Transport::whereNotNull('driver_id')->leftJoin('users', 'transports.user_id', '=', 'users.id')
            ->get([ 'transports.transport_id AS transport_id', 'users.driver_id AS driver_id' ]);

        return response()->json([
            'transport_ids' => $transports->mapWithKeys(function ($transport){
                return [ $transport->transport_id => $transport->driver_id ];
            }),
        ], 200);
    }

    public function transport_status_pairs(Request $request){
        $transports = Transport::whereNotNull('driver_id')
            ->leftJoin('users', 'transports.user_id', '=', 'users.id')
            ->leftJoin('statuses', 'transports.status_id', '=', 'statuses.id')
            ->get([
                'transports.transport_id as transport_id',
                'transports.status_id as status_id',
                'statuses.slug as status_slug',
            ]);

        return response()->json([
            'transport_ids' => $transports->mapWithKeys(function ($transport){
                return [ $transport->transport_id => $transport->status_id == null ? 'none' : $transport->status_slug ];
            }),
        ], 200);
    }

    // Get transports info
    public function transports_info(Request $request){
        $transport_ids = $request->transports;

        $transports = Transport::whereIn('transport_id', $transport_ids)
            ->get()->mapWithKeys(function ($transport){
                return [ $transport->transport_id => [
                    'number' => $transport->number,
                    'loading' => $transport->loading,
                    'loading_date' => $transport->loading_date,
                    'unloading' => $transport->unloading,
                    'ldm' => $transport->ldm,
                    'timocom_id' => $transport->timocom_id,
                    'raal_id' => $transport->raal_id,
                    'weight' => $transport->weight,
                    'driver_plate_number' => $transport->driver_plate_number,
                    'driver_price' => $transport->driver_price,
                ] ];
            });

        return response()->json([
            'transports' => $transports->toArray(),
        ]);
    }

    // Get drivers info
    public function drivers_info(Request $request){
        $driver_ids = $request->drivers;

        $drivers = User::whereIn('driver_id', $driver_ids)
            ->get()->mapWithKeys(function ($driver){
                return [ $driver->driver_id => [
                    'name' => $driver->name,
                ] ];
            });

        return response()->json([
            'drivers' => $drivers->toArray(),
        ]);
    }

    function transport_fix(Request $request){
        $transport = Transport::where('transport_id', $request->transport_id)->firstOrFail();

        $user = User::create([
            'driver_id' => $request->driver['driver_id'],
            'name' => $request->driver['name'],
            'email' => $request->driver['email'],
            'country' => $request->driver['country'],
        ]);

        $link = $user->generateLink(Str::random(32));

        $transport->user_id = $user->id;
        $transport->save();

        return response()->json([
            'link' => $link,
        ], 200);
    }

}
