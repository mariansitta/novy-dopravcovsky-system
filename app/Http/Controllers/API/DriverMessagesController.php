<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\DriverMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DriverMessagesController extends Controller
{
    // Vráti nové správy od dopravcov (synced_at = null, direction = 'driver')
    // Damaro ich stiahne a uloží do svojej DB
    public function get()
    {
        $messages = DriverMessage::where('direction', 'driver')
            ->whereNull('synced_at')
            ->get()
            ->map(fn ($msg) => [
                'id'         => $msg->id,
                'driver_id'  => $msg->driver_id,
                'body'       => $msg->body,
                'created_at' => $msg->created_at,
            ]);

        return response()->json(['messages' => $messages]);
    }

    // Označí správy od dopravcu ako prenesené do Damaro
    public function mark_synced(Request $request)
    {
        $ids = $request->input('ids', []);

        DriverMessage::whereIn('id', $ids)
            ->where('direction', 'driver')
            ->update(['synced_at' => now()]);

        return response()->json(['ok' => true]);
    }

    // Prijme odpovede z Damaro a uloží ich do dopravcovského systému
    public function sync(Request $request)
    {
        $messages = $request->input('messages', []);

        foreach ($messages as $msg) {
            // Ulož len ak ešte neexistuje (podľa driver_id + created_at)
            DriverMessage::firstOrCreate(
                [
                    'driver_id'  => $msg['driver_id'],
                    'direction'  => 'damaro',
                    'created_at' => $msg['created_at'],
                ],
                [
                    'body'      => $msg['body'],
                    'read_at'   => null,
                    'synced_at' => now(),
                ]
            );
        }

        Log::info('DriverMessages: synced Damaro replies', [
            'count' => count($messages),
        ]);

        return response()->json(['ok' => true]);
    }
}