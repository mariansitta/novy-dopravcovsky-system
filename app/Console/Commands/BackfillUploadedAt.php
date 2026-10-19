<?php

namespace App\Console\Commands;

use App\Models\File;
use App\Models\Transport;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Doplní bill_uploaded_at / docs_uploaded_at pre transporty nahraté pred zavedením
 * stĺpcov. Berie dátum posledného súboru na disku (to isté, čo sa doteraz ručne
 * pozeralo cez FTP), a keď súbor chýba, files.created_at.
 */
class BackfillUploadedAt extends Command
{
    protected $signature = 'transports:backfill-uploaded-at {--dry-run : Iba vypíše, nič neuloží}';

    protected $description = 'Doplní čas nahratia dokladov (bill/docs) z dátumu súboru na disku';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $updated = 0;

        Transport::withTrashed()
            ->where(fn($q) => $q->whereNull('bill_uploaded_at')->orWhereNull('docs_uploaded_at'))
            ->whereHas('files', fn($q) => $q->withTrashed()->whereIn('type', ['bill', 'docs']))
            ->chunkById(200, function ($transports) use ($dryRun, &$updated) {
                foreach ($transports as $transport) {
                    $changed = false;

                    foreach (['bill', 'docs'] as $slot) {
                        if ($transport->{$slot . '_uploaded_at'} !== null) {
                            continue;
                        }

                        $file = $transport->files()->withTrashed()
                            ->where('type', $slot)
                            ->orderBy('id', 'desc')
                            ->first();

                        if (! $file) {
                            continue;
                        }

                        [$uploadedAt, $source] = $this->resolveUploadedAt($file);

                        $this->line(sprintf('#%d (damaro %s) %s: %s [%s]',
                            $transport->id, $transport->transport_id ?? '-', $slot, $uploadedAt, $source));

                        $transport->{$slot . '_uploaded_at'} = $uploadedAt;
                        $changed = true;
                    }

                    if ($changed) {
                        $updated++;
                        if (! $dryRun) {
                            $transport->timestamps = false;
                            $transport->saveQuietly();
                        }
                    }
                }
            });

        $this->info(($dryRun ? '[dry-run] ' : '') . "Transportov: {$updated}");

        return self::SUCCESS;
    }

    private function resolveUploadedAt(File $file): array
    {
        $fullPath = public_path($file->path . $file->filename);

        if (is_file($fullPath) && ($mtime = filemtime($fullPath)) !== false) {
            return [Carbon::createFromTimestamp($mtime, config('app.timezone')), 'file'];
        }

        return [$file->created_at, 'created_at'];
    }
}
