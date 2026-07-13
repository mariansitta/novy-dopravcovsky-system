<?php

namespace App\Models;

class TransportNotice extends BaseModel
{
    protected $table = 'transport_notices';

    protected $fillable = [
        'transport_id',
        'body',
        'slot',
        'source',
    ];

    public function transport()
    {
        return $this->belongsTo(Transport::class);
    }

    // Defenzívny append-only zápis histórie – chyba zápisu nesmie
    // zhodiť API volanie z damara ani upload dopravcu.
    public static function record($transportId, ?string $body, string $source, ?string $slot = null): void
    {
        if ($body === null || trim($body) === '') {
            return;
        }

        try {
            static::create([
                'transport_id' => $transportId,
                'body' => $body,
                'slot' => $slot,
                'source' => $source,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('TransportNotice record failed', [
                'transport_id' => $transportId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
